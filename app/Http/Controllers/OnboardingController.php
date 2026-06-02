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
        // NOTA: no usamos las reglas `mimes:`/`mimetypes:` porque internamente
        // llaman a getMimeType() → Symfony Mime → la extensión php_fileinfo.
        // En el hosting de producción (ea-php84) fileinfo está DESACTIVADO, así
        // que ese guessing revienta con 500. Validamos por extensión + magic
        // bytes (%PDF), que no dependen de fileinfo.
        $request->validate([
            'pdf' => ['required', 'file', 'extensions:pdf', 'max:10240'],
        ], [
            'pdf.required' => 'Suba el PDF de su plan.',
            'pdf.extensions' => 'El archivo debe ser un PDF válido.',
            'pdf.max' => 'Máximo 10 MB.',
        ]);

        $file = $request->file('pdf');

        // Chequeo de firma: los PDF empiezan con "%PDF". Sustituye al MIME
        // guessing sin necesitar fileinfo.
        if (strncmp((string) file_get_contents($file->getRealPath(), false, null, 0, 4), '%PDF', 4) !== 0) {
            return back()
                ->withInput()
                ->withErrors(['pdf' => 'El archivo no parece ser un PDF válido.']);
        }

        $userId = Auth::id();

        // NO usamos Storage::/storeAs(): el adaptador local de Flysystem
        // instancia un FinfoMimeTypeDetector en su CONSTRUCTOR (→ new finfo()),
        // así que cualquier acceso al disco revienta con "Class finfo not found"
        // cuando fileinfo está desactivado (caso de ea-php84 en prod). Escribimos
        // el archivo directo con UploadedFile::move() → move_uploaded_file(),
        // que no toca Flysystem ni fileinfo.
        $filename = \Illuminate\Support\Str::random(40).'.pdf';
        $relativeDir = "plans/{$userId}";
        $absoluteDir = storage_path("app/private/{$relativeDir}");
        $file->move($absoluteDir, $filename);
        $path = "{$relativeDir}/{$filename}";
        $absolutePath = "{$absoluteDir}/{$filename}";

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
            // @unlink directo (no Storage::, que reventaría por fileinfo).
            @unlink($absolutePath);

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
