<?php

namespace App\Support;

use App\Models\DailyCheck;
use App\Models\DailyMode;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Punto único de verdad de la gamificación (Tanda B).
 *
 * - Racha "con día de gracia": un día por debajo del umbral del usuario NO rompe
 *   la racha; dos días seguidos por debajo SÍ la rompen. El umbral lo elige cada
 *   paciente (users.streak_threshold).
 * - Catálogo de medallas + detección. El estado desbloqueado se persiste en
 *   user_achievements (una vez ganada, permanente).
 *
 * Todo se calcula sobre una ventana fija (no el rango del heatmap) para que la
 * racha y las medallas sean estables aunque el usuario cambie la vista 7/30/90.
 */
class Gamification
{
    /** Días hacia atrás que miramos para racha + detección de medallas. */
    public const WINDOW_DAYS = 120;

    /** Zona horaria del usuario (CR no tiene DST → offset fijo). */
    public const TZ = 'America/Costa_Rica';

    /**
     * Catálogo de medallas: key => [emoji, título, cómo se gana].
     */
    public static function medals(): array
    {
        return [
            'semana_perfecta' => ['🔥', 'Semana perfecta', '7 días seguidos cumpliendo tu meta.'],
            'racha_30' => ['🏆', 'Racha de 30', 'Una racha de 30 días.'],
            'mes_consistente' => ['📈', 'Mes consistente', 'Promedio de 80%+ en 30 días.'],
            'madrugador' => ['🌅', 'Madrugador', 'Tu primera comida antes de las 9am, 5 días.'],
        ];
    }

    /**
     * Evalúa toda la gamificación del usuario para su plan activo. Detecta y
     * PERSISTE medallas nuevas. Devuelve todo lo que el dashboard necesita.
     *
     * @return array{
     *   threshold:int, racha_actual:int, racha_max:int,
     *   unlocked: array<string,\Illuminate\Support\Carbon>,
     *   newly: array<int,string>
     * }
     */
    public static function evaluate(User $user, array $extracted, bool $supplementsAffect): array
    {
        $threshold = self::threshold($user);

        $series = self::dailySeries($extracted, $supplementsAffect);

        $rachaActual = self::currentStreak($series, $threshold);
        $rachaMax = self::longestStreak($series, $threshold);

        // --- Condiciones de cada medalla ---
        $strictBest = self::longestStrictRun($series, $threshold);

        // Mes consistente: promedio de los últimos 30 días CON data >= 80,
        // exigiendo al menos 20 días registrados (que sea de verdad "el mes").
        $last30 = array_slice($series, -30);
        $withData = array_filter($last30, fn ($d) => $d['fidelidad'] !== null);
        $diasConData30 = count($withData);
        $avg30 = $diasConData30 > 0
            ? array_sum(array_map(fn ($d) => $d['fidelidad'], $withData)) / $diasConData30
            : 0;

        $madrugadorDays = self::madrugadorDays();

        $conditions = [
            'semana_perfecta' => $strictBest >= 7,
            'racha_30' => $rachaMax >= 30,
            'mes_consistente' => $diasConData30 >= 20 && $avg30 >= 80,
            'madrugador' => $madrugadorDays >= 5,
        ];

        // --- Persistir medallas recién desbloqueadas ---
        $unlocked = $user->userAchievements()->get()->keyBy('key')
            ->map(fn ($a) => $a->unlocked_at);

        $newly = [];
        foreach ($conditions as $key => $met) {
            if ($met && ! $unlocked->has($key)) {
                $row = $user->userAchievements()->create([
                    'key' => $key,
                    'unlocked_at' => now(),
                ]);
                $unlocked[$key] = $row->unlocked_at;
                $newly[] = $key;
            }
        }

        return [
            'threshold' => $threshold,
            'racha_actual' => $rachaActual,
            'racha_max' => $rachaMax,
            'unlocked' => $unlocked->all(),
            'newly' => $newly,
        ];
    }

    /** Umbral del usuario, acotado a un rango razonable. */
    public static function threshold(User $user): int
    {
        return max(40, min(100, (int) ($user->streak_threshold ?? 60)));
    }

    /**
     * Serie diaria (oldest -> newest) de la ventana: cada día con su % de
     * fidelidad (null si no hubo data) y si es hoy. Auto-scopeada al usuario
     * autenticado por el global scope de DailyCheck/DailyMode.
     *
     * @return array<int,array{fidelidad:int|null,is_today:bool}>
     */
    private static function dailySeries(array $extracted, bool $supplementsAffect): array
    {
        $today = now()->toDateString();
        $start = now()->subDays(self::WINDOW_DAYS - 1)->toDateString();

        $modesByDate = DailyMode::whereBetween('date', [$start, $today])
            ->pluck('mode', 'date')->toArray();
        $checksByDate = DailyCheck::whereBetween('date', [$start, $today])
            ->get()->groupBy(fn ($c) => $c->date->toDateString());

        $series = [];
        for ($i = self::WINDOW_DAYS - 1; $i >= 0; $i--) {
            $key = now()->subDays($i)->toDateString();
            $mode = $modesByDate[$key] ?? 'descanso';
            $dayChecks = $checksByDate->get($key, collect());

            // Día con data = al menos un check que cuenta (comida, o suplemento si
            // la preferencia está activa). Farma nunca cuenta.
            $hasData = $dayChecks->contains(function ($c) use ($supplementsAffect) {
                if (str_starts_with($c->item_id, 'farm-')) {
                    return false;
                }
                if (str_starts_with($c->item_id, 'sup-')) {
                    return $supplementsAffect;
                }

                return true;
            });

            $series[] = [
                'fidelidad' => $hasData
                    ? PlanData::fidelity($extracted, $mode, $dayChecks, $supplementsAffect)
                    : null,
                'is_today' => $key === $today,
            ];
        }

        return $series;
    }

    /**
     * Racha actual contando hacia atrás desde hoy, con día de gracia.
     * - Hoy: si ya cumple, suma; si no, queda "pendiente" (no penaliza, el día no
     *   terminó).
     * - Días cerrados: cumplir suma; un día por debajo es gracia (no suma, no
     *   rompe); dos días por debajo seguidos rompen.
     */
    public static function currentStreak(array $series, int $threshold): int
    {
        $n = count($series);
        if ($n === 0) {
            return 0;
        }

        $streak = 0;
        $i = $n - 1;

        // Hoy se trata aparte: sólo suma si ya cumplió; nunca consume gracia.
        if ($series[$i]['is_today']) {
            if (self::qualifies($series[$i], $threshold)) {
                $streak++;
            }
            $i--;
        }

        $consecutiveFails = 0;
        for (; $i >= 0; $i--) {
            if (self::qualifies($series[$i], $threshold)) {
                $streak++;
                $consecutiveFails = 0;
            } else {
                $consecutiveFails++;
                if ($consecutiveFails >= 2) {
                    break; // dos fallos seguidos: la racha terminó acá
                }
                // un solo fallo = día de gracia: la racha sigue viva pero no suma
            }
        }

        return $streak;
    }

    /**
     * Racha más larga de la ventana, con la misma regla de día de gracia.
     */
    public static function longestStreak(array $series, int $threshold): int
    {
        $longest = 0;
        $run = 0;
        $fails = 0;

        foreach ($series as $day) {
            if (self::qualifies($day, $threshold)) {
                $run++;
                $fails = 0;
                $longest = max($longest, $run);
            } else {
                $fails++;
                if ($fails >= 2) {
                    $run = 0; // se rompió; el siguiente día que cumpla arranca de cero
                }
                // un fallo aislado: gracia, el run se mantiene (no suma)
            }
        }

        return $longest;
    }

    /**
     * Corrida más larga ESTRICTA (sin gracia): N días consecutivos cumpliendo.
     * Para "Semana perfecta", que sí exige 7 seguidos sin perdón.
     */
    private static function longestStrictRun(array $series, int $threshold): int
    {
        $longest = 0;
        $run = 0;
        foreach ($series as $day) {
            if (self::qualifies($day, $threshold)) {
                $run++;
                $longest = max($longest, $run);
            } else {
                $run = 0;
            }
        }

        return $longest;
    }

    /** Un día "cumple" si tiene data y su fidelidad alcanza el umbral. */
    private static function qualifies(array $day, int $threshold): bool
    {
        return $day['fidelidad'] !== null && $day['fidelidad'] >= $threshold;
    }

    /**
     * Cuántos días (en la ventana) la PRIMERA comida marcada fue antes de las 9am
     * (hora de Costa Rica). Usa created_at del check; sólo comidas (no sup/farma).
     */
    private static function madrugadorDays(): int
    {
        $start = now()->subDays(self::WINDOW_DAYS - 1)->toDateString();
        $today = now()->toDateString();

        $checks = DailyCheck::whereBetween('date', [$start, $today])
            ->whereNotNull('created_at')
            ->get()
            ->filter(fn ($c) => ! str_starts_with($c->item_id, 'sup-')
                && ! str_starts_with($c->item_id, 'farm-'));

        $byDate = $checks->groupBy(fn ($c) => $c->date->toDateString());

        $days = 0;
        foreach ($byDate as $dayChecks) {
            /** @var Collection $dayChecks */
            $earliest = $dayChecks
                ->map(fn ($c) => Carbon::parse($c->created_at)->setTimezone(self::TZ))
                ->min();
            if ($earliest && $earliest->hour < 9) {
                $days++;
            }
        }

        return $days;
    }
}
