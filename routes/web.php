<?php

use App\Http\Controllers\CheckController;
use App\Http\Controllers\InsightController;
use App\Http\Controllers\ModeController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('landing');
})->name('landing');

Route::middleware(['auth', 'verified'])->group(function () {
    // Onboarding (sin plan.required: el propio show() decide)
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('/onboarding/upload', [OnboardingController::class, 'uploadPdf'])->name('onboarding.upload');

    // App: requiere tener plan activo. Si no, EnsureHasActivePlan redirige a /onboarding.
    Route::middleware('plan.required')->group(function () {
        Route::get('/dashboard', function () {
            $plan = auth()->user()->activeNutritionalPlan;
            $today = now()->toDateString();

            $modeRecord = \App\Models\DailyMode::where('date', $today)->first();
            $mode = $modeRecord?->mode ?? 'descanso';

            $extracted = $plan?->extracted_data ?? [];
            $comidasBase = $extracted['comidas'] ?? [];
            $comidasExtra = match ($mode) {
                'entreno' => $extracted['comidas_entreno'] ?? [],
                'competencia' => $extracted['comidas_competencia'] ?? [],
                default => [],
            };
            $comidas = array_merge($comidasBase, $comidasExtra);

            $checksToday = [];
            $notesToday = [];
            if ($plan) {
                foreach (\App\Models\DailyCheck::where('date', $today)->get() as $c) {
                    $checksToday[$c->item_id] = $c->status;
                    if ($c->note) {
                        $notesToday[$c->item_id] = $c->note;
                    }
                }
            }

            $totalComidas = count($comidas);
            $score = collect($checksToday)->sum(fn ($s) => match ($s) {
                'fiel' => 1,
                'parcial' => 0.5,
                default => 0,
            });
            $fidelidad = $totalComidas > 0 ? (int) round(($score / $totalComidas) * 100) : 0;

            // Heatmap últimos 7 días (hoy y 6 atrás).
            // Denominador = total de comidas con el modo de cada día (si existe registro);
            // si no, usa el plan base. Sin checks => null (gris).
            $heatmap = [];
            if ($plan) {
                $modesByDate = \App\Models\DailyMode::whereBetween('date', [
                    now()->subDays(6)->toDateString(),
                    $today,
                ])->pluck('mode', 'date')->map(fn ($m, $d) => $m)->toArray();

                $checksByDate = \App\Models\DailyCheck::whereBetween('date', [
                    now()->subDays(6)->toDateString(),
                    $today,
                ])->get()->groupBy(fn ($c) => $c->date->toDateString());

                for ($i = 6; $i >= 0; $i--) {
                    $d = now()->subDays($i);
                    $key = $d->toDateString();
                    $dayMode = $modesByDate[$key] ?? 'descanso';
                    $dayExtra = match ($dayMode) {
                        'entreno' => $extracted['comidas_entreno'] ?? [],
                        'competencia' => $extracted['comidas_competencia'] ?? [],
                        default => [],
                    };
                    $totalDia = count($comidasBase) + count($dayExtra);
                    $checksDia = $checksByDate->get($key, collect());
                    $scoreDia = $checksDia->sum(fn ($c) => match ($c->status) {
                        'fiel' => 1, 'parcial' => 0.5, default => 0,
                    });

                    $heatmap[] = [
                        'date' => $key,
                        'label' => $d->isoFormat('dd'), // L M X J V S D
                        'day' => $d->day,
                        'fidelidad' => ($totalDia > 0 && $checksDia->count() > 0)
                            ? (int) round(($scoreDia / $totalDia) * 100)
                            : null,
                        'is_today' => $key === $today,
                    ];
                }
            }

            return view('dashboard', compact('plan', 'comidas', 'checksToday', 'notesToday', 'fidelidad', 'mode', 'heatmap'));
        })->name('dashboard');

        Route::post('/api/checks', [CheckController::class, 'store'])->name('checks.store');
        Route::post('/api/mode', [ModeController::class, 'store'])->name('mode.store');
        Route::get('/api/insight/weekly', [InsightController::class, 'weekly'])->name('insight.weekly');

        Route::get('/api/day/{date}', function (string $date) {
            try {
                $d = \Carbon\Carbon::parse($date);
            } catch (\Throwable) {
                abort(422, 'Fecha inválida');
            }
            // Solo permite ver últimos 30 días por seguridad/limites futuros
            if ($d->diffInDays(now()) > 30 || $d->isAfter(now())) {
                abort(403);
            }
            $key = $d->toDateString();
            $plan = auth()->user()->activeNutritionalPlan;
            if (! $plan) {
                return response()->json(['error' => 'Sin plan activo'], 422);
            }

            $modeRow = \App\Models\DailyMode::where('date', $key)->first();
            $mode = $modeRow?->mode ?? 'descanso';

            $extracted = $plan->extracted_data ?? [];
            $comidasBase = $extracted['comidas'] ?? [];
            $comidasExtra = match ($mode) {
                'entreno' => $extracted['comidas_entreno'] ?? [],
                'competencia' => $extracted['comidas_competencia'] ?? [],
                default => [],
            };
            $comidas = array_merge($comidasBase, $comidasExtra);

            $checksByItem = \App\Models\DailyCheck::where('date', $key)->get()
                ->keyBy('item_id');

            $items = [];
            foreach ($comidas as $idx => $c) {
                $itemId = $c['id'] ?? \Illuminate\Support\Str::slug($c['nombre'] ?? 'comida-'.$idx);
                $check = $checksByItem->get($itemId);
                $items[] = [
                    'item_id' => $itemId,
                    'nombre' => $c['nombre'] ?? 'Comida',
                    'icono' => $c['icono_sugerido'] ?? '🍽️',
                    'hora' => $c['hora'] ?? null,
                    'status' => $check?->status,
                    'note' => $check?->note,
                ];
            }

            $score = $checksByItem->sum(fn ($c) => match ($c->status) {
                'fiel' => 1, 'parcial' => 0.5, default => 0,
            });
            $fidelidad = count($comidas) > 0
                ? (int) round(($score / count($comidas)) * 100)
                : 0;

            return response()->json([
                'date' => $key,
                'date_label' => $d->isoFormat('dddd, D [de] MMMM'),
                'mode' => $mode,
                'fidelidad' => $fidelidad,
                'items' => $items,
            ]);
        })->name('day.show');

        Route::get('/plan', function () {
            return view('plan', ['plan' => auth()->user()->activeNutritionalPlan]);
        })->name('plan.show');

        Route::delete('/plan/active', function () {
            auth()->user()->activeNutritionalPlan?->delete();
            return redirect()->route('onboarding.show')->with('status', 'Plan eliminado. Suba uno nuevo.');
        })->name('plan.destroy');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
