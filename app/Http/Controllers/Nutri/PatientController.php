<?php

namespace App\Http\Controllers\Nutri;

use App\Http\Controllers\Controller;
use App\Models\DailyCheck;
use App\Models\DailyMode;
use App\Models\NutritionistPatientNote;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function show(User $patient): View
    {
        $nutri = Auth::user();

        $pivot = $nutri->patients()->where('users.id', $patient->id)->first()?->pivot;

        if (! $pivot) {
            abort(404);
        }

        $plan = $patient->activeNutritionalPlan;
        $extracted = $plan?->extracted_data ?? [];
        $comidasBase = $extracted['comidas'] ?? [];

        $today = now()->toDateString();
        $startDate = now()->subDays(29)->toDateString();

        $heatmap = [];
        $stats = ['promedio' => 0, 'dias_perfectos' => 0, 'racha_actual' => 0, 'racha_max' => 0, 'dias_con_data' => 0];

        if ($plan) {
            $modesByDate = DailyMode::where('user_id', $patient->id)
                ->whereBetween('date', [$startDate, $today])
                ->pluck('mode', 'date')->toArray();

            $checksByDate = DailyCheck::where('user_id', $patient->id)
                ->whereBetween('date', [$startDate, $today])
                ->get()->groupBy(fn ($c) => $c->date->toDateString());

            for ($i = 29; $i >= 0; $i--) {
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
                $f = ($totalDia > 0 && $checksDia->count() > 0)
                    ? (int) round(($scoreDia / $totalDia) * 100)
                    : null;

                $heatmap[] = [
                    'date' => $key,
                    'day' => $d->day,
                    'fidelidad' => $f,
                    'is_today' => $key === $today,
                    'dow' => (int) $d->dayOfWeekIso,
                ];
            }

            $rachaActual = 0; $rachaMax = 0; $rachaTmp = 0; $sum = 0; $diasConData = 0;
            foreach ($heatmap as $cell) {
                $f = $cell['fidelidad'];
                if ($f === null) { $rachaTmp = 0; continue; }
                $diasConData++;
                $sum += $f;
                if ($f === 100) $stats['dias_perfectos']++;
                if ($f >= 67) { $rachaTmp++; $rachaMax = max($rachaMax, $rachaTmp); } else { $rachaTmp = 0; }
            }
            for ($i = count($heatmap) - 1; $i >= 0; $i--) {
                $f = $heatmap[$i]['fidelidad'];
                if ($f === null || $f < 67) break;
                $rachaActual++;
            }
            $stats['promedio'] = $diasConData > 0 ? (int) round($sum / $diasConData) : 0;
            $stats['racha_actual'] = $rachaActual;
            $stats['racha_max'] = $rachaMax;
            $stats['dias_con_data'] = $diasConData;
        }

        $notes = NutritionistPatientNote::where('nutritionist_id', $nutri->id)
            ->where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->get();

        return view('nutri.patients.show', compact('patient', 'pivot', 'plan', 'heatmap', 'stats', 'notes'));
    }

    public function storeNote(Request $request, User $patient): RedirectResponse
    {
        $nutri = Auth::user();

        if (! $nutri->patients()->where('users.id', $patient->id)->exists()) {
            abort(404);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        NutritionistPatientNote::create([
            'nutritionist_id' => $nutri->id,
            'patient_id' => $patient->id,
            'body' => $data['body'],
        ]);

        return back()->with('status', 'Nota guardada.');
    }

    public function destroyNote(User $patient, NutritionistPatientNote $note): RedirectResponse
    {
        $nutri = Auth::user();

        if ($note->nutritionist_id !== $nutri->id || $note->patient_id !== $patient->id) {
            abort(404);
        }

        $note->delete();

        return back()->with('status', 'Nota eliminada.');
    }
}
