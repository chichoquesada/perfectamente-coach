<x-app-layout>
    <x-slot name="header">
        <h1 class="font-serif text-2xl">Mi plan</h1>
        <p class="text-sm text-text-secondary mt-1">Lo que su nutricionista escribió, organizado.</p>
    </x-slot>

    @php
        $d = $plan?->extracted_data ?? [];
        $paciente = $d['paciente'] ?? [];
        $comidas = $d['comidas'] ?? [];
        $comidasEntreno = $d['comidas_entreno'] ?? [];
        $comidasCompetencia = $d['comidas_competencia'] ?? [];
        $permitidos = $d['permitidos'] ?? [];
        $evitar = $d['evitar'] ?? [];
        $suplementos = $d['suplementos'] ?? [];
        $farmacologia = $d['farmacologia'] ?? [];
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
        <div class="bg-bg-card border border-line/[0.06] rounded-2xl p-8 text-center">
            <p class="text-text-secondary">No tiene plan activo.</p>
            <a href="{{ route('onboarding.show') }}" class="inline-block mt-4 text-gold underline text-sm">Subir mi plan</a>
        </div>
    @else
        {{-- Resumen del paciente --}}
        @if (array_filter($paciente))
            <div class="bg-bg-card border border-line/[0.06] rounded-2xl p-6 mb-6">
                <p class="text-xs text-gold tracking-[0.25em] uppercase mb-3">Paciente</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    <div>
                        <div class="text-xs text-text-secondary mb-0.5">Nombre</div>
                        <div class="font-serif">{{ auth()->user()->name ?: ($paciente['nombre'] ?? '') }}</div>
                    </div>
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

        {{-- Comidas (lo central: qué comer) --}}
        @php
            $gruposComidas = array_filter([
                ['label' => 'Comidas del día', 'color' => 'gold', 'items' => $comidas],
                ['label' => '💪 Días de entreno', 'color' => 'gold', 'items' => $comidasEntreno],
                ['label' => '🏆 Días de competencia', 'color' => 'gold', 'items' => $comidasCompetencia],
            ], fn ($g) => count($g['items']) > 0);
        @endphp
        @foreach ($gruposComidas as $grupo)
            <div class="bg-bg-card border border-line/[0.06] rounded-2xl p-6 mb-6">
                <p class="text-xs text-{{ $grupo['color'] }} tracking-[0.25em] uppercase mb-4">{{ $grupo['label'] }}</p>
                <div class="space-y-4">
                    @foreach ($grupo['items'] as $c)
                        <div class="bg-bg/40 border border-line/[0.06] rounded-xl p-4">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 flex items-center justify-center bg-line/5 rounded-lg text-xl shrink-0">
                                    {{ $c['icono_sugerido'] ?? '🍽️' }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    @if (! empty($c['hora']))
                                        <div class="text-xs text-gold uppercase tracking-wider">{{ $c['hora'] }}</div>
                                    @endif
                                    <h3 class="font-serif text-lg">{{ $c['nombre'] ?? 'Comida' }}</h3>
                                    @if (! empty($c['descripcion_plan']))
                                        <p class="text-sm text-text-secondary mt-1">{{ $c['descripcion_plan'] }}</p>
                                    @endif
                                </div>
                            </div>

                            @if (! empty($c['opciones']))
                                <div class="mt-3">
                                    <p class="text-xs text-text-secondary/70 uppercase tracking-wider mb-1.5">Opciones</p>
                                    <ul class="space-y-1">
                                        @foreach ($c['opciones'] as $op)
                                            <li class="text-sm text-text-primary flex gap-2">
                                                <span class="text-gold shrink-0">·</span>
                                                <span>{{ $op }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if (! empty($c['notas']))
                                <ul class="mt-3 space-y-1">
                                    @foreach ($c['notas'] as $n)
                                        <li class="text-xs text-text-secondary flex gap-2">
                                            <span class="text-parcial shrink-0">!</span>
                                            <span>{{ $n }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            @if (! empty($c['tip']))
                                <div class="mt-3 bg-gold/5 border-l-2 border-gold/40 rounded-r-lg px-3 py-2">
                                    <p class="text-xs text-text-secondary italic">💡 {{ $c['tip'] }}</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- Suplementos (estructurados) --}}
        @if (count($suplementos) > 0)
            <div class="bg-bg-card border border-line/[0.06] rounded-2xl p-6 mb-6">
                <p class="text-xs text-gold tracking-[0.25em] uppercase mb-4">🥤 Suplementos</p>
                <div class="space-y-3">
                    @foreach ($suplementos as $s)
                        <div class="flex items-start gap-3">
                            <span class="text-gold mt-0.5">·</span>
                            <div>
                                <div class="text-sm text-text-primary">
                                    <span class="font-medium">{{ $s['nombre'] ?? '' }}</span>
                                    @if (! empty($s['dosis']))<span class="text-text-secondary"> — {{ $s['dosis'] }}</span>@endif
                                    @if (! empty($s['frecuencia']))<span class="text-text-secondary/70"> · {{ $s['frecuencia'] }}</span>@endif
                                </div>
                                @if (! empty($s['nota']))
                                    <div class="text-xs text-text-secondary/60 italic mt-0.5">{{ $s['nota'] }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @elseif (count($supDiarios) > 0 || count($supEntreno) > 0)
            {{-- Fallback legacy: lista plana --}}
            <div class="bg-bg-card border border-line/[0.06] rounded-2xl p-6 mb-6">
                <p class="text-xs text-gold tracking-[0.25em] uppercase mb-4">Suplementos</p>
                <ul class="space-y-1.5">
                    @foreach (array_merge($supDiarios, $supEntreno) as $s)
                        <li class="text-sm text-text-primary flex gap-2">
                            <span class="text-gold">·</span>
                            <span>{{ is_array($s) ? ($s['nombre'] ?? '') : $s }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Farmacología (estructurada) --}}
        @if (count($farmacologia) > 0)
            <div class="bg-bg-card border border-line/[0.06] rounded-2xl p-6 mb-6">
                <p class="text-xs text-gold tracking-[0.25em] uppercase mb-4">💊 Farmacología</p>
                <div class="space-y-3">
                    @foreach ($farmacologia as $f)
                        <div class="flex items-start gap-3">
                            <span class="text-gold mt-0.5">·</span>
                            <div>
                                <div class="text-sm text-text-primary">
                                    <span class="font-medium">{{ $f['nombre'] ?? '' }}</span>
                                    @if (! empty($f['dosis']))<span class="text-text-secondary"> — {{ $f['dosis'] }}</span>@endif
                                    @if (! empty($f['frecuencia']))<span class="text-text-secondary/70"> · {{ $f['frecuencia'] }}</span>@endif
                                </div>
                                @if (! empty($f['nota']))
                                    <div class="text-xs text-text-secondary/60 italic mt-0.5">{{ $f['nota'] }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Permitidos por categoría --}}
        @php
            $hayPermitidos = collect($categoriasOrden)->keys()->contains(fn ($k) => ! empty($permitidos[$k] ?? null));
        @endphp
        @if ($hayPermitidos)
            <div class="bg-bg-card border border-line/[0.06] rounded-2xl p-6 mb-6">
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
            <div class="bg-bg-card border border-line/[0.06] rounded-2xl p-6 mb-6">
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

        {{-- Comida libre --}}
        @if ($comidaLibre)
            <div class="bg-bg-card border border-gold/20 rounded-2xl p-6 mb-6">
                <p class="text-xs text-gold tracking-[0.25em] uppercase mb-2">Comida libre</p>
                <p class="text-sm text-text-primary italic font-serif">{{ $comidaLibre }}</p>
            </div>
        @endif

        {{-- Validación de IA --}}
        @if ($validacion && ! empty($validacion['advertencias'] ?? []))
            <div class="bg-bg-card border border-line/[0.06] rounded-2xl p-6 mb-6">
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
