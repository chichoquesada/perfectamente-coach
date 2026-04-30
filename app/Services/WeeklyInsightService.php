<?php

namespace App\Services;

use App\Models\DailyCheck;
use App\Models\NutritionalPlan;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WeeklyInsightService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) config('services.gemini.key');
        $this->model = (string) config('services.gemini.model', 'gemini-flash-latest');

        if ($this->apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY no configurada');
        }
    }

    /**
     * Genera el insight semanal del usuario actual.
     * Devuelve array: insight_principal, patrones, comidas_problematicas,
     * recomendacion, tono ("celebracion"|"motivacion"|"alerta"), score_promedio.
     */
    public function generate(User $user): array
    {
        $plan = $user->activeNutritionalPlan;
        if (! $plan) {
            throw new RuntimeException('Sin plan activo');
        }

        $context = $this->buildContext($user, $plan);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-goog-api-key' => $this->apiKey,
        ])
            ->withOptions(['verify' => $this->caBundle()])
            ->timeout(60)
            ->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent",
                [
                    'contents' => [[
                        'parts' => [[
                            'text' => $this->prompt($context),
                        ]],
                    ]],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'temperature' => 0.7,
                        'maxOutputTokens' => 4096,
                    ],
                ]
            );

        if (! $response->successful()) {
            throw new RuntimeException("Gemini API error {$response->status()}: " . $response->body());
        }

        $jsonText = (string) $response->json('candidates.0.content.parts.0.text');
        $cleaned = $this->cleanJsonText($jsonText);

        try {
            $data = json_decode($cleaned, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $dumpPath = storage_path('logs/insight_failed_' . now()->format('Ymd_His') . '.json');
            @file_put_contents($dumpPath, $jsonText);
            throw new RuntimeException(
                'Insight JSON malformado: ' . $e->getMessage()
                . ' | raw guardado en ' . basename($dumpPath)
            );
        }

        return $data + ['score_promedio' => $context['score_promedio']];
    }

    /**
     * Limpia salida LLM antes de json_decode:
     * 1. ASCII control chars (byte-level, no falla nunca)
     * 2. Unicode line/paragraph separators U+2028/U+2029 y NBSP U+00A0
     *    que JS tolera dentro de strings pero PHP json_decode rechaza.
     */
    private function cleanJsonText(string $raw): string
    {
        $step1 = preg_replace('/[\x00-\x1F\x7F]/', '', $raw);
        if (! is_string($step1)) {
            $step1 = $raw;
        }
        $step2 = @preg_replace('/[\x{2028}\x{2029}\x{00A0}]/u', ' ', $step1);
        if (is_string($step2)) {
            return $step2;
        }
        return $step1;
    }

    private function buildContext(User $user, NutritionalPlan $plan): array
    {
        $today = now();
        $start = $today->copy()->subDays(6)->toDateString();
        $end = $today->toDateString();

        $checks = DailyCheck::where('nutritional_plan_id', $plan->id)
            ->whereBetween('date', [$start, $end])
            ->get();

        $extracted = $plan->extracted_data ?? [];
        $comidas = $extracted['comidas'] ?? [];
        $totalComidas = count($comidas);
        $diasContados = 7;

        // Score por día y por comida
        $byDay = [];
        $byMeal = [];

        foreach ($checks as $c) {
            $weight = match ($c->status) {
                'fiel' => 1, 'parcial' => 0.5, default => 0,
            };
            $dateKey = $c->date->toDateString();
            $byDay[$dateKey] = ($byDay[$dateKey] ?? 0) + $weight;
            $byMeal[$c->item_id] = $byMeal[$c->item_id] ?? ['fiel' => 0, 'parcial' => 0, 'nofiel' => 0];
            $byMeal[$c->item_id][$c->status]++;
        }

        $scorePromedio = $totalComidas > 0 && $diasContados > 0
            ? (int) round((array_sum($byDay) / ($totalComidas * $diasContados)) * 100)
            : 0;

        return [
            'paciente' => $extracted['paciente']['nombre'] ?? 'Atleta',
            'objetivo' => $extracted['objetivos']['principal'] ?? null,
            'comidas_plan' => array_map(fn ($c) => [
                'id' => $c['id'] ?? null,
                'nombre' => $c['nombre'] ?? null,
                'hora' => $c['hora'] ?? null,
            ], $comidas),
            'checks_por_dia' => $byDay,
            'comidas_marcadas' => $byMeal,
            'total_comidas_plan' => $totalComidas,
            'rango' => "$start a $end",
            'score_promedio' => $scorePromedio,
        ];
    }

    private function prompt(array $context): string
    {
        $ctxJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
Eres el coach mental de PerfectaMENTE Coach, una app que ayuda atletas a ser fieles a su plan nutricional. Tono: directo Hormozi-style, sin azúcar, en "usted" (no "tú"). Toque atleta. NUNCA emojis en headers. Honestidad antes que motivación vacía.

Te paso el contexto de la última semana de un usuario:

$ctxJson

Devuelva un análisis JSON con esta estructura EXACTA:

{
  "insight_principal": "1 frase poderosa, máximo 90 caracteres, que resume la semana. Ejemplos del tono correcto: 'La cena es donde se le escapa la fidelidad. No hoy.' / 'Fidelidad de campeón. Sostenga el ritmo.' / 'Tres días sin marcar. Vuelva al tablero hoy.'",
  "patrones_detectados": ["array de 1-3 patrones concretos basados en datos. Ejemplos: 'Falla 60% en cenas (vs 90% fidelidad en desayunos)', 'Solo marcó checks 3 de 7 días', 'Mejor desempeño en días entre semana'"],
  "comidas_problematicas": ["array con item_id de las comidas con menor fidelidad, máximo 2"],
  "recomendacion": "1 acción concreta para esta semana, máximo 120 caracteres. Hormozi style. Ejemplo: 'Ponga la cena lista a las 6pm. La hora del primer combate.'",
  "tono": "uno de: celebracion (fidelidad ≥80%), motivacion (50-79%), alerta (<50% o pocos checks)"
}

REGLAS DURAS:
- En español de Costa Rica, "usted" siempre.
- Sin em dashes (—) en copy formal.
- Sin emojis.
- Si el usuario tiene 0 checks la semana entera, el tono es "alerta" y la recomendación es ir a marcar HOY.
- Datos primero, motivación después. NUNCA invente patrones que no estén en los datos.
- Devuelva SOLO el JSON. Sin markdown, sin explicaciones.
PROMPT;
    }

    private function caBundle(): string|bool
    {
        $local = storage_path('certs/cacert.pem');
        return is_file($local) ? $local : true;
    }
}
