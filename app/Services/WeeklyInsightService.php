<?php

namespace App\Services;

use App\Models\DailyCheck;
use App\Models\NutritionalPlan;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

        $response = $this->callGeminiWithFallback($this->prompt($context));

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

        // Respetar la preferencia del usuario: si los suplementos cuentan en su
        // fidelidad, también entran al análisis (mismo criterio que PlanData).
        // La farmacología NUNCA cuenta. 'na' se ignora.
        $supplementsCount = (bool) $user->supplements_affect_fidelity;

        $comidas = $extracted['comidas'] ?? [];
        $comidaNames = [];
        foreach ($comidas as $idx => $c) {
            $id = $c['id'] ?? \Illuminate\Support\Str::slug($c['nombre'] ?? 'comida-'.$idx);
            $comidaNames[$id] = $c['nombre'] ?? null;
        }

        $suplementos = $supplementsCount ? \App\Support\PlanData::supplements($extracted) : [];
        $supNames = [];
        foreach ($suplementos as $s) {
            $supNames[$s['item_id']] = $s['nombre'] ?? null;
        }

        // Conjunto contable: comidas + (suplementos si el toggle está ON).
        $countableIds = array_merge(array_keys($comidaNames), array_keys($supNames));
        $countablePorDia = count($countableIds);
        $diasContados = 7;

        // Score por día y por ítem (solo ítems contables; excluye farma y 'na').
        $byDay = [];
        $byMeal = [];

        foreach ($checks as $c) {
            if ($c->status === 'na') {
                continue;
            }
            if (! in_array($c->item_id, $countableIds, true)) {
                continue;
            }
            $weight = match ($c->status) {
                'fiel' => 1, 'parcial' => 0.5, default => 0,
            };
            $dateKey = $c->date->toDateString();
            $byDay[$dateKey] = ($byDay[$dateKey] ?? 0) + $weight;
            $byMeal[$c->item_id] = $byMeal[$c->item_id] ?? ['fiel' => 0, 'parcial' => 0, 'nofiel' => 0];
            $byMeal[$c->item_id][$c->status]++;
        }

        $scorePromedio = $countablePorDia > 0 && $diasContados > 0
            ? (int) round((array_sum($byDay) / ($countablePorDia * $diasContados)) * 100)
            : 0;

        $context = [
            'paciente' => $extracted['paciente']['nombre'] ?? 'Atleta',
            'objetivo' => $extracted['objetivos']['principal'] ?? null,
            'incluye_suplementos' => $supplementsCount,
            'comidas_plan' => array_map(fn ($c) => [
                'id' => $c['id'] ?? null,
                'nombre' => $c['nombre'] ?? null,
                'hora' => $c['hora'] ?? null,
            ], $comidas),
            'checks_por_dia' => $byDay,
            'comidas_marcadas' => $byMeal,
            'total_items_contables' => $countablePorDia,
            'rango' => "$start a $end",
            'score_promedio' => $scorePromedio,
        ];

        // Solo incluimos los suplementos en el contexto si cuentan (toggle ON),
        // para que la IA pueda comentarlos como parte de la adherencia.
        if ($supplementsCount && count($supNames) > 0) {
            $context['suplementos_plan'] = array_map(
                fn ($id, $nombre) => ['id' => $id, 'nombre' => $nombre],
                array_keys($supNames),
                array_values($supNames)
            );
        }

        return $context;
    }

    private function prompt(array $context): string
    {
        $ctxJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
Eres el coach motivacional de PerfectaMENTE Coach, una app que ayuda atletas y personas comunes a ser fieles a su plan nutricional. Su trabajo es **acompañar y motivar**, NO juzgar ni rematar.

Tono obligatorio:
- **Empático**: reconozca el esfuerzo antes que el fallo. Si solo marcó 2 días, eso es un comienzo, no un fracaso.
- **Motivador desde la posibilidad**: enfocado en lo que pueden hacer hoy, no en lo que fallaron ayer.
- **Cálido y humano**: con calor, esperanza, espiritualidad ocasional sutil cuando aplica (Chicho es atleta cristiano).
- **Constructivo**: cada observación trae una idea hopeful de qué hacer ahora.
- En **"usted"** siempre, español Costa Rica.
- Sin emojis en headers. Sin em dashes (—). Sin floreos vacíos tipo "¡tú puedes!".

Frases del estilo correcto:
- "Cada check cuenta. Empiece con la próxima comida."
- "Las cenas son su zona de crecimiento. Su versión campeona ya está en la cocina."
- "Su mañana habla bien de su disciplina. Llevemos esa energía a la tarde."
- "Tres días sin marcar. La fidelidad vuelve hoy con un solo check."
- "Buen ritmo esta semana. Sostenga lo que ya está funcionando."

Frases que NO debe usar (confrontativas, rematan en vez de ayudar):
- ❌ "Usted no puede gestionar lo que no mide."
- ❌ "Entrega la guerra en la cena. Así no bajará grasa."
- ❌ "Su falta de registro es su mayor freno."
- ❌ "Domine su tarde."

Contexto de la última semana del usuario:

$ctxJson

Devuelva un análisis JSON con esta estructura EXACTA:

{
  "insight_principal": "1 frase cálida y específica de la semana, máximo 100 caracteres. Reconoce primero, motiva después. Ejemplos: 'Tiene una mañana sólida. Ahora invitemos a la tarde a esa misma fidelidad.' / 'Buena fidelidad esta semana. Sostenga ese ritmo.' / 'Comienzo lento, está bien. Su próximo check abre la semana siguiente.'",
  "patrones_detectados": ["array de 1-3 observaciones basadas en datos, dichas con calidez. Ejemplos: 'Sus desayunos son su fortaleza esta semana', 'Las cenas tienen espacio para crecer', 'Marcó checks en 3 de 7 días, un buen punto de partida'"],
  "comidas_problematicas": ["array con item_id de las comidas con menor fidelidad (mostradas como zona de crecimiento, no como falla), máximo 2"],
  "recomendacion": "1 acción concreta y hopeful para esta semana, máximo 130 caracteres. Ejemplo: 'Deje la cena lista temprano. Una decisión menos a la hora del hambre.' / 'Comience mañana con un check antes de las 9am. Una victoria temprana.'",
  "tono": "uno de: celebracion (fidelidad ≥75%), motivacion (40-74%), apoyo (<40% o pocos checks)"
}

REGLAS DURAS:
- Si "incluye_suplementos" es true, considere también la adherencia a los suplementos (suplementos_plan) como parte del análisis, igual que las comidas. La farmacología NUNCA se analiza.
- Reconozca lo que el usuario hizo bien antes de mencionar lo que falta. Siempre.
- Si el usuario tiene 0 checks la semana entera: tono "apoyo", insight cálido tipo "Cada gran historia tiene un primer capítulo. El suyo empieza con un check.", recomendación que invite sin presionar.
- NUNCA invente patrones que no estén en los datos.
- "comidas problemáticas" es solo el nombre técnico del campo: NO use esa palabra hacia el usuario, hable de "zonas de crecimiento" o "espacio para mejorar".
- Devuelva SOLO el JSON. Sin markdown, sin explicaciones.
PROMPT;
    }

    /**
     * Modelos a intentar, en orden (configurado primero). Mismo patrón que
     * GeminiExtractorService: si Google reporta sobrecarga (503/429/500) en uno,
     * caemos al siguiente. Se deduplican.
     *
     * @return list<string>
     */
    private function modelCandidates(): array
    {
        return array_values(array_unique([
            $this->model,
            'gemini-2.5-flash',
            'gemini-2.0-flash',
            'gemini-flash-latest',
        ]));
    }

    /**
     * Llama a Gemini probando cada modelo candidato, con hasta 3 reintentos por
     * modelo y backoff (2s/4s) ante 503/429/500 (servicio saturado). Errores
     * definitivos (400/401/404…) cortan de inmediato.
     *
     * @return \Illuminate\Http\Client\Response
     */
    private function callGeminiWithFallback(string $promptText)
    {
        $payload = [
            'contents' => [[
                'parts' => [[
                    'text' => $promptText,
                ]],
            ]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature' => 0.7,
                'maxOutputTokens' => 4096,
            ],
        ];

        $lastStatus = 0;
        $lastBody = '';

        foreach ($this->modelCandidates() as $model) {
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'X-goog-api-key' => $this->apiKey,
                ])
                    ->withOptions(['verify' => $this->caBundle()])
                    ->timeout(60)
                    ->post(
                        "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                        $payload
                    );

                if ($response->successful()) {
                    return $response;
                }

                $lastStatus = $response->status();
                $lastBody = $response->body();

                if (! in_array($lastStatus, [429, 500, 503], true)) {
                    // Error definitivo: no insistir.
                    throw new RuntimeException("Gemini API error {$lastStatus}: {$lastBody}");
                }

                Log::warning('Insight: Gemini sobrecargado, reintentando', [
                    'model' => $model,
                    'attempt' => $attempt,
                    'status' => $lastStatus,
                ]);

                if ($attempt < 3) {
                    sleep(2 * $attempt); // 2s, luego 4s
                }
            }
            // Este modelo sigue caído: probamos el siguiente.
        }

        throw new RuntimeException(
            "Gemini API error {$lastStatus} (todos los modelos sobrecargados): {$lastBody}"
        );
    }

    private function caBundle(): string|bool
    {
        $local = storage_path('certs/cacert.pem');
        return is_file($local) ? $local : true;
    }
}
