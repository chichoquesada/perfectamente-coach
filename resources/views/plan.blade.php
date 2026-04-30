<x-app-layout>
    <x-slot name="header">
        <h1 class="font-serif text-2xl">Mi plan</h1>
        <p class="text-sm text-text-secondary mt-1">Lo que su nutricionista escribió, organizado.</p>
    </x-slot>

    @php
        $d = $plan?->extracted_data ?? [];
        $paciente = $d['paciente'] ?? [];
        $permitidos = $d['permitidos'] ?? [];
        $evitar = $d['evitar'] ?? [];
        $supDiarios = $d['suplementos_diarios'] ?? [];
        $supEntreno = $d['suplementos_entreno'] ?? [];
        $comidaLibre = $d['comida_libre'] ?? null;
        $validacion = $d['validacion'] ?? null;

        $categoriasOrden = [
            'proteinas' => ['Proteínas', '🥩'],
            'vegetales' => ['Vegetales', '🥬'],
            'ensaladas' => ['Ensaladas', '🥗'],
            'tuberculos' => ['Tubérculos', '🥔'],
            'bebidas' => ['Bebidas', '🥤'],
            'especias' => ['Especias', '🌶️'],
            'snacks_ansiedad' => ['Snacks ansiedad', '🍿'],
        ];
    @endphp

    @if (! $plan)
        <div class="bg-bg-card border border-white/[0.06] rounded-2xl p-8 text-center">
            <p class="text-text-secondary">No tiene plan activo.</p>
            <a href="{{ route('onboarding.show') }}" class="inline-block mt-4 text-gold underline text-sm">Subir mi plan</a>
        </div>
    @else
        {{-- Resumen del paciente --}}
        @if (array_filter($paciente))
            <div class="bg-bg-card border border-white/[0.06] rounded-2xl p-6 mb-6">
                <p class="text-xs text-gold tracking-[0.25em] uppercase mb-3">Paciente</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    @if (! empty($paciente['nombre']))
                        <div>
                            <div class="text-xs text-text-secondary mb-0.5">Nombre</div>
                            <div class="font-serif">{{ $paciente['nombre'] }}</div>
                        </div>
                    @endif
                    @if (! empty($paciente['edad']))
                        <div>
                            <div class="text-xs text-text-secondary mb-0.5">Edad</div>
                            <div class="font-serif">{{ $paciente['edad'] }} años</div>
                        </div>
                    @endif
                    @if (! empty($paciente['altura_cm']))
                        <div>
                            <div class="text-xs text-text-secondary mb-0.5">Altura</div>
                            <div class="font-serif">{{ $paciente['altura_cm'] }} cm</div>
                        </div>
                    @endif
                    @if (! empty($paciente['peso_kg']))
                        <div>
                            <div class="text-xs text-text-secondary mb-0.5">Peso</div>
                            <div class="font-serif">{{ $paciente['peso_kg'] }} kg</div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Permitidos por categoría --}}
        @php
            $hayPermitidos = collect($categoriasOrden)->keys()->contains(fn ($k) => ! empty($permitidos[$k] ?? null));
        @endphp
        @if ($hayPermitidos)
            <div class="bg-bg-card border border-white/[0.06] rounded-2xl p-6 mb-6">
                <p class="text-xs text-fiel tracking-[0.25em] uppercase mb-4">Permitidos</p>
                <div class="space-y-5">
                    @foreach ($categoriasOrden as $key => [$label, $icon])
                        @if (! empty($permitidos[$key] ?? null))
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-base">{{ $icon }}</span>
                                    <h3 class="font-serif text-base">{{ $label }}</h3>
                                    <span class="text-xs text-text-secondary/60">{{ count($permitidos[$key]) }}</span>
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($permitidos[$key] as $item)
                                        <span class="text-xs px-2.5 py-1 bg-fiel/10 border border-fiel/20 text-text-primary rounded-full">
                                            {{ $item }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Evitar --}}
        @if (count($evitar) > 0)
            <div class="bg-bg-card border border-white/[0.06] rounded-2xl p-6 mb-6">
                <p class="text-xs text-nofiel tracking-[0.25em] uppercase mb-4">Evitar</p>
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($evitar as $item)
                        <span class="text-xs px-2.5 py-1 bg-nofiel/10 border border-nofiel/20 text-text-primary rounded-full">
                            {{ $item }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Suplementos --}}
        @if (count($supDiarios) > 0 || count($supEntreno) > 0)
            <div class="bg-bg-card border border-white/[0.06] rounded-2xl p-6 mb-6">
                <p class="text-xs text-gold tracking-[0.25em] uppercase mb-4">Suplementos</p>

                @if (count($supDiarios) > 0)
                    <h3 class="font-serif text-base mb-2">Diarios</h3>
                    <ul class="space-y-1.5 mb-4">
                        @foreach ($supDiarios as $s)
                            <li class="text-sm text-text-primary flex gap-2">
                                <span class="text-gold">·</span>
                                <span>
                                    {{ is_array($s) ? ($s['nombre'] ?? '') . (isset($s['dosis']) ? ' — ' . $s['dosis'] : '') : $s }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if (count($supEntreno) > 0)
                    <h3 class="font-serif text-base mb-2">Para entreno</h3>
                    <ul class="space-y-1.5">
                        @foreach ($supEntreno as $s)
                            <li class="text-sm text-text-primary flex gap-2">
                                <span class="text-gold">·</span>
                                <span>
                                    {{ is_array($s) ? ($s['nombre'] ?? '') . (isset($s['dosis']) ? ' — ' . $s['dosis'] : '') : $s }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        {{-- Comida libre --}}
        @if ($comidaLibre)
            <div class="bg-bg-card border border-gold/20 rounded-2xl p-6 mb-6">
                <p class="text-xs text-gold tracking-[0.25em] uppercase mb-2">Comida libre</p>
                <p class="text-sm text-text-primary italic font-serif">{{ $comidaLibre }}</p>
            </div>
        @endif

        {{-- Validación de IA --}}
        @if ($validacion && ! empty($validacion['advertencias'] ?? []))
            <div class="bg-bg-card border border-white/[0.06] rounded-2xl p-6 mb-6">
                <p class="text-xs text-text-secondary tracking-[0.25em] uppercase mb-3">Notas de extracción</p>
                <p class="text-xs text-text-secondary mb-2">
                    Completitud detectada:
                    <span class="text-text-primary font-medium">{{ $validacion['completitud'] ?? 'media' }}</span>
                </p>
                <ul class="space-y-1.5">
                    @foreach ($validacion['advertencias'] as $w)
                        <li class="text-xs text-text-secondary flex gap-2">
                            <span class="text-parcial">!</span>
                            <span>{{ $w }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif
</x-app-layout>
