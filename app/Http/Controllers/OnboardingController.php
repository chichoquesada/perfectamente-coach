<?php

namespace App\Http\Controllers;

use App\Models\NutritionalPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function uploadPdf(Request $request): RedirectResponse
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

        // Placeholder: la extracción real con Gemini llega en bloque E.
        // Por ahora creamos el plan vacío para desbloquear el dashboard.
        NutritionalPlan::create([
            'pdf_path' => $path,
            'extracted_data' => [
                'pending_extraction' => true,
                'uploaded_at' => now()->toIso8601String(),
            ],
            'is_active' => true,
        ]);

        return redirect()->route('dashboard')->with('status', 'Plan recibido. Pronto procesaremos su PDF.');
    }
}
