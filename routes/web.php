<?php

use App\Http\Controllers\CheckController;
use App\Http\Controllers\InsightController;
use App\Http\Controllers\ModeController;
use App\Http\Controllers\Nutri\InvitationController;
use App\Http\Controllers\Nutri\NutriDashboardController;
use App\Http\Controllers\Nutri\PatientController;
use App\Http\Controllers\Nutri\PlanController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Support\PlanData;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->isNutritionist()
            ? redirect()->route('nutri.dashboard')
            : redirect()->route('dashboard');
    }
    return view('landing');
})->name('landing');

Route::view('/terminos', 'legal.terms')->name('legal.terms');
Route::view('/privacidad', 'legal.privacy')->name('legal.privacy');

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

            // Suplementos y farmacología (estructurados) — checkeables aparte.
            $suplementos = $extracted['suplementos'] ?? [];
            $farmacologia = $extracted['farmacologia'] ?? [];
            // Compat: planes viejos con lista plana en suplementos_diarios.
            if (empty($suplementos) && ! empty($extracted['suplementos_diarios'])) {
                $suplementos = collect($extracted['suplementos_diarios'])
                    ->map(fn ($s) => is_array($s) ? ($s['nombre'] ?? '') : (string) $s)
                    ->filter(fn ($n) => trim($n) !== '')
                    ->map(fn ($n) => [
                        'id' => 'sup-'.\Illuminate\Support\Str::slug($n).'-'.substr(md5($n), 0, 4),
                        'nombre' => $n, 'dosis' => null, 'frecuencia' => null, 'nota' => null,
                    ])->values()->all();
            }

            // Preferencia del usuario: ¿los suplementos cuentan en la fidelidad?
            $supplementsAffect = (bool) auth()->user()->supplements_affect_fidelity;

            $checkRecordsToday = $plan
                ? \App\Models\DailyCheck::where('date', $today)->get()
                : collect();

            $checksToday = [];
            $notesToday = [];
            foreach ($checkRecordsToday as $c) {
                $checksToday[$c->item_id] = $c->status;
                if ($c->note) {
                    $notesToday[$c->item_id] = $c->note;
                }
            }

            $fidelidad = PlanData::fidelity($extracted, $mode, $checkRecordsToday, $supplementsAffect);

            // Heatmap rango variable (7 / 30 / 90 días). Default 30.
            $range = (int) request()->query('range', 30);
            if (! in_array($range, [7, 30, 90], true)) {
                $range = 30;
            }

            $heatmap = [];
            $heatmapStats = ['promedio' => 0, 'dias_perfectos' => 0, 'racha_actual' => 0, 'racha_max' => 0, 'dias_con_data' => 0];
            // Gamificación (racha con día de gracia + medallas). Default sin plan.
            $gam = [
                'threshold' => \App\Support\Gamification::threshold(auth()->user()),
                'unlocked' => [],
                'newly' => [],
            ];

            if ($plan) {
                $startDate = now()->subDays($range - 1)->toDateString();
                $modesByDate = \App\Models\DailyMode::whereBetween('date', [$startDate, $today])
                    ->pluck('mode', 'date')->toArray();
                $checksByDate = \App\Models\DailyCheck::whereBetween('date', [$startDate, $today])
                    ->get()->groupBy(fn ($c) => $c->date->toDateString());

                for ($i = $range - 1; $i >= 0; $i--) {
                    $d = now()->subDays($i);
                    $key = $d->toDateString();
                    $dayMode = $modesByDate[$key] ?? 'descanso';
                    $dayChecks = $checksByDate->get($key, collect());
                    // Día con data = tiene al menos un check que cuenta (comida, o
                    // suplemento si la preferencia está activa). Farma nunca cuenta.
                    $hasData = $dayChecks->contains(function ($c) use ($supplementsAffect) {
                        if (str_starts_with($c->item_id, 'farm-')) return false;
                        if (str_starts_with($c->item_id, 'sup-')) return $supplementsAffect;
                        return true;
                    });
                    $f = $hasData
                        ? PlanData::fidelity($extracted, $dayMode, $dayChecks, $supplementsAffect)
                        : null;

                    $heatmap[] = [
                        'date' => $key,
                        'label' => $d->isoFormat('dd'),
                        'day' => $d->day,
                        'fidelidad' => $f,
                        'is_today' => $key === $today,
                        'dow' => (int) $d->dayOfWeekIso, // 1=lun..7=dom
                    ];
                }

                // Stats del rango visible: promedio, días perfectos, días con data.
                $sum = 0;
                $diasConData = 0;
                foreach ($heatmap as $cell) {
                    $f = $cell['fidelidad'];
                    if ($f === null) {
                        continue;
                    }
                    $diasConData++;
                    $sum += $f;
                    if ($f === 100) {
                        $heatmapStats['dias_perfectos']++;
                    }
                }
                $heatmapStats['promedio'] = $diasConData > 0 ? (int) round($sum / $diasConData) : 0;
                $heatmapStats['dias_con_data'] = $diasConData;

                // Racha (con día de gracia + umbral del usuario) y detección de
                // medallas: sobre una ventana fija, NO sobre el rango visible, para
                // que sean estables aunque se cambie la vista 7/30/90.
                $gam = \App\Support\Gamification::evaluate(auth()->user(), $extracted, $supplementsAffect);
                $heatmapStats['racha_actual'] = $gam['racha_actual'];
                $heatmapStats['racha_max'] = $gam['racha_max'];
            }

            return view('dashboard', compact('plan', 'comidas', 'checksToday', 'notesToday', 'fidelidad', 'mode', 'heatmap', 'range', 'heatmapStats', 'suplementos', 'farmacologia', 'supplementsAffect', 'gam'));
        })->name('dashboard');

        Route::post('/api/checks', [CheckController::class, 'store'])->name('checks.store');
        Route::post('/api/mode', [ModeController::class, 'store'])->name('mode.store');

        // Preferencia: ¿los suplementos cuentan en el % de fidelidad?
        Route::post('/api/prefs/supplements-fidelity', function (\Illuminate\Http\Request $request) {
            $validated = $request->validate(['enabled' => ['required', 'boolean']]);
            $user = auth()->user();
            $user->supplements_affect_fidelity = $validated['enabled'];
            $user->save();

            return response()->json(['enabled' => $user->supplements_affect_fidelity]);
        })->name('prefs.supplementsFidelity');

        // Preferencia: % de fidelidad mínimo para que un día cuente en la racha.
        Route::post('/api/prefs/streak-threshold', function (\Illuminate\Http\Request $request) {
            $validated = $request->validate([
                'threshold' => ['required', 'integer', 'min:40', 'max:100'],
            ]);
            $user = auth()->user();
            $user->streak_threshold = $validated['threshold'];
            $user->save();

            return response()->json(['threshold' => $user->streak_threshold]);
        })->name('prefs.streakThreshold');

        // Asignar / editar la hora de una comida en el plan activo del paciente.
        // El usuario puede ponerle hora a una comida que vino sin ella (o vaciarla).
        Route::post('/api/meal-time', function (\Illuminate\Http\Request $request) {
            $validated = $request->validate([
                'item_id' => ['required', 'string'],
                'hora' => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            ], [
                'hora.regex' => 'Use el formato HH:MM (24h).',
            ]);

            $plan = auth()->user()->activeNutritionalPlan;
            if (! $plan) {
                return response()->json(['error' => 'Sin plan activo'], 422);
            }

            $hora = $validated['hora'] ?: null;
            $extracted = $plan->extracted_data ?? [];
            $found = false;

            // Buscamos la comida por su id (o slug del nombre) en los 3 grupos.
            foreach (['comidas', 'comidas_entreno', 'comidas_competencia'] as $grupo) {
                foreach ($extracted[$grupo] ?? [] as $idx => $c) {
                    $id = $c['id'] ?? \Illuminate\Support\Str::slug($c['nombre'] ?? 'comida-'.$idx);
                    if ($id === $validated['item_id']) {
                        $extracted[$grupo][$idx]['hora'] = $hora;
                        $found = true;
                    }
                }
            }

            if (! $found) {
                return response()->json(['error' => 'Comida no encontrada'], 404);
            }

            $plan->extracted_data = $extracted;
            $plan->save();

            return response()->json(['hora' => $hora]);
        })->name('mealtime.store');
        Route::get('/api/insight/weekly', [InsightController::class, 'weekly'])->name('insight.weekly');

        Route::get('/api/day/{date}', function (string $date) {
            try {
                $d = \Carbon\Carbon::parse($date);
            } catch (\Throwable) {
                abort(422, 'Fecha inválida');
            }
            // Solo permite ver últimos 90 días por seguridad/limites futuros
            if ($d->diffInDays(now()) > 90 || $d->isAfter(now())) {
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
            $supplementsAffect = (bool) auth()->user()->supplements_affect_fidelity;

            $checksByItem = \App\Models\DailyCheck::where('date', $key)->get()
                ->keyBy('item_id');

            // Los ítems del popup deben ser EXACTAMENTE los que cuentan en la
            // fidelidad del día: comidas siempre; suplementos sólo si el toggle del
            // usuario está ON. Así el % del popup cuadra con los ítems visibles y
            // con el conteo de "ítems" del HUD de arriba. (Antes mostraba sólo
            // comidas pero el % incluía suplementos → no cuadraba.)
            $items = [];
            foreach (PlanData::meals($extracted, $mode) as $c) {
                $check = $checksByItem->get($c['item_id']);
                $items[] = [
                    'item_id' => $c['item_id'],
                    'nombre' => $c['nombre'] ?? 'Comida',
                    'icono' => $c['icono_sugerido'] ?? '🍽️',
                    'hora' => $c['hora'] ?? null,
                    'status' => $check?->status,
                    'note' => $check?->note,
                    'tipo' => 'comida',
                ];
            }
            if ($supplementsAffect) {
                foreach (PlanData::supplements($extracted) as $s) {
                    $check = $checksByItem->get($s['item_id']);
                    $items[] = [
                        'item_id' => $s['item_id'],
                        'nombre' => $s['nombre'] ?? 'Suplemento',
                        'icono' => '🥤',
                        'hora' => null,
                        'status' => $check?->status,
                        'note' => $check?->note,
                        'tipo' => 'suplemento',
                    ];
                }
            }

            $fidelidad = PlanData::fidelity(
                $extracted,
                $mode,
                $checksByItem->values(),
                $supplementsAffect,
            );

            return response()->json([
                'date' => $key,
                'date_label' => $d->isoFormat('dddd, D [de] MMMM'),
                'mode' => $mode,
                'fidelidad' => $fidelidad,
                'items' => $items,
            ]);
        })->name('day.show');

        // "Mi plan" se unificó con la vista de detalle (plans.showOne). Redirigimos
        // para que cualquier link viejo siga funcionando y haya una sola vista.
        Route::get('/plan', function () {
            $plan = auth()->user()->activeNutritionalPlan;

            return $plan
                ? redirect()->route('plans.showOne', $plan)
                : redirect()->route('onboarding.show');
        })->name('plan.show');

        // "Reemplazar plan": archiva el activo (is_active=false) en vez de borrarlo,
        // así queda en el historial. Luego el onboarding deja subir el nuevo.
        Route::delete('/plan/active', function () {
            auth()->user()->activeNutritionalPlan?->update(['is_active' => false]);
            return redirect()->route('onboarding.show')->with('status', 'Plan archivado. Suba el nuevo; el anterior queda en su historial.');
        })->name('plan.destroy');
    });

    // Historial de planes (fuera de plan.required: se puede consultar aunque no
    // haya un plan activo, ej. justo después de archivar).
    Route::get('/mis-planes', function () {
        $plans = auth()->user()->nutritionalPlans()
            ->orderByDesc('is_active')
            ->orderByDesc('created_at')
            ->get();

        return view('plans-history', compact('plans'));
    })->name('plans.history');

    // Ver un plan específico (read-only). El global scope de BelongsToUser
    // garantiza que sólo se resuelven planes del usuario autenticado.
    Route::get('/mis-planes/{plan}', function (\App\Models\NutritionalPlan $plan) {
        return view('plan', ['plan' => $plan, 'historical' => true]);
    })->name('plans.showOne');

    // Reactivar un plan archivado: pasa a ser el activo y desactiva el anterior.
    Route::post('/mis-planes/{plan}/reactivar', function (\App\Models\NutritionalPlan $plan) {
        \App\Models\NutritionalPlan::where('is_active', true)->update(['is_active' => false]);
        $plan->update(['is_active' => true]);

        return redirect()->route('dashboard')->with('status', 'Plan reactivado. Es tu plan actual.');
    })->name('plans.reactivate');
});

// Panel del nutricionista
Route::middleware(['auth', 'verified', 'nutri'])->prefix('nutri')->name('nutri.')->group(function () {
    Route::get('/', [NutriDashboardController::class, 'index'])->name('dashboard');
    Route::post('/invitar', [InvitationController::class, 'store'])->name('invitations.store');

    Route::get('/pacientes/{patient}', [PatientController::class, 'show'])->name('patients.show');
    Route::post('/pacientes/{patient}/notas', [PatientController::class, 'storeNote'])->name('patients.notes.store');
    Route::delete('/pacientes/{patient}/notas/{note}', [PatientController::class, 'destroyNote'])->name('patients.notes.destroy');

    Route::get('/planes', [PlanController::class, 'index'])->name('plans.index');
    Route::get('/planes/nuevo', [PlanController::class, 'create'])->name('plans.create');
    Route::post('/planes/extraer-pdf', [PlanController::class, 'extractPdf'])->name('plans.extractPdf');
    Route::post('/planes', [PlanController::class, 'store'])->name('plans.store');
    Route::get('/planes/{plan}/editar', [PlanController::class, 'edit'])->name('plans.edit');
    Route::put('/planes/{plan}', [PlanController::class, 'update'])->name('plans.update');
    Route::post('/planes/{plan}/duplicar', [PlanController::class, 'duplicate'])->name('plans.duplicate');
    Route::post('/planes/{plan}/asignar', [PlanController::class, 'assign'])->name('plans.assign');
    Route::delete('/planes/{plan}', [PlanController::class, 'destroy'])->name('plans.destroy');
});

// Aceptación de invitación (público).
Route::get('/aceptar-invitacion/{token}', [InvitationController::class, 'show'])->name('invitation.show');
Route::post('/aceptar-invitacion/{token}', [InvitationController::class, 'accept'])->name('invitation.accept');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
