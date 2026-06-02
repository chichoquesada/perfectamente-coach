<?php

namespace App\Http\Controllers;

use App\Services\WeeklyInsightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InsightController extends Controller
{
    /**
     * GET /api/insight/weekly
     * Cachea 24h por usuario para no quemar tokens si refresca varias veces.
     * Query param ?refresh=1 fuerza regeneración (rate-limit en frontend).
     */
    public function weekly(Request $request, WeeklyInsightService $svc): JsonResponse
    {
        $userId = Auth::id();
        $cacheKey = "insight:weekly:user:{$userId}:" . now()->toDateString();

        if ($request->boolean('refresh')) {
            Cache::forget($cacheKey);
        }

        try {
            $insight = Cache::remember($cacheKey, now()->addHours(24), function () use ($svc) {
                return $svc->generate(Auth::user());
            });
        } catch (\Throwable $e) {
            Log::error('Weekly insight failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            // Distinguir "servicio de IA saturado" (503/429 de Google, tras agotar
            // reintentos y rotación de modelos) de un error real, para no alarmar.
            $msg = $e->getMessage();
            $overloaded = str_contains($msg, 'sobrecargados')
                || str_contains($msg, '503')
                || str_contains($msg, '429');

            return response()->json([
                'error' => $overloaded
                    ? 'El servicio de IA está saturado en este momento (alta demanda). Espere un minuto y vuelva a generar su análisis.'
                    : 'No pudimos generar su análisis. Intente más tarde.',
            ], $overloaded ? 503 : 500);
        }

        return response()->json($insight);
    }
}
