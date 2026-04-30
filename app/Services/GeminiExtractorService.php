<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
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

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-goog-api-key' => $this->apiKey,
        ])->timeout(120)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent",
            [
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
                    'maxOutputTokens' => 8192,
                ],
            ]
        );

        if (! $response->successful()) {
            throw new RuntimeException(
                "Gemini API error {$response->status()}: " . $response->body()
            );
        }

        $jsonText = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($jsonText) || $jsonText === '') {
            throw new RuntimeException(
                'Gemini devolvió respuesta vacía: ' . $response->body()
            );
        }

        try {
            return json_decode($jsonText, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new RuntimeException(
                'Gemini devolvió JSON malformado: ' . $e->getMessage() . ' | raw: ' . $jsonText
            );
        }
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
