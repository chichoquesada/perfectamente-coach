<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="font-serif text-2xl">Mis planes</h1>
                <p class="text-sm text-text-secondary mt-1">Tu historial. El activo arriba; los anteriores quedan guardados para ver tu evolución.</p>
            </div>
            <a href="{{ route('onboarding.show', ['nuevo' => 1]) }}" class="shrink-0 inline-flex items-center gap-2 bg-gold text-black px-4 py-2 rounded-full font-bold text-sm hover:bg-gold/90 transition">
                <span aria-hidden="true">+</span> Subir nuevo plan
            </a>
        </div>
    </x-slot>

    @if ($plans->isEmpty())
        <div class="bg-bg-card border border-line/[0.06] rounded-2xl p-8 text-center">
            <div class="text-5xl font-serif italic text-gold mb-4">~</div>
            <p class="text-text-secondary text-sm mb-6">Todavía no tenés planes cargados.</p>
            <a href="{{ route('onboarding.show') }}" class="inline-flex items-center gap-2 bg-gold text-black px-5 py-2.5 rounded-full font-bold text-sm hover:bg-gold/90 transition">
                Subir mi plan <span aria-hidden="true">→</span>
            </a>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($plans as $plan)
                @php
                    $d = $plan->extracted_data ?? [];
                    $pac = $d['paciente'] ?? [];
                    $nComidas = count($d['comidas'] ?? []);
                    $metricas = array_filter([
                        ! empty($pac['peso_kg']) ? $pac['peso_kg'].' kg' : null,
                        ! empty($pac['altura_cm']) ? $pac['altura_cm'].' cm' : null,
                    ]);
                @endphp
                <div class="bg-bg-card border border-line/[0.06] rounded-2xl p-5 hover:border-gold/30 transition">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs text-text-secondary">{{ $plan->created_at?->isoFormat('D [de] MMMM, YYYY') }}</span>
                            @if ($plan->is_active)
                                <span class="text-[10px] px-2 py-0.5 bg-fiel/15 border border-fiel/30 text-fiel rounded-full uppercase tracking-wider">Activo</span>
                            @else
                                <span class="text-[10px] px-2 py-0.5 bg-line/10 border border-line/15 text-text-secondary rounded-full uppercase tracking-wider">Archivado</span>
                            @endif
                        </div>
                        <h2 class="font-serif text-lg truncate">{{ $plan->objetivo_principal ?? 'Plan nutricional' }}</h2>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-xs text-text-secondary">
                            @if ($plan->metodologia)
                                <span>{{ $plan->metodologia }}</span>
                            @endif
                            @if ($nComidas > 0)
                                <span>· {{ $nComidas }} comidas</span>
                            @endif
                            @foreach ($metricas as $m)
                                <span>· {{ $m }}</span>
                            @endforeach
                        </div>
                    </div>

                    {{-- Pie de acciones: presente en TODOS los planes --}}
                    <div class="flex items-center gap-2 mt-4 pt-4 border-t border-line/[0.06]">
                        <a href="{{ route('plans.showOne', $plan) }}"
                           class="inline-flex items-center gap-1.5 text-xs font-semibold px-4 py-2 rounded-full border border-line/15 text-text-secondary hover:border-gold/40 hover:text-text-primary transition">
                            Ver detalle
                        </a>
                        @if ($plan->is_active)
                            <span class="text-xs text-fiel/80 px-2">★ Es tu plan actual</span>
                        @else
                            <form action="{{ route('plans.reactivate', $plan) }}" method="POST"
                                  onsubmit="return confirm('¿Reactivar este plan? Pasará a ser tu plan actual y el activo de ahora quedará archivado.');">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1.5 text-xs font-bold px-4 py-2 rounded-full bg-gold text-black hover:bg-gold/90 transition">
                                    <span aria-hidden="true">↻</span> Reactivar este plan
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-app-layout>
