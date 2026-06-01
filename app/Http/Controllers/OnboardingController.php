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
    public function show(Request $request): View|RedirectResponse
    {
        $hasActivePlan = Auth::user()->activeNutritionalPlan()->exists();

        // Con plan activo, sólo entramos al formulario si el usuario eligió
        // explícitamente "Subir nuevo plan" (?nuevo=1). Si no, al dashboard.
        if ($hasActivePlan && ! $request->boolean('nuevo')) {
            return redirect()->route('dashboard');
        }

        return view('onboarding.show', ['hasActivePlan' => $hasActivePlan]);
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

            // Distinguimos "servicio ocupado" (503/429 de Google) de un PDF
            // realmente ilegible, para no culpar al PDF cuando no es la causa.
            $overloaded = str_contains($e->getMessage(), 'sobrecargado')
                || str_contains($e->getMessage(), '503')
                || str_contains($e->getMessage(), '429');

            $msg = $overloaded
                ? 'El servicio de IA está saturado en este momento (alta demanda). No es problema de su PDF — espere un minuto y vuelva a intentarlo.'
                : 'No pudimos leer su plan. Intente de nuevo o verifique que el PDF tenga texto legible (no escaneado).';

            return back()
                ->withInput()
                ->withErrors(['pdf' => $msg]);
        }

        // Archivar cualquier plan activo previo: 1 activo a la vez, el resto queda
        // en el historial. (El global scope de BelongsToUser limita al usuario.)
        NutritionalPlan::where('is_active', true)->update(['is_active' => false]);

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
