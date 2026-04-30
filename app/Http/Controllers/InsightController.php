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

            return response()->json([
                'error' => 'No pudimos generar su análisis. Intente más tarde.',
            ], 500);
        }

        return response()->json($insight);
    }
}
