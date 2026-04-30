<?php

use App\Http\Controllers\CheckController;
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

            $checksToday = $plan
                ? \App\Models\DailyCheck::where('date', $today)->pluck('status', 'item_id')->toArray()
                : [];

            $totalComidas = count($comidas);
            $score = collect($checksToday)->sum(fn ($s) => match ($s) {
                'fiel' => 1,
                'parcial' => 0.5,
                default => 0,
            });
            $fidelidad = $totalComidas > 0 ? (int) round(($score / $totalComidas) * 100) : 0;

            return view('dashboard', compact('plan', 'comidas', 'checksToday', 'fidelidad', 'mode'));
        })->name('dashboard');

        Route::post('/api/checks', [CheckController::class, 'store'])->name('checks.store');
        Route::post('/api/mode', [ModeController::class, 'store'])->name('mode.store');

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
