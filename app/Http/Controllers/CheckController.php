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
            'only_note' => ['nullable', 'boolean'],
        ]);

        $plan = Auth::user()->activeNutritionalPlan;

        if (! $plan) {
            return response()->json(['error' => 'No hay plan activo'], 422);
        }

        $today = now()->toDateString();
        $onlyNote = $data['only_note'] ?? false;

        // Guardar SOLO la nota, sin tocar el status existente
        if ($onlyNote) {
            $existing = DailyCheck::where('date', $today)
                ->where('item_id', $data['item_id'])
                ->first();

            if ($existing) {
                $existing->update(['note' => $data['note'] ?? null]);
            } else {
                // Crear placeholder con status null no es válido por enum.
                // Si no hay check todavía, se guarda nota cuando el usuario marca status.
                // Pero queremos permitir nota sin status: usamos status='nofiel' silente?
                // Mejor: rechazar si no hay status previo, instruir a marcar primero.
                return response()->json([
                    'error' => 'Marque la comida primero antes de agregar nota.',
                ], 422);
            }

            return response()->json([
                'status' => $existing->status,
                'note' => $existing->note,
                'fidelidad' => $this->fidelidadHoy($plan, $today),
            ]);
        }

        // status vacío => quitar el check (volver al estado neutro)
        if (empty($data['status'])) {
            DailyCheck::where('date', $today)
                ->where('item_id', $data['item_id'])
                ->delete();

            return response()->json([
                'status' => null,
                'note' => null,
                'fidelidad' => $this->fidelidadHoy($plan, $today),
            ]);
        }

        $attrs = [
            'nutritional_plan_id' => $plan->id,
            'status' => $data['status'],
            'mode' => 'descanso',
        ];
        // Solo sobrescribir nota si se envió explícitamente (puede ser '' para borrar)
        if ($request->has('note')) {
            $attrs['note'] = $data['note'] ?? null;
        }

        $check = DailyCheck::updateOrCreate(
            ['date' => $today, 'item_id' => $data['item_id']],
            $attrs,
        );

        return response()->json([
            'status' => $check->status,
            'note' => $check->note,
            'fidelidad' => $this->fidelidadHoy($plan, $today),
        ]);
    }

    private function fidelidadHoy(NutritionalPlan $plan, string $date): int
    {
        $totalComidas = count($plan->extracted_data['comidas'] ?? []);

        if ($totalComidas === 0) {
            return 0;
        }

        // Excluir checks de suplementos/farmacología (prefijo sup-/farm-): no
        // cuentan para la fidelidad de comidas.
        $score = DailyCheck::where('date', $date)->get()
            ->filter(fn ($c) => ! str_starts_with($c->item_id, 'sup-') && ! str_starts_with($c->item_id, 'farm-'))
            ->sum(fn ($c) => match ($c->status) {
                'fiel' => 1,
                'parcial' => 0.5,
                default => 0,
            });

        return (int) round(($score / $totalComidas) * 100);
    }
}
