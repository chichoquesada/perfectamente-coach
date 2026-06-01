<x-app-layout>
    <x-slot name="header">
        <h1 class="font-serif text-2xl">Hoy</h1>
        <p class="text-sm text-text-secondary mt-1">{{ now()->isoFormat('dddd, D [de] MMMM') }}</p>
    </x-slot>

    @if (session('status'))
        <div class="mb-6 p-4 bg-fiel/10 border border-fiel/30 rounded-xl text-sm text-fiel">
            {{ session('status') }}
        </div>
    @endif

    @php
        $data = $plan?->extracted_data ?? [];
        // Nombre de la cuenta (confiable) por encima del extraído del PDF, que puede venir truncado.
        $paciente = auth()->user()->name ?: ($data['paciente']['nombre'] ?? null);
        $objetivo = $data['objetivos']['principal'] ?? null;
        $metodologia = $data['metodologia'] ?? null;
        // $comidas, $mode, $checksToday, $fidelidad, $suplementos, $farmacologia vienen del controller
        $totalSupFarma = count($suplementos) + count($farmacologia);

        // Map itemId => hora actual (para edición reactiva de horas en el cliente).
        $horasIniciales = [];
        foreach ($comidas as $idx => $c) {
            $id = $c['id'] ?? \Illuminate\Support\Str::slug($c['nombre'] ?? 'comida-'.$idx);
            $horasIniciales[$id] = $c['hora'] ?? null;
        }
    @endphp

    @if (! $plan)
        <div class="bg-bg-card border border-line/[0.06] rounded-2xl p-8 text-center">
            <div class="text-5xl font-serif italic text-gold mb-4">~</div>
            <h2 class="font-serif text-xl mb-2">Aún no ha subido su plan</h2>
            <p class="text-text-secondary text-sm mb-6 max-w-sm mx-auto">
                Suba el PDF de su nutricionista. La IA lo lee, lo organiza y le entrega su tablero diario.
            </p>
            <a href="{{ route('onboarding.show') }}" class="inline-flex items-center gap-2 bg-gold text-black px-5 py-2.5 rounded-full font-bold text-sm hover:bg-gold/90 transition">
                Subir mi plan <span aria-hidden="true">→</span>
            </a>
        </div>
    @else
    <div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div
            x-data="dashboard({{ \Illuminate\Support\Js::from($checksToday) }}, {{ \Illuminate\Support\Js::from($notesToday) }}, {{ $fidelidad }}, {{ count($comidas) }}, '{{ $mode }}', {{ \Illuminate\Support\Js::from($horasIniciales) }}, {{ $supplementsAffect ? 'true' : 'false' }})"
            class="bg-bg-card border border-line/[0.06] rounded-2xl p-6 sm:p-8"
        >
            <div class="flex items-start justify-between gap-4 mb-6">
                <div>
                    <p class="text-xs text-gold tracking-[0.25em] uppercase mb-2">Su plan</p>
                    @if ($paciente)
                        <h2 class="font-serif text-2xl mb-1">{{ $paciente }}</h2>
                    @endif
                    @if ($objetivo)
                        <p class="text-sm text-text-secondary">{{ $objetivo }}</p>
                    @endif
                </div>
                @if ($metodologia)
                    <span class="shrink-0 text-xs px-3 py-1 bg-line/5 border border-line/10 rounded-full text-text-secondary">
                        {{ $metodologia }}
                    </span>
                @endif
            </div>

            {{-- Selector de modo del día --}}
            <div class="mb-6">
                <p class="text-xs text-text-secondary tracking-wider uppercase mb-2">Hoy es día de</p>
                <div class="grid grid-cols-3 gap-2 bg-bg/50 border border-line/[0.06] p-1 rounded-full">
                    @foreach ([
                        'descanso' => ['Descanso', '🛌'],
                        'entreno' => ['Entreno', '💪'],
                        'competencia' => ['Competencia', '🏆'],
                    ] as $key => [$label, $icon])
                        <button
                            type="button"
                            @click="setMode('{{ $key }}')"
                            :disabled="modeLoading"
                            :class="mode === '{{ $key }}' ? 'bg-gold text-black' : 'text-text-secondary hover:text-text-primary'"
                            class="flex items-center justify-center gap-1.5 py-2 px-3 rounded-full text-xs font-semibold transition"
                        >
                            <span>{{ $icon }}</span>
                            <span>{{ $label }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3 text-center mb-6">
                <div class="bg-bg/50 border border-line/[0.06] rounded-xl py-3">
                    <div class="font-serif text-2xl text-gold">{{ count($comidas) }}</div>
                    <div class="text-xs text-text-secondary mt-1">Comidas</div>
                </div>
                <div class="bg-bg/50 border border-line/[0.06] rounded-xl py-3">
                    <div class="font-serif text-2xl text-gold">{{ $totalSupFarma }}</div>
                    <div class="text-xs text-text-secondary mt-1">Sup/Fármacos</div>
                </div>
                <div class="bg-bg/50 border border-line/[0.06] rounded-xl py-3">
                    <div class="font-serif text-2xl text-gold" x-text="fidelidad + '%'"></div>
                    <div class="text-xs text-text-secondary mt-1">Fidelidad hoy</div>
                </div>
            </div>

            @if (count($comidas) > 0)
                <div class="space-y-3">
                    @foreach ($comidas as $c)
                        @php
                            $itemId = $c['id'] ?? \Illuminate\Support\Str::slug($c['nombre'] ?? 'comida-'.$loop->index);
                            $descripcion = $c['descripcion_plan'] ?? null;
                            $opciones = $c['opciones'] ?? [];
                            $tip = $c['tip'] ?? null;
                            $notasComida = $c['notas'] ?? [];
                            $tieneDetalle = $descripcion || count($opciones) > 0 || $tip || count($notasComida) > 0;
                        @endphp
                        <div
                            class="bg-bg/50 border-l-2 rounded-xl transition"
                            :class="{
                                'border-fiel': checks['{{ $itemId }}'] === 'fiel',
                                'border-parcial': checks['{{ $itemId }}'] === 'parcial',
                                'border-nofiel': checks['{{ $itemId }}'] === 'nofiel',
                                'border-line/[0.04]': !checks['{{ $itemId }}'],
                            }"
                        >
                            <div class="flex items-center gap-3 p-3">
                                <div class="w-10 h-10 flex items-center justify-center bg-line/5 rounded-lg text-xl shrink-0">
                                    {{ $c['icono_sugerido'] ?? '🍽️' }}
                                </div>
                                <div
                                    @if ($tieneDetalle) @click="toggleExpand('{{ $itemId }}')" @endif
                                    class="flex-1 min-w-0 {{ $tieneDetalle ? 'cursor-pointer' : '' }}"
                                >
                                    {{-- Hora: editable. Si no tiene, botón para asignarla. --}}
                                    <button
                                        type="button"
                                        @click.stop="openHora('{{ $itemId }}')"
                                        class="text-xs uppercase tracking-wider transition"
                                        :class="horas['{{ $itemId }}'] ? 'text-text-secondary hover:text-gold' : 'text-gold/70 hover:text-gold'"
                                    >
                                        <span x-show="horas['{{ $itemId }}']" x-text="horas['{{ $itemId }}']"></span>
                                        <span x-show="!horas['{{ $itemId }}']">+ asignar hora</span>
                                    </button>
                                    <div class="font-serif text-base truncate flex items-center gap-1.5">
                                        <span class="truncate">{{ $c['nombre'] ?? 'Comida' }}</span>
                                        @if ($tieneDetalle)
                                            <span class="text-text-secondary/50 text-xs shrink-0" x-text="expanded === '{{ $itemId }}' ? '▲' : '▼'"></span>
                                        @endif
                                    </div>
                                    <div
                                        x-show="notes['{{ $itemId }}'] && noteOpen !== '{{ $itemId }}'"
                                        @click.stop="openNote('{{ $itemId }}')"
                                        class="text-xs text-text-secondary italic mt-1 cursor-pointer hover:text-text-primary transition truncate"
                                        x-text="notes['{{ $itemId }}']"
                                    ></div>
                                </div>

                                <button
                                    type="button"
                                    @click="openNote('{{ $itemId }}')"
                                    :class="notes['{{ $itemId }}'] ? 'text-gold' : 'text-text-secondary/40 hover:text-text-secondary'"
                                    class="w-7 h-7 flex items-center justify-center text-sm transition shrink-0"
                                    title="Agregar nota"
                                >
                                    <span x-show="!notes['{{ $itemId }}']">+</span>
                                    <span x-show="notes['{{ $itemId }}']" x-cloak>✎</span>
                                </button>

                                {{-- Toggle de estado compacto (fool-proof): 4 opciones siempre visibles --}}
                                <div class="flex items-center gap-1 shrink-0 bg-line/5 rounded-lg p-0.5">
                                    @foreach ([
                                        'fiel' => ['✓', 'bg-fiel text-black'],
                                        'parcial' => ['~', 'bg-parcial text-black'],
                                        'nofiel' => ['✗', 'bg-nofiel text-white'],
                                        'na' => ['NA', 'bg-line/40 text-text-primary'],
                                    ] as $st => [$icon, $activeClass])
                                        <button
                                            type="button"
                                            @click="setStatus('{{ $itemId }}', '{{ $st }}')"
                                            :disabled="loading['{{ $itemId }}']"
                                            :class="checks['{{ $itemId }}'] === '{{ $st }}'
                                                ? '{{ $activeClass }}'
                                                : 'text-text-secondary/50 hover:text-text-primary'"
                                            class="w-7 h-7 rounded-md flex items-center justify-center {{ $st === 'na' ? 'text-[11px]' : 'text-sm' }} font-bold transition disabled:opacity-40"
                                            title="{{ ['fiel' => 'Fiel', 'parcial' => 'Parcial', 'nofiel' => 'No fiel', 'na' => 'No aplica hoy'][$st] }}"
                                        >{{ $icon }}</button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Detalle expandible: qué comer --}}
                            @if ($tieneDetalle)
                                <div x-show="expanded === '{{ $itemId }}'" x-cloak x-transition.opacity class="px-3 pb-3 -mt-1">
                                    <div class="bg-bg/60 border border-line/[0.06] rounded-lg p-3 space-y-3">
                                        @if ($descripcion)
                                            <p class="text-sm text-text-secondary">{{ $descripcion }}</p>
                                        @endif
                                        @if (count($opciones) > 0)
                                            <div>
                                                <p class="text-[10px] text-text-secondary/70 uppercase tracking-wider mb-1.5">Opciones</p>
                                                <ul class="space-y-1">
                                                    @foreach ($opciones as $op)
                                                        <li class="text-sm text-text-primary flex gap-2">
                                                            <span class="text-gold shrink-0">·</span>
                                                            <span>{{ $op }}</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                        @if (count($notasComida) > 0)
                                            <ul class="space-y-1">
                                                @foreach ($notasComida as $n)
                                                    <li class="text-xs text-text-secondary flex gap-2">
                                                        <span class="text-parcial shrink-0">!</span>
                                                        <span>{{ $n }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                        @if ($tip)
                                            <div class="bg-gold/5 border-l-2 border-gold/40 rounded-r-lg px-3 py-2">
                                                <p class="text-xs text-text-secondary italic">💡 {{ $tip }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            {{-- Editor de hora inline --}}
                            <div x-show="horaOpen === '{{ $itemId }}'" x-cloak x-transition.opacity class="px-3 pb-3">
                                <div class="flex items-center gap-2">
                                    <input
                                        type="time"
                                        x-model="horaDraft"
                                        class="bg-bg border border-line/10 text-text-primary focus:border-gold focus:ring-1 focus:ring-gold rounded-lg px-3 py-1.5 text-sm transition"
                                    >
                                    <button type="button" @click="saveHora('{{ $itemId }}')" :disabled="loading['{{ $itemId }}']"
                                        class="text-xs bg-gold text-black px-4 py-1.5 rounded-full font-semibold hover:bg-gold/90 disabled:opacity-40 transition">Guardar</button>
                                    <button type="button" @click="clearHora('{{ $itemId }}')" :disabled="loading['{{ $itemId }}']"
                                        x-show="horas['{{ $itemId }}']"
                                        class="text-xs text-text-secondary/60 hover:text-nofiel px-2 py-1.5 transition">Sin hora</button>
                                    <button type="button" @click="horaOpen = null"
                                        class="text-xs text-text-secondary/60 hover:text-text-primary px-2 py-1.5 transition">Cancelar</button>
                                </div>
                            </div>

                            {{-- Editor de nota inline --}}
                            <div
                                x-show="noteOpen === '{{ $itemId }}'"
                                x-cloak
                                x-transition.opacity
                                class="px-3 pb-3"
                            >
                                <textarea
                                    x-model="noteDraft"
                                    placeholder="¿Qué pasó? (opcional, ayuda al análisis IA)"
                                    rows="2"
                                    maxlength="500"
                                    class="w-full bg-bg border border-line/10 text-text-primary placeholder-text-secondary/40 focus:border-gold focus:ring-1 focus:ring-gold rounded-lg px-3 py-2 text-sm transition resize-none"
                                ></textarea>
                                <div class="flex items-center justify-between mt-2 gap-3">
                                    <span class="text-xs text-text-secondary/40" x-text="(noteDraft || '').length + '/500'"></span>
                                    <div class="flex gap-2">
                                        <button
                                            type="button"
                                            @click="closeNote()"
                                            class="text-xs text-text-secondary/60 hover:text-text-primary px-3 py-1 transition"
                                        >Cancelar</button>
                                        <button
                                            type="button"
                                            @click="saveNote('{{ $itemId }}')"
                                            :disabled="loading['{{ $itemId }}']"
                                            class="text-xs bg-gold text-black px-4 py-1 rounded-full font-semibold hover:bg-gold/90 disabled:opacity-40 transition"
                                        >Guardar</button>
                                    </div>
                                </div>
                                <p
                                    x-show="!checks['{{ $itemId }}']"
                                    class="text-xs text-text-secondary/60 italic mt-2"
                                >
                                    Marque la comida (Fiel / Parcial / No fiel) antes de guardar la nota.
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <p class="mt-6 text-xs text-text-secondary/60 italic font-serif text-center">
                Toque la comida para ver el detalle · marque Fiel, Parcial o No fiel
            </p>

            @if ($totalSupFarma > 0)
                <div class="mt-8 pt-6 border-t border-line/[0.06]">
                    @foreach ([
                        ['items' => $suplementos, 'label' => '🥤 Suplementos', 'kind' => 'sup'],
                        ['items' => $farmacologia, 'label' => '💊 Farmacología', 'kind' => 'farm'],
                    ] as $section)
                        @if (count($section['items']) > 0)
                            <div class="flex items-center justify-between gap-3 mb-3">
                                <p class="text-xs text-gold tracking-[0.25em] uppercase">{{ $section['label'] }}</p>
                                {{-- Toggle: ¿los suplementos cuentan en la fidelidad? --}}
                                @if ($section['kind'] === 'sup')
                                    <button
                                        type="button"
                                        @click="toggleSupplementsFidelity()"
                                        :disabled="prefLoading"
                                        class="flex items-center gap-2 text-[11px] text-text-secondary hover:text-text-primary transition disabled:opacity-40"
                                        title="Si está activo, marcar suplementos suma a tu % de fidelidad"
                                    >
                                        <span>Cuentan en mi fidelidad</span>
                                        <span
                                            class="relative w-9 h-5 rounded-full transition shrink-0"
                                            :class="supplementsAffect ? 'bg-gold' : 'bg-line/20'"
                                        >
                                            <span
                                                class="absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white transition-transform"
                                                :class="supplementsAffect ? 'translate-x-4' : ''"
                                            ></span>
                                        </span>
                                    </button>
                                @endif
                            </div>
                            <div class="space-y-2 mb-5">
                                @foreach ($section['items'] as $s)
                                    @php
                                        $sid = $s['id'] ?? ('sup-'.\Illuminate\Support\Str::slug($s['nombre'] ?? 'item-'.$loop->index));
                                        $sub = trim(implode(' · ', array_filter([$s['dosis'] ?? null, $s['frecuencia'] ?? null])));
                                    @endphp
                                    <div
                                        class="bg-bg/50 border-l-2 rounded-xl transition"
                                        :class="{
                                            'border-fiel': checks['{{ $sid }}'] === 'fiel',
                                            'border-parcial': checks['{{ $sid }}'] === 'parcial',
                                            'border-nofiel': checks['{{ $sid }}'] === 'nofiel',
                                            'border-line/[0.04]': !checks['{{ $sid }}'],
                                        }"
                                    >
                                        <div class="flex items-center gap-3 p-3">
                                            <div class="flex-1 min-w-0">
                                                <div class="font-serif text-base truncate">{{ $s['nombre'] ?? '' }}</div>
                                                @if ($sub !== '')
                                                    <div class="text-xs text-text-secondary truncate">{{ $sub }}</div>
                                                @endif
                                                @if (! empty($s['nota']))
                                                    <div class="text-xs text-text-secondary/60 italic truncate">{{ $s['nota'] }}</div>
                                                @endif
                                            </div>
                                            {{-- Toggle compacto de estado (igual que las comidas) --}}
                                            <div class="flex items-center gap-1 shrink-0 bg-line/5 rounded-lg p-0.5">
                                                @foreach ([
                                                    'fiel' => ['✓', 'bg-fiel text-black'],
                                                    'parcial' => ['~', 'bg-parcial text-black'],
                                                    'nofiel' => ['✗', 'bg-nofiel text-white'],
                                                    'na' => ['NA', 'bg-line/40 text-text-primary'],
                                                ] as $st => [$icon, $activeClass])
                                                    <button
                                                        type="button"
                                                        @click="setStatus('{{ $sid }}', '{{ $st }}')"
                                                        :disabled="loading['{{ $sid }}']"
                                                        :class="checks['{{ $sid }}'] === '{{ $st }}'
                                                            ? '{{ $activeClass }}'
                                                            : 'text-text-secondary/50 hover:text-text-primary'"
                                                        class="w-7 h-7 rounded-md flex items-center justify-center {{ $st === 'na' ? 'text-[11px]' : 'text-sm' }} font-bold transition disabled:opacity-40"
                                                        title="{{ ['fiel' => 'Fiel', 'parcial' => 'Parcial', 'nofiel' => 'No fiel', 'na' => 'No aplica hoy'][$st] }}"
                                                    >{{ $icon }}</button>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                    <p class="text-xs text-text-secondary/60 italic font-serif text-center">
                        La farmacología nunca cuenta en tu % de fidelidad (es responsabilidad médica).
                    </p>
                </div>
            @endif
        </div>
    </div> {{-- close lg:col-span-2 --}}

    <aside class="space-y-6">
        {{-- Análisis IA semanal --}}
        <div
            x-data="weeklyInsight()"
            x-init="loadFromCache()"
            class="bg-bg-card border border-line/[0.06] rounded-2xl p-6"
        >
            <div class="flex items-baseline justify-between mb-4">
                <p class="text-xs text-gold tracking-[0.25em] uppercase">Análisis IA</p>
                <button
                    type="button"
                    @click="generate(true)"
                    :disabled="loading"
                    class="text-xs text-text-secondary/60 hover:text-gold underline transition disabled:opacity-40"
                >
                    <span x-show="!data && !loading">Generar</span>
                    <span x-show="data && !loading">Regenerar</span>
                    <span x-show="loading" x-cloak>Pensando…</span>
                </button>
            </div>

            <template x-if="!data && !loading && !error">
                <div class="text-center py-6">
                    <div class="text-3xl mb-2 opacity-40 font-serif italic text-gold">~</div>
                    <p class="text-sm text-text-secondary">Pida su análisis semanal cuando esté listo.</p>
                </div>
            </template>

            <template x-if="loading">
                <div class="text-center py-6" x-cloak>
                    <div class="w-10 h-10 mx-auto rounded-full border-2 border-line/10 border-t-gold animate-spin"></div>
                    <p class="text-xs text-text-secondary mt-3">Leyendo su semana…</p>
                </div>
            </template>

            <template x-if="error">
                <p class="text-sm text-nofiel" x-text="error" x-cloak></p>
            </template>

            <template x-if="data && !loading">
                <div x-cloak>
                    <p
                        class="font-serif text-lg leading-snug mb-4"
                        :class="{
                            'text-fiel': data.tono === 'celebracion',
                            'text-gold': data.tono === 'motivacion',
                            'text-text-primary': data.tono === 'apoyo',
                        }"
                        x-text="data.insight_principal"
                    ></p>

                    <template x-if="data.patrones_detectados && data.patrones_detectados.length">
                        <div class="mb-4">
                            <p class="text-xs text-text-secondary uppercase tracking-wider mb-2">Patrones</p>
                            <ul class="space-y-1.5">
                                <template x-for="p in data.patrones_detectados" :key="p">
                                    <li class="text-xs text-text-primary flex gap-2">
                                        <span class="text-gold">·</span>
                                        <span x-text="p"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>

                    <div class="bg-bg/50 border-l-2 border-gold rounded-r-lg p-3">
                        <p class="text-xs text-gold tracking-wider uppercase mb-1">Esta semana</p>
                        <p class="text-sm text-text-primary italic font-serif" x-text="data.recomendacion"></p>
                    </div>
                </div>
            </template>
        </div>

        {{-- Heatmap rango variable --}}
        @if (! empty($heatmap))
            @php
                // Padding al lunes: dow del primer día (1=lun..7=dom) determina cuántas celdas vacías al inicio.
                $firstDow = $heatmap[0]['dow'];
                $padStart = $firstDow - 1;
                $rangeLabel = ['7' => 'Su semana', '30' => 'Su mes', '90' => 'Sus 90 días'][(string) $range] ?? 'Su mes';
                $isCompact = $range > 7;
            @endphp
            <div x-data="dayDetail()" class="bg-bg-card border border-line/[0.06] rounded-2xl p-6">
                <div class="flex items-baseline justify-between mb-4">
                    <p class="text-xs text-gold tracking-[0.25em] uppercase">{{ $rangeLabel }}</p>
                </div>

                {{-- Selector de rango --}}
                <div class="grid grid-cols-3 gap-1 bg-bg/50 border border-line/[0.06] p-1 rounded-full mb-5 text-xs">
                    @foreach (['7' => 'Semana', '30' => 'Mes', '90' => '90 días'] as $r => $lbl)
                        <a
                            href="{{ route('dashboard', ['range' => $r]) }}"
                            class="text-center py-1.5 rounded-full font-semibold transition {{ (int) $r === $range ? 'bg-gold text-black' : 'text-text-secondary hover:text-text-primary' }}"
                        >{{ $lbl }}</a>
                    @endforeach
                </div>

                {{-- Stats --}}
                @if ($heatmapStats['dias_con_data'] > 0)
                    <div class="grid grid-cols-4 gap-2 mb-5 text-center">
                        <div class="bg-bg/50 border border-line/[0.06] rounded-lg py-2">
                            <div class="font-serif text-base text-gold">{{ $heatmapStats['promedio'] }}%</div>
                            <div class="text-[10px] text-text-secondary mt-0.5">Promedio</div>
                        </div>
                        <div class="bg-bg/50 border border-line/[0.06] rounded-lg py-2">
                            <div class="font-serif text-base text-fiel">{{ $heatmapStats['dias_perfectos'] }}</div>
                            <div class="text-[10px] text-text-secondary mt-0.5">Perfectos</div>
                        </div>
                        <div class="bg-bg/50 border border-line/[0.06] rounded-lg py-2">
                            <div class="font-serif text-base text-gold">{{ $heatmapStats['racha_actual'] }}</div>
                            <div class="text-[10px] text-text-secondary mt-0.5">Racha</div>
                        </div>
                        <div class="bg-bg/50 border border-line/[0.06] rounded-lg py-2">
                            <div class="font-serif text-base text-text-primary">{{ $heatmapStats['racha_max'] }}</div>
                            <div class="text-[10px] text-text-secondary mt-0.5">Máxima</div>
                        </div>
                    </div>
                @endif

                {{-- Header de días (L M X J V S D) --}}
                <div class="grid grid-cols-7 gap-1 mb-1">
                    @foreach (['L', 'M', 'X', 'J', 'V', 'S', 'D'] as $dl)
                        <div class="text-center text-[10px] uppercase tracking-wider text-text-secondary/50">{{ $dl }}</div>
                    @endforeach
                </div>

                <div class="grid grid-cols-7 gap-1">
                    @for ($i = 0; $i < $padStart; $i++)
                        <div></div>
                    @endfor

                    @foreach ($heatmap as $day)
                        @php
                            $f = $day['fidelidad'];
                            if ($f === null) {
                                $bg = 'bg-line/[0.03]';
                                $border = 'border-line/[0.04]';
                                $textColor = 'text-text-secondary/40';
                            } elseif ($f === 100) {
                                $bg = 'bg-gold/25';
                                $border = 'border-gold/50';
                                $textColor = 'text-gold';
                            } elseif ($f >= 67) {
                                $bg = 'bg-fiel/20';
                                $border = 'border-fiel/40';
                                $textColor = 'text-fiel';
                            } elseif ($f >= 34) {
                                $bg = 'bg-parcial/20';
                                $border = 'border-parcial/40';
                                $textColor = 'text-parcial';
                            } else {
                                $bg = 'bg-nofiel/20';
                                $border = 'border-nofiel/40';
                                $textColor = 'text-nofiel';
                            }
                            $todayRing = $day['is_today'] ? 'ring-2 ring-gold/60 ring-offset-1 ring-offset-bg-card' : '';
                        @endphp
                        <button
                            type="button"
                            @click="open('{{ $day['date'] }}')"
                            class="group"
                            title="{{ $day['date'] }}{{ $f !== null ? ' — '.$f.'%' : ' — sin checks' }}"
                        >
                            <div
                                class="aspect-square rounded-md border flex flex-col items-center justify-center {{ $bg }} {{ $border }} {{ $todayRing }} transition group-hover:border-line/30 cursor-pointer"
                            >
                                <div class="text-[9px] text-text-secondary/60 leading-none">{{ $day['day'] }}</div>
                                @if (! $isCompact)
                                    <div class="font-serif text-xs {{ $textColor }} mt-0.5 leading-none">
                                        {{ $f !== null ? $f.'%' : '·' }}
                                    </div>
                                @else
                                    <div class="font-serif text-[10px] {{ $textColor }} mt-0.5 leading-none">
                                        {{ $f !== null ? '·' : '' }}
                                    </div>
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>

                {{-- Leyenda compacta --}}
                @if ($isCompact)
                    <div class="flex items-center justify-center gap-3 mt-4 text-[10px] text-text-secondary/60">
                        <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-nofiel/30 border border-nofiel/40"></span>Bajo</span>
                        <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-parcial/30 border border-parcial/40"></span>Medio</span>
                        <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-fiel/30 border border-fiel/40"></span>Alto</span>
                        <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-gold/30 border border-gold/50"></span>100%</span>
                    </div>
                @endif

                @if ($heatmapStats['dias_con_data'] === 0)
                    <p class="text-xs text-text-secondary/60 text-center mt-4 italic font-serif">
                        Su racha empieza con el primer check de hoy.
                    </p>
                @endif

                {{-- Modal detalle del día --}}
                <div
                    x-show="showing"
                    x-cloak
                    x-transition.opacity
                    @keydown.escape.window="close()"
                    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/70 backdrop-blur-sm p-4"
                    @click.self="close()"
                >
                    <div class="w-full max-w-md bg-bg-card border border-line/[0.08] rounded-2xl p-6 max-h-[85vh] overflow-y-auto">
                        <div class="flex items-start justify-between gap-4 mb-5">
                            <div>
                                <p class="text-xs text-gold tracking-[0.25em] uppercase mb-1" x-text="data?.mode || ''"></p>
                                <h3 class="font-serif text-xl capitalize" x-text="data?.date_label || ''"></h3>
                            </div>
                            <button @click="close()" class="text-text-secondary hover:text-text-primary text-2xl leading-none transition">×</button>
                        </div>

                        <template x-if="loading">
                            <div class="text-center py-8">
                                <div class="w-8 h-8 mx-auto rounded-full border-2 border-line/10 border-t-gold animate-spin"></div>
                            </div>
                        </template>

                        <template x-if="!loading && data">
                            <div>
                                <div class="bg-bg/50 border border-line/[0.06] rounded-xl py-3 text-center mb-5">
                                    <div class="font-serif text-3xl text-gold" x-text="data.fidelidad + '%'"></div>
                                    <div class="text-xs text-text-secondary mt-1">Fidelidad del día</div>
                                </div>

                                <div class="space-y-2">
                                    <template x-for="item in data.items" :key="item.item_id">
                                        <div
                                            class="bg-bg/50 border-l-2 rounded-lg p-3"
                                            :class="{
                                                'border-fiel': item.status === 'fiel',
                                                'border-parcial': item.status === 'parcial',
                                                'border-nofiel': item.status === 'nofiel',
                                                'border-line/[0.04]': !item.status || item.status === 'na',
                                            }"
                                        >
                                            <div class="flex items-center gap-3" :class="item.status === 'na' ? 'opacity-50' : ''">
                                                <div class="w-9 h-9 flex items-center justify-center bg-line/5 rounded-lg text-base shrink-0" x-text="item.icono"></div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-[10px] text-text-secondary uppercase tracking-wider" x-text="item.hora || '—'"></div>
                                                    <div class="font-serif text-sm truncate" x-text="item.nombre"></div>
                                                </div>
                                                <div
                                                    class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                                                    :class="{
                                                        'bg-fiel text-black': item.status === 'fiel',
                                                        'bg-parcial text-black': item.status === 'parcial',
                                                        'bg-nofiel text-white': item.status === 'nofiel',
                                                        'bg-line/40 text-text-primary': item.status === 'na',
                                                        'border border-line/15 text-text-secondary/50': !item.status,
                                                    }"
                                                    x-text="{ fiel: '✓', parcial: '~', nofiel: '✗', na: 'NA' }[item.status] || ''"
                                                ></div>
                                            </div>
                                            <template x-if="item.note">
                                                <p class="text-xs text-text-secondary italic font-serif mt-2 pl-12" x-text="item.note"></p>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('plan.destroy') }}" method="POST" onsubmit="return confirm('¿Eliminar el plan actual y subir uno nuevo?');" class="text-center">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-xs text-text-secondary/60 hover:text-nofiel underline transition">
                Reemplazar plan
            </button>
        </form>
    </aside>
    </div> {{-- close grid lg:grid-cols-3 --}}

        <script>
            function dashboard(initialChecks, initialNotes, fidelidad, totalComidas, mode, initialHoras, supplementsAffect) {
                return {
                    checks: initialChecks,
                    notes: initialNotes,
                    horas: initialHoras || {},
                    fidelidad: fidelidad,
                    totalComidas: totalComidas,
                    mode: mode,
                    modeLoading: false,
                    loading: {},
                    noteOpen: null,
                    noteDraft: '',
                    expanded: null,
                    horaOpen: null,
                    horaDraft: '',
                    supplementsAffect: supplementsAffect,
                    prefLoading: false,
                    async toggleSupplementsFidelity() {
                        if (this.prefLoading) return;
                        this.prefLoading = true;
                        const next = !this.supplementsAffect;
                        try {
                            const res = await fetch('{{ route('prefs.supplementsFidelity') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                },
                                body: JSON.stringify({ enabled: next }),
                            });
                            if (!res.ok) throw new Error('HTTP ' + res.status);
                            // Reload para recalcular fidelidad de hoy y el heatmap histórico.
                            window.location.reload();
                        } catch (e) {
                            console.error('pref toggle failed', e);
                            alert('No se pudo cambiar la preferencia. Reintente.');
                            this.prefLoading = false;
                        }
                    },
                    toggleExpand(itemId) {
                        this.expanded = this.expanded === itemId ? null : itemId;
                    },
                    openHora(itemId) {
                        this.horaOpen = this.horaOpen === itemId ? null : itemId;
                        this.horaDraft = this.horas[itemId] || '';
                    },
                    async saveHora(itemId) {
                        await this.sendHora(itemId, this.horaDraft || null);
                    },
                    async clearHora(itemId) {
                        await this.sendHora(itemId, null);
                    },
                    async sendHora(itemId, hora) {
                        this.loading[itemId] = true;
                        try {
                            const res = await fetch('{{ route('mealtime.store') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                },
                                body: JSON.stringify({ item_id: itemId, hora: hora }),
                            });
                            const json = await res.json();
                            if (!res.ok) throw new Error(json.error || 'HTTP ' + res.status);
                            if (json.hora) {
                                this.horas[itemId] = json.hora;
                            } else {
                                delete this.horas[itemId];
                            }
                            this.horaOpen = null;
                        } catch (e) {
                            console.error('save hora failed', e);
                            alert(e.message || 'No se pudo guardar la hora.');
                        } finally {
                            this.loading[itemId] = false;
                        }
                    },
                    setStatus(itemId, status) {
                        // Toggle: si ya está en ese estado, lo desmarca.
                        const next = this.checks[itemId] === status ? null : status;
                        this.send(itemId, next);
                    },
                    openNote(itemId) {
                        this.noteOpen = itemId;
                        this.noteDraft = this.notes[itemId] || '';
                    },
                    closeNote() {
                        this.noteOpen = null;
                        this.noteDraft = '';
                    },
                    async saveNote(itemId) {
                        if (!this.checks[itemId]) {
                            alert('Marque la comida (✓ ~ ✗) antes de guardar la nota.');
                            return;
                        }
                        this.loading[itemId] = true;
                        try {
                            const res = await fetch('{{ route('checks.store') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                },
                                body: JSON.stringify({
                                    item_id: itemId,
                                    only_note: true,
                                    note: this.noteDraft,
                                }),
                            });
                            const json = await res.json();
                            if (!res.ok) throw new Error(json.error || 'HTTP ' + res.status);
                            if (this.noteDraft) {
                                this.notes[itemId] = this.noteDraft;
                            } else {
                                delete this.notes[itemId];
                            }
                            this.closeNote();
                        } catch (e) {
                            console.error('save note failed', e);
                            alert(e.message || 'No se pudo guardar la nota.');
                        } finally {
                            this.loading[itemId] = false;
                        }
                    },
                    async setMode(next) {
                        if (next === this.mode || this.modeLoading) return;
                        this.modeLoading = true;
                        try {
                            const res = await fetch('{{ route('mode.store') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                },
                                body: JSON.stringify({ mode: next }),
                            });
                            if (!res.ok) throw new Error('HTTP ' + res.status);
                            // Reload para que el server recalcule comidas con el modo nuevo
                            window.location.reload();
                        } catch (e) {
                            console.error('mode failed', e);
                            alert('No se pudo cambiar el modo. Reintente.');
                            this.modeLoading = false;
                        }
                    },
                    cycle(itemId) {
                        const order = [null, 'fiel', 'parcial', 'nofiel'];
                        const current = this.checks[itemId] ?? null;
                        const next = order[(order.indexOf(current) + 1) % order.length];
                        this.send(itemId, next);
                    },
                    async send(itemId, status) {
                        this.loading[itemId] = true;
                        try {
                            const res = await fetch('{{ route('checks.store') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                },
                                body: JSON.stringify({ item_id: itemId, status: status }),
                            });
                            if (!res.ok) throw new Error('HTTP ' + res.status);
                            const json = await res.json();
                            if (json.status) {
                                this.checks[itemId] = json.status;
                            } else {
                                delete this.checks[itemId];
                                delete this.notes[itemId]; // backend ya borró el check entero
                            }
                            this.fidelidad = json.fidelidad;
                        } catch (e) {
                            console.error('check failed', e);
                            alert('No se pudo guardar. Reintente.');
                        } finally {
                            this.loading[itemId] = false;
                        }
                    },
                    iconFor(status) {
                        return { fiel: '✓', parcial: '~', nofiel: '✗' }[status] ?? '';
                    }
                }
            }

            function dayDetail() {
                return {
                    showing: false,
                    loading: false,
                    data: null,
                    async open(date) {
                        this.showing = true;
                        this.loading = true;
                        this.data = null;
                        try {
                            const res = await fetch('/api/day/' + date, {
                                headers: { 'Accept': 'application/json' },
                            });
                            if (!res.ok) throw new Error('HTTP ' + res.status);
                            this.data = await res.json();
                        } catch (e) {
                            console.error('day detail failed', e);
                            alert('No se pudo cargar el detalle del día.');
                            this.showing = false;
                        } finally {
                            this.loading = false;
                        }
                    },
                    close() {
                        this.showing = false;
                        this.data = null;
                    },
                }
            }

            function weeklyInsight() {
                return {
                    data: null,
                    loading: false,
                    error: null,
                    cacheKey: 'pm.insight.weekly',
                    cacheDateKey: 'pm.insight.weekly.date',
                    loadFromCache() {
                        // Solo restaura si fue de hoy (server cachea 24h igualmente)
                        const today = new Date().toISOString().slice(0, 10);
                        if (localStorage.getItem(this.cacheDateKey) === today) {
                            try {
                                this.data = JSON.parse(localStorage.getItem(this.cacheKey));
                            } catch (e) { /* ignore */ }
                        }
                    },
                    async generate(force = false) {
                        if (this.loading) return;
                        this.loading = true;
                        this.error = null;
                        try {
                            const url = '{{ route('insight.weekly') }}' + (force ? '?refresh=1' : '');
                            const res = await fetch(url, {
                                headers: { 'Accept': 'application/json' },
                            });
                            const json = await res.json();
                            if (!res.ok) throw new Error(json.error || 'HTTP ' + res.status);
                            this.data = json;
                            const today = new Date().toISOString().slice(0, 10);
                            localStorage.setItem(this.cacheKey, JSON.stringify(json));
                            localStorage.setItem(this.cacheDateKey, today);
                        } catch (e) {
                            console.error('insight failed', e);
                            this.error = e.message || 'No pudimos generar su análisis.';
                        } finally {
                            this.loading = false;
                        }
                    }
                }
            }
        </script>
    @endif
</x-app-layout>
