<?php

namespace App\Http\Controllers\Nutri;

use App\Http\Controllers\Controller;
use App\Models\Methodology;
use App\Models\NutritionalPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        $plans = NutritionalPlan::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get();

        return view('nutri.plans.index', compact('plans'));
    }

    public function create(): View
    {
        return view('nutri.plans.form', [
            'plan' => null,
            'extracted' => $this->emptyShape(),
            'methodologies' => $this->methodologyOptions(),
            'templates' => $this->templateOptions(),
        ]);
    }

    public function edit(NutritionalPlan $plan): View
    {
        $this->authorizeOwn($plan);

        return view('nutri.plans.form', [
            'plan' => $plan,
            'extracted' => $this->migrateLegacySupplements(
                array_merge($this->emptyShape(), $plan->extracted_data ?? [])
            ),
            'methodologies' => $this->methodologyOptions(),
            'templates' => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $extracted = $this->parseAndValidate($request);

        NutritionalPlan::create([
            'user_id' => Auth::id(),
            'extracted_data' => $extracted,
            'metodologia' => $extracted['metodologia'] ?? null,
            'objetivo_principal' => $extracted['objetivos']['principal'] ?? null,
            'is_active' => false,
        ]);

        return redirect()->route('nutri.plans.index')->with('status', 'Plan creado.');
    }

    public function update(Request $request, NutritionalPlan $plan): RedirectResponse
    {
        $this->authorizeOwn($plan);

        $extracted = $this->parseAndValidate($request);

        $plan->update([
            'extracted_data' => $extracted,
            'metodologia' => $extracted['metodologia'] ?? null,
            'objetivo_principal' => $extracted['objetivos']['principal'] ?? null,
        ]);

        return redirect()->route('nutri.plans.index')->with('status', 'Plan actualizado.');
    }

    public function destroy(NutritionalPlan $plan): RedirectResponse
    {
        $this->authorizeOwn($plan);

        $plan->delete();

        return back()->with('status', 'Plan eliminado.');
    }

    public function duplicate(NutritionalPlan $plan): RedirectResponse
    {
        $this->authorizeOwn($plan);

        $extracted = $plan->extracted_data ?? [];
        $originalName = $extracted['paciente']['nombre'] ?? 'Plan';
        $extracted['paciente']['nombre'] = $originalName.' (copia)';

        $copy = NutritionalPlan::create([
            'user_id' => Auth::id(),
            'extracted_data' => $extracted,
            'metodologia' => $plan->metodologia,
            'objetivo_principal' => $plan->objetivo_principal,
            'is_active' => false,
        ]);

        return redirect()->route('nutri.plans.edit', $copy)->with('status', 'Plan duplicado. Modifíquelo y guarde.');
    }

    private function authorizeOwn(NutritionalPlan $plan): void
    {
        if ($plan->user_id !== Auth::id()) {
            abort(404);
        }
    }

    /**
     * Metodologías disponibles para el nutri actual: base globales + propias.
     */
    private function methodologyOptions()
    {
        return Methodology::where(function ($q) {
            $q->whereNull('user_id')->orWhere('user_id', Auth::id());
        })
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Planes propios del nutri para "Cargar desde plantilla" (prefill client-side).
     */
    private function templateOptions()
    {
        return NutritionalPlan::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->extracted_data['paciente']['nombre'] ?? 'Plan',
                'data' => $this->migrateLegacySupplements(
                    array_merge($this->emptyShape(), $p->extracted_data ?? [])
                ),
            ])
            ->values();
    }

    private function emptyShape(): array
    {
        return [
            'paciente' => ['nombre' => '', 'edad' => null, 'peso_kg' => null, 'altura_cm' => null],
            'objetivos' => ['principal' => '', 'secundario' => ''],
            'metodologia' => '',
            'comidas' => [],
            'comidas_entreno' => [],
            'comidas_competencia' => [],
            'suplementos' => [],
            'farmacologia' => [],
            'suplementos_diarios' => [], // legacy: lista plana de strings
            'evitar' => [],
            'permitidos' => [
                'proteinas' => [], 'vegetales' => [], 'bebidas' => [],
                'especias' => [], 'ensaladas' => [],
            ],
        ];
    }

    /**
     * Compat: planes viejos guardaban suplementos como lista plana de strings en
     * `suplementos_diarios`. Si el nuevo `suplementos` está vacío, los migramos a
     * la forma estructurada para que se vean en el editor y dashboard.
     */
    private function migrateLegacySupplements(array $extracted): array
    {
        if (empty($extracted['suplementos']) && ! empty($extracted['suplementos_diarios'])) {
            $extracted['suplementos'] = collect($extracted['suplementos_diarios'])
                ->map(fn ($s) => is_array($s) ? $s : ['nombre' => (string) $s])
                ->filter(fn ($s) => trim($s['nombre'] ?? '') !== '')
                ->map(fn ($s) => [
                    'id' => 'sup-'.Str::slug($s['nombre']).'-'.substr(md5($s['nombre']), 0, 4),
                    'nombre' => $s['nombre'],
                    'dosis' => $s['dosis'] ?? null,
                    'frecuencia' => $s['frecuencia'] ?? null,
                    'nota' => $s['nota'] ?? null,
                ])
                ->values()
                ->all();
        }

        return $extracted;
    }

    private function parseAndValidate(Request $request): array
    {
        $request->validate([
            'plan_data' => ['required', 'json'],
        ]);

        $raw = json_decode($request->input('plan_data'), true);
        if (! is_array($raw)) {
            abort(422, 'Estructura del plan inválida.');
        }

        $clean = $this->emptyShape();

        $clean['paciente']['nombre'] = trim($raw['paciente']['nombre'] ?? '') ?: 'Plan sin nombre';
        $clean['objetivos']['principal'] = trim($raw['objetivos']['principal'] ?? '');
        $clean['objetivos']['secundario'] = trim($raw['objetivos']['secundario'] ?? '');
        $clean['metodologia'] = trim($raw['metodologia'] ?? '');

        foreach (['comidas', 'comidas_entreno', 'comidas_competencia'] as $bucket) {
            $clean[$bucket] = $this->normalizeComidas($raw[$bucket] ?? []);
        }

        $clean['suplementos'] = $this->normalizeStructured($raw['suplementos'] ?? [], 'sup');
        $clean['farmacologia'] = $this->normalizeStructured($raw['farmacologia'] ?? [], 'farm');
        // Mantener legacy sincronizado para vistas que aún lo lean.
        $clean['suplementos_diarios'] = collect($clean['suplementos'])->pluck('nombre')->all();

        $clean['evitar'] = $this->cleanList($raw['evitar'] ?? []);

        foreach (array_keys($clean['permitidos']) as $cat) {
            $clean['permitidos'][$cat] = $this->cleanList($raw['permitidos'][$cat] ?? []);
        }

        // Persistir metodología nueva para reutilizarla en el dropdown.
        $this->rememberMethodology($clean['metodologia']);

        return $clean;
    }

    private function rememberMethodology(string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            return;
        }

        $exists = Methodology::where('name', $name)
            ->where(function ($q) {
                $q->whereNull('user_id')->orWhere('user_id', Auth::id());
            })
            ->exists();

        if (! $exists) {
            Methodology::create(['user_id' => Auth::id(), 'name' => $name]);
        }
    }

    private function normalizeComidas(array $items): array
    {
        $out = [];
        foreach ($items as $c) {
            $nombre = trim($c['nombre'] ?? '');
            if ($nombre === '') continue;

            $opciones = $this->cleanList($c['opciones'] ?? []);

            $out[] = [
                'id' => Str::slug($nombre).'-'.substr(md5($nombre.count($out)), 0, 4),
                'nombre' => $nombre,
                'hora' => trim($c['hora'] ?? '') ?: null,
                'icono_sugerido' => trim($c['icono_sugerido'] ?? '') ?: '🍽️',
                'descripcion_plan' => trim($c['descripcion_plan'] ?? '') ?: null,
                'opciones' => $opciones,
                'tip' => trim($c['tip'] ?? '') ?: null,
                'notas' => [],
            ];
        }
        return $out;
    }

    /**
     * Normaliza suplementos / farmacología. Cada id lleva prefijo ('sup'/'farm')
     * para distinguirlos de las comidas y NO contaminar el cálculo de fidelidad.
     */
    private function normalizeStructured(array $items, string $prefix): array
    {
        $out = [];
        foreach ($items as $i) {
            $nombre = trim($i['nombre'] ?? '');
            if ($nombre === '') continue;

            $out[] = [
                'id' => $prefix.'-'.Str::slug($nombre).'-'.substr(md5($nombre.count($out)), 0, 4),
                'nombre' => $nombre,
                'dosis' => trim($i['dosis'] ?? '') ?: null,
                'frecuencia' => trim($i['frecuencia'] ?? '') ?: null,
                'nota' => trim($i['nota'] ?? '') ?: null,
            ];
        }
        return $out;
    }

    private function cleanList(array $items): array
    {
        return array_values(array_filter(array_map(
            fn ($x) => trim((string) $x),
            $items
        ), fn ($x) => $x !== ''));
    }
}
