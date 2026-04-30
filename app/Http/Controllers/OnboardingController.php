<?php

namespace App\Http\Controllers;

use App\Models\NutritionalPlan;
use App\Services\GeminiExtractorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::user()->activeNutritionalPlan()->exists()) {
            return redirect()->route('dashboard');
        }

        return view('onboarding.show');
    }

    public function uploadPdf(Request $request, GeminiExtractorService $gemini): RedirectResponse
    {
        $request->validate([
            'pdf' => ['required', 'file', 'mimetypes:application/pdf', 'max:10240'],
        ], [
            'pdf.required' => 'Suba el PDF de su plan.',
            'pdf.mimetypes' => 'El archivo debe ser un PDF válido.',
            'pdf.max' => 'Máximo 10 MB.',
        ]);

        $userId = Auth::id();
        $path = $request->file('pdf')->store("plans/{$userId}");
        $absolutePath = Storage::path($path);

        // Subimos timeout para llamadas Gemini (PDFs largos pueden tardar 30-60s).
        set_time_limit(180);

        try {
            $extracted = $gemini->extractPlanFromPdf($absolutePath);
        } catch (\Throwable $e) {
            Log::error('Gemini extraction failed', [
                'user_id' => $userId,
                'pdf_path' => $path,
                'error' => $e->getMessage(),
            ]);

            // Borramos el PDF para no dejar huérfano. El usuario reintenta.
            Storage::delete($path);

            return back()
                ->withInput()
                ->withErrors([
                    'pdf' => 'No pudimos leer su plan. Intente de nuevo o verifique que el PDF tenga texto legible (no escaneado).',
                ]);
        }

        NutritionalPlan::create([
            'pdf_path' => $path,
            'extracted_data' => $extracted,
            'metodologia' => $extracted['metodologia'] ?? null,
            'objetivo_principal' => $extracted['objetivos']['principal'] ?? null,
            'is_active' => true,
        ]);

        return redirect()
            ->route('dashboard')
            ->with('status', 'Plan recibido y procesado. Bienvenido.');
    }
}
