<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GeminiExtractorService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) config('services.gemini.key');
        $this->model = (string) config('services.gemini.model', 'gemini-flash-latest');

        if ($this->apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY no configurada en .env');
        }
    }

    /**
     * Modelos a intentar, en orden. El configurado primero; si Google lo
     * reporta sobrecargado (503/429), caemos al siguiente. Se deduplican
     * por si el configurado ya está en la lista de fallbacks.
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
     * Lee un PDF (path absoluto) y devuelve el plan extraído como array.
     *
     * @throws RuntimeException si la API falla o el JSON está malformado.
     */
    public function extractPlanFromPdf(string $absolutePdfPath): array
    {
        if (! is_file($absolutePdfPath)) {
            throw new RuntimeException("PDF no encontrado: {$absolutePdfPath}");
        }

        $pdfBase64 = base64_encode((string) file_get_contents($absolutePdfPath));

        $response = $this->callGeminiWithFallback($pdfBase64);

        $jsonText = $response->json('candidates.0.content.parts.0.text');
        $finishReason = $response->json('candidates.0.finishReason');

        if (! is_string($jsonText) || $jsonText === '') {
            throw new RuntimeException(
                'Gemini devolvió respuesta vacía (finishReason: '
                . ($finishReason ?? 'desconocido') . '): ' . $response->body()
            );
        }

        // Limpieza en 2 pasos: ASCII control chars (byte-level) +
        // Unicode line/paragraph separators U+2028/U+2029 y NBSP U+00A0
        // que PHP json_decode rechaza dentro de strings.
        $step1 = preg_replace('/[\x00-\x1F\x7F]/', '', $jsonText);
        if (! is_string($step1)) {
            $step1 = $jsonText;
        }
        $step2 = @preg_replace('/[\x{2028}\x{2029}\x{00A0}]/u', ' ', $step1);
        $cleaned = is_string($step2) ? $step2 : $step1;

        // Intento 1: parseo directo.
        $decoded = json_decode($cleaned, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Intento 2: si Gemini truncó la salida (MAX_TOKENS), el JSON queda
        // incompleto. Intentamos repararlo cerrando lo que quedó abierto.
        $repaired = $this->repairTruncatedJson($cleaned);
        if ($repaired !== null) {
            $decoded = json_decode($repaired, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Falló todo: persistimos el raw y lanzamos un error con diagnóstico real.
        $dumpPath = storage_path('logs/gemini_failed_' . now()->format('Ymd_His') . '.json');
        @file_put_contents($dumpPath, $jsonText);

        $diag = $finishReason === 'MAX_TOKENS'
            ? 'la respuesta se truncó por longitud (finishReason: MAX_TOKENS)'
            : 'JSON malformado: ' . json_last_error_msg() . ' (finishReason: ' . ($finishReason ?? 'STOP') . ')';

        throw new RuntimeException(
            'Gemini devolvió ' . $diag
            . ' | raw guardado en ' . basename($dumpPath)
            . ' | preview: ' . substr($cleaned, 0, 300)
        );
    }

    /**
     * Llama a Gemini probando cada modelo candidato en orden, con reintentos
     * y backoff ante sobrecarga del servicio (503 UNAVAILABLE / 429). Devuelve
     * la primera respuesta exitosa. Errores no transitorios (400, 401, 404…)
     * cortan de inmediato sin reintentar.
     *
     * @return \Illuminate\Http\Client\Response
     */
    private function callGeminiWithFallback(string $pdfBase64)
    {
        $payload = [
            'contents' => [[
                'parts' => [
                    ['inline_data' => [
                        'mime_type' => 'application/pdf',
                        'data' => $pdfBase64,
                    ]],
                    ['text' => $this->prompt()],
                ],
            ]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature' => 0.2,
                // gemini-flash-latest admite hasta 65536 tokens de salida.
                // Con 8192 los planes grandes (muchas opciones de comida) se
                // truncaban a mitad de string → JSON inválido.
                'maxOutputTokens' => 65536,
            ],
        ];

        $lastStatus = 0;
        $lastBody = '';

        foreach ($this->modelCandidates() as $model) {
            // Hasta 3 intentos por modelo ante 503/429, con backoff 2s/4s.
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'X-goog-api-key' => $this->apiKey,
                ])
                    ->withOptions(['verify' => $this->caBundle()])
                    ->timeout(120)
                    ->post(
                        "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                        $payload
                    );

                if ($response->successful()) {
                    return $response;
                }

                $lastStatus = $response->status();
                $lastBody = $response->body();

                $transient = in_array($lastStatus, [429, 500, 503], true);

                if (! $transient) {
                    // Error definitivo (400/401/404…): no insistir.
                    throw new RuntimeException("Gemini API error {$lastStatus}: {$lastBody}");
                }

                Log::warning('Gemini sobrecargado, reintentando', [
                    'model' => $model,
                    'attempt' => $attempt,
                    'status' => $lastStatus,
                ]);

                if ($attempt < 3) {
                    sleep(2 * $attempt); // 2s, luego 4s
                }
            }
            // Este modelo sigue caído tras 3 intentos: probamos el siguiente.
        }

        throw new RuntimeException(
            "Gemini API error {$lastStatus} (todos los modelos sobrecargados): {$lastBody}"
        );
    }

    /**
     * Repara, best-effort, un JSON truncado a mitad de generación:
     * recorta cualquier string sin cerrar y balancea los {} y [] abiertos.
     * Devuelve null si no parece recuperable.
     */
    private function repairTruncatedJson(string $json): ?string
    {
        $json = rtrim($json);
        if ($json === '' || $json[0] !== '{') {
            return null;
        }

        $stack = [];        // pila de contenedores abiertos: '{' o '['
        $inString = false;
        $escaped = false;
        $lastSafe = 0;      // offset hasta el último carácter "seguro" fuera de string

        for ($i = 0, $n = strlen($json); $i < $n; $i++) {
            $ch = $json[$i];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($ch === '\\') {
                    $escaped = true;
                } elseif ($ch === '"') {
                    $inString = false;
                    $lastSafe = $i;
                }
                continue;
            }

            if ($ch === '"') {
                $inString = true;
            } elseif ($ch === '{' || $ch === '[') {
                $stack[] = $ch;
                $lastSafe = $i;
            } elseif ($ch === '}' || $ch === ']') {
                array_pop($stack);
                $lastSafe = $i;
            } elseif ($ch === ',' || (! ctype_space($ch) && $ch !== ':')) {
                $lastSafe = $i;
            }
        }

        // Recortamos hasta el último token completo fuera de string y
        // eliminamos una coma colgante, luego cerramos contenedores abiertos.
        $trimmed = rtrim(substr($json, 0, $lastSafe + 1));
        $trimmed = rtrim($trimmed, ',');

        // Recalculamos qué contenedores siguen abiertos sobre el texto recortado.
        $closers = '';
        $stack2 = [];
        $inString = false;
        $escaped = false;
        for ($i = 0, $n = strlen($trimmed); $i < $n; $i++) {
            $ch = $trimmed[$i];
            if ($inString) {
                if ($escaped) { $escaped = false; }
                elseif ($ch === '\\') { $escaped = true; }
                elseif ($ch === '"') { $inString = false; }
                continue;
            }
            if ($ch === '"') { $inString = true; }
            elseif ($ch === '{' || $ch === '[') { $stack2[] = $ch; }
            elseif ($ch === '}' || $ch === ']') { array_pop($stack2); }
        }
        while (! empty($stack2)) {
            $closers .= (array_pop($stack2) === '{') ? '}' : ']';
        }

        return $trimmed . $closers;
    }

    /**
     * Usa el CA bundle del proyecto si existe (necesario en Windows local
     * donde PHP CLI no tiene curl.cainfo configurado). En servidores Linux
     * con cacert del sistema esto sigue funcionando porque el bundle local
     * es válido y completo.
     */
    private function caBundle(): string|bool
    {
        $local = storage_path('certs/cacert.pem');

        return is_file($local) ? $local : true;
    }

    private function prompt(): string
    {
        return <<<'PROMPT'
Eres un asistente experto en extraer planes nutricionales de documentos.

Te paso un PDF con un plan nutricional. Extrae TODA la información en JSON con esta estructura exacta:

{
  "paciente": { "nombre": "string|null", "edad": "number|null", "altura_cm": "number|null", "peso_kg": "number|null" },
  "objetivos": { "principal": "string", "secundario": "string|null" },
  "metodologia": "string (ej: FODMAP, Low Carbs, Keto, Mediterránea)",
  "comidas": [{
    "id": "slug-en-minusculas-sin-espacios",
    "nombre": "string",
    "hora": "HH:MM en formato 24h",
    "icono_sugerido": "emoji apropiado",
    "descripcion_plan": "string descriptiva",
    "opciones": ["string"],
    "tip": "string|null",
    "notas": ["string"]
  }],
  "comidas_entreno": [],
  "comidas_competencia": [],
  "suplementos_diarios": [],
  "suplementos_entreno": [],
  "permitidos": {
    "vegetales": [], "ensaladas": [], "proteinas": [],
    "tuberculos": [], "bebidas": [], "especias": [],
    "snacks_ansiedad": []
  },
  "evitar": [],
  "comida_libre": "string|null",
  "validacion": { "completitud": "alta|media|baja", "advertencias": ["string"] }
}

REGLAS CRÍTICAS:
1. EXCLUIR de la app: testosterona, HCG, anabólicos, hormonas. Es responsabilidad médica, no de tracking.
2. Conservar TODAS las opciones del plan, no resumir.
3. Si detecta variantes por entreno vs descanso, separarlas en comidas_entreno y comidas vs descanso.
4. Si un campo no aplica o no está en el PDF, devuelva [] (lista vacía) o null según corresponda.
5. Devolver SOLO el JSON. Sin markdown, sin backticks, sin explicaciones.
PROMPT;
    }
}
