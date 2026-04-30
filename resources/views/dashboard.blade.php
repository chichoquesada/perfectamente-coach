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
        $paciente = $data['paciente']['nombre'] ?? null;
        $objetivo = $data['objetivos']['principal'] ?? null;
        $metodologia = $data['metodologia'] ?? null;
        $suplementos = $data['suplementos_diarios'] ?? [];
        // $comidas, $mode, $checksToday, $fidelidad vienen del controller
    @endphp

    @if (! $plan)
        <div class="bg-bg-card border border-white/[0.06] rounded-2xl p-8 text-center">
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
        <div
            x-data="dashboard({{ \Illuminate\Support\Js::from($checksToday) }}, {{ $fidelidad }}, {{ count($comidas) }}, '{{ $mode }}')"
            class="bg-bg-card border border-white/[0.06] rounded-2xl p-6 sm:p-8 mb-6"
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
                    <span class="shrink-0 text-xs px-3 py-1 bg-white/5 border border-white/10 rounded-full text-text-secondary">
                        {{ $metodologia }}
                    </span>
                @endif
            </div>

            {{-- Selector de modo del día --}}
            <div class="mb-6">
                <p class="text-xs text-text-secondary tracking-wider uppercase mb-2">Hoy es día de</p>
                <div class="grid grid-cols-3 gap-2 bg-bg/50 border border-white/[0.06] p-1 rounded-full">
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
                <div class="bg-bg/50 border border-white/[0.06] rounded-xl py-3">
                    <div class="font-serif text-2xl text-gold">{{ count($comidas) }}</div>
                    <div class="text-xs text-text-secondary mt-1">Comidas</div>
                </div>
                <div class="bg-bg/50 border border-white/[0.06] rounded-xl py-3">
                    <div class="font-serif text-2xl text-gold">{{ count($suplementos) }}</div>
                    <div class="text-xs text-text-secondary mt-1">Suplementos</div>
                </div>
                <div class="bg-bg/50 border border-white/[0.06] rounded-xl py-3">
                    <div class="font-serif text-2xl text-gold" x-text="fidelidad + '%'"></div>
                    <div class="text-xs text-text-secondary mt-1">Fidelidad hoy</div>
                </div>
            </div>

            @if (count($comidas) > 0)
                <div class="space-y-2">
                    @foreach ($comidas as $c)
                        @php
                            $itemId = $c['id'] ?? \Illuminate\Support\Str::slug($c['nombre'] ?? 'comida-'.$loop->index);
                        @endphp
                        <div
                            class="flex items-center gap-3 p-3 bg-bg/50 border-l-2 rounded-xl transition"
                            :class="{
                                'border-fiel': checks['{{ $itemId }}'] === 'fiel',
                                'border-parcial': checks['{{ $itemId }}'] === 'parcial',
                                'border-nofiel': checks['{{ $itemId }}'] === 'nofiel',
                                'border-white/[0.04]': !checks['{{ $itemId }}'],
                            }"
                        >
                            <div class="w-10 h-10 flex items-center justify-center bg-white/5 rounded-lg text-xl shrink-0">
                                {{ $c['icono_sugerido'] ?? '🍽️' }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs text-text-secondary uppercase tracking-wider">
                                    {{ $c['hora'] ?? '—' }}
                                </div>
                                <div class="font-serif text-base truncate">
                                    {{ $c['nombre'] ?? 'Comida' }}
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="cycle('{{ $itemId }}')"
                                :disabled="loading['{{ $itemId }}']"
                                :class="{
                                    'bg-fiel border-fiel text-black': checks['{{ $itemId }}'] === 'fiel',
                                    'bg-parcial border-parcial text-black': checks['{{ $itemId }}'] === 'parcial',
                                    'bg-nofiel border-nofiel text-white': checks['{{ $itemId }}'] === 'nofiel',
                                    'border-white/15 text-text-secondary hover:border-white/30': !checks['{{ $itemId }}'],
                                    'opacity-40': loading['{{ $itemId }}']
                                }"
                                class="w-9 h-9 rounded-full border flex items-center justify-center text-xs font-bold transition shrink-0"
                                title="Click para marcar"
                            >
                                <span x-show="!loading['{{ $itemId }}']" x-text="iconFor(checks['{{ $itemId }}'])"></span>
                                <span x-show="loading['{{ $itemId }}']" x-cloak>…</span>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif

            <p class="mt-6 text-xs text-text-secondary/60 italic font-serif text-center">
                Click en el círculo: vacío → Fiel → Parcial → No fiel → vacío
            </p>
        </div>

        <form action="{{ route('plan.destroy') }}" method="POST" onsubmit="return confirm('¿Eliminar el plan actual y subir uno nuevo?');" class="text-center">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-xs text-text-secondary/60 hover:text-nofiel underline transition">
                Reemplazar plan
            </button>
        </form>

        <script>
            function dashboard(initial, fidelidad, totalComidas, mode) {
                return {
                    checks: initial,
                    fidelidad: fidelidad,
                    totalComidas: totalComidas,
                    mode: mode,
                    modeLoading: false,
                    loading: {},
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
        </script>
    @endif
</x-app-layout>
