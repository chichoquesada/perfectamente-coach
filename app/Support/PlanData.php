<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Punto único de verdad para derivar del plan (extracted_data) las comidas y
 * suplementos del día, sus IDs estables, y el % de fidelidad. Antes esta lógica
 * estaba duplicada en el dashboard, en /api/day y en CheckController, lo que
 * provocaba cálculos inconsistentes (ej: el total de comidas ignoraba el modo).
 */
class PlanData
{
    /**
     * Comidas aplicables al modo del día (base + entreno/competencia).
     * Cada comida lleva un 'item_id' estable.
     */
    public static function meals(array $extracted, string $mode): array
    {
        $base = $extracted['comidas'] ?? [];
        $extra = match ($mode) {
            'entreno' => $extracted['comidas_entreno'] ?? [],
            'competencia' => $extracted['comidas_competencia'] ?? [],
            default => [],
        };

        $out = [];
        foreach (array_merge($base, $extra) as $idx => $c) {
            $c['item_id'] = $c['id'] ?? Str::slug($c['nombre'] ?? 'comida-'.$idx);
            $out[] = $c;
        }

        return $out;
    }

    /**
     * Suplementos normalizados con 'item_id' estable (prefijo sup-). Soporta el
     * formato estructurado ($extracted['suplementos']) y el legacy de lista
     * plana ($extracted['suplementos_diarios']).
     */
    public static function supplements(array $extracted): array
    {
        $structured = $extracted['suplementos'] ?? [];
        if (! empty($structured)) {
            $out = [];
            foreach ($structured as $idx => $s) {
                $nombre = $s['nombre'] ?? '';
                $s['item_id'] = $s['id'] ?? ('sup-'.Str::slug($nombre !== '' ? $nombre : 'item-'.$idx));
                $out[] = $s;
            }

            return $out;
        }

        // Legacy: lista plana de strings en suplementos_diarios.
        $out = [];
        foreach ($extracted['suplementos_diarios'] ?? [] as $idx => $raw) {
            $nombre = is_array($raw) ? ($raw['nombre'] ?? '') : (string) $raw;
            $nombre = trim($nombre);
            if ($nombre === '') {
                continue;
            }
            $out[] = [
                'item_id' => 'sup-'.Str::slug($nombre).'-'.substr(md5($nombre), 0, 4),
                'nombre' => $nombre,
                'dosis' => null,
                'frecuencia' => null,
                'nota' => null,
            ];
        }

        return $out;
    }

    /**
     * Farmacología (estructurada). Nunca cuenta para la fidelidad.
     */
    public static function pharma(array $extracted): array
    {
        return $extracted['farmacologia'] ?? [];
    }

    /**
     * % de fidelidad del día. fiel=1, parcial=0.5, nofiel/sin marcar=0.
     *
     * Las comidas siempre cuentan. Los suplementos cuentan sólo si
     * $supplementsCount (preferencia del usuario). La farmacología nunca cuenta.
     *
     * @param  Collection|iterable  $checks  Checks del día (cada uno con ->item_id y ->status)
     */
    public static function fidelity(array $extracted, string $mode, $checks, bool $supplementsCount): int
    {
        $checks = $checks instanceof Collection ? $checks : collect($checks);

        $mealIds = collect(self::meals($extracted, $mode))->pluck('item_id');
        $total = $mealIds->count();

        $countableIds = $mealIds;
        if ($supplementsCount) {
            $supIds = collect(self::supplements($extracted))->pluck('item_id');
            $total += $supIds->count();
            $countableIds = $countableIds->merge($supIds);
        }

        if ($total === 0) {
            return 0;
        }

        $idSet = $countableIds->flip();
        $score = $checks
            ->filter(fn ($c) => $idSet->has($c->item_id))
            ->sum(fn ($c) => match ($c->status) {
                'fiel' => 1,
                'parcial' => 0.5,
                default => 0,
            });

        return (int) round(($score / $total) * 100);
    }
}
