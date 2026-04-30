<?php

namespace App\Http\Controllers;

use App\Models\DailyCheck;
use App\Models\NutritionalPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'item_id' => ['required', 'string', 'max:80'],
            'status'  => ['nullable', 'in:fiel,parcial,nofiel'],
            'note'    => ['nullable', 'string', 'max:500'],
        ]);

        $plan = Auth::user()->activeNutritionalPlan;

        if (! $plan) {
            return response()->json(['error' => 'No hay plan activo'], 422);
        }

        $today = now()->toDateString();

        // status vacío => quitar el check (volver al estado neutro)
        if (empty($data['status'])) {
            DailyCheck::where('date', $today)
                ->where('item_id', $data['item_id'])
                ->delete();

            return response()->json([
                'status' => null,
                'fidelidad' => $this->fidelidadHoy($plan, $today),
            ]);
        }

        $check = DailyCheck::updateOrCreate(
            ['date' => $today, 'item_id' => $data['item_id']],
            [
                'nutritional_plan_id' => $plan->id,
                'status' => $data['status'],
                'note' => $data['note'] ?? null,
                'mode' => 'descanso',
            ]
        );

        return response()->json([
            'status' => $check->status,
            'fidelidad' => $this->fidelidadHoy($plan, $today),
        ]);
    }

    private function fidelidadHoy(NutritionalPlan $plan, string $date): int
    {
        $totalComidas = count($plan->extracted_data['comidas'] ?? []);

        if ($totalComidas === 0) {
            return 0;
        }

        $score = DailyCheck::where('date', $date)->get()->sum(
            fn ($c) => match ($c->status) {
                'fiel' => 1,
                'parcial' => 0.5,
                default => 0,
            }
        );

        return (int) round(($score / $totalComidas) * 100);
    }
}
