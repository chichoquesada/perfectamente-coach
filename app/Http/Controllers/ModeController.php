<?php

namespace App\Http\Controllers;

use App\Models\DailyMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModeController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mode' => ['required', 'in:descanso,entreno,competencia'],
        ]);

        DailyMode::updateOrCreate(
            ['date' => now()->toDateString()],
            ['mode' => $data['mode']],
        );

        return response()->json(['mode' => $data['mode']]);
    }
}
