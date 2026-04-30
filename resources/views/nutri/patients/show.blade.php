<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs text-gold tracking-[0.25em] uppercase mb-1">Paciente</p>
                <h1 class="font-serif text-2xl truncate">{{ $patient->name ?? $pivot->invitation_email }}</h1>
                <p class="text-sm text-text-secondary truncate">{{ $patient->email }}</p>
            </div>
            <a href="{{ route('nutri.dashboard') }}" class="shrink-0 text-xs text-text-secondary hover:text-text-primary transition">
                ← Volver
            </a>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="bg-fiel/10 border border-fiel/30 text-fiel text-sm rounded-xl p-3 mb-4">
            {{ session('status') }}
        </div>
    @endif

    {{-- Status badge + plan --}}
    <div class="bg-bg-card border border-white/[0.06] rounded-2xl p-6 mb-6">
        <div class="flex flex-wrap items-center gap-3 mb-4">
            <span class="text-xs px-3 py-1 rounded-full
                @if ($pivot->status === 'active') bg-fiel/15 text-fiel border border-fiel/30
                @elseif ($pivot->status === 'invited') bg-parcial/15 text-parcial border border-parcial/30
                @else bg-white/5 text-text-secondary border border-white/10
                @endif">
                {{ ['active' => 'Activo', 'invited' => 'Invitado', 'archived' => 'Archivado'][$pivot->status] ?? $pivot->status }}
            </span>
            @if ($pivot->accepted_at)
                <span class="text-xs text-text-secondary">Aceptó {{ \Carbon\Carbon::parse($pivot->accepted_at)->isoFormat('D MMM YYYY') }}</span>
            @elseif ($pivot->invited_at)
                <span class="text-xs text-text-secondary">Invitado {{ \Carbon\Carbon::parse($pivot->invited_at)->isoFormat('D MMM YYYY') }}</span>
            @endif
        </div>

        @if ($plan)
            <div>
                <p class="text-xs text-text-secondary tracking-wider uppercase mb-1">Plan activo</p>
                <p class="font-serif text-base">
                    {{ $plan->extracted_data['paciente']['nombre'] ?? 'Plan sin nombre' }}
                </p>
                @if ($obj = $plan->extracted_data['objetivos']['principal'] ?? null)
                    <p class="text-sm text-text-secondary mt-1">{{ $obj }}</p>
                @endif
            </div>
        @else
            <p class="text-sm text-text-secondary">Sin plan activo todavía.</p>
        @endif
    </div>

    {{-- Stats --}}
    @if ($plan)
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            <div class="bg-bg-card border border-white/[0.06] rounded-xl p-4 text-center">
                <div class="font-serif text-2xl text-gold">{{ $stats['promedio'] }}%</div>
                <div class="text-xs text-text-secondary mt-1">Adherencia 30d</div>
            </div>
            <div class="bg-bg-card border border-white/[0.06] rounded-xl p-4 text-center">
                <div class="font-serif text-2xl text-text-primary">{{ $stats['racha_actual'] }}</div>
                <div class="text-xs text-text-secondary mt-1">Racha actual</div>
            </div>
            <div class="bg-bg-card border border-white/[0.06] rounded-xl p-4 text-center">
                <div class="font-serif text-2xl text-text-primary">{{ $stats['racha_max'] }}</div>
                <div class="text-xs text-text-secondary mt-1">Racha máxima</div>
            </div>
            <div class="bg-bg-card border border-white/[0.06] rounded-xl p-4 text-center">
                <div class="font-serif text-2xl text-gold">{{ $stats['dias_perfectos'] }}</div>
                <div class="text-xs text-text-secondary mt-1">Días 100%</div>
            </div>
        </div>

        {{-- Heatmap 30d simple --}}
        <div class="bg-bg-card border border-white/[0.06] rounded-2xl p-6 mb-6">
            <p class="text-xs text-gold tracking-[0.25em] uppercase mb-3">Últimos 30 días</p>
            @php
                $padDow = $heatmap[0]['dow'] ?? 1;
                $padding = $padDow - 1;
            @endphp
            <div class="grid grid-cols-7 gap-1">
                @for ($i = 0; $i < $padding; $i++)
                    <div class="aspect-square"></div>
                @endfor
                @foreach ($heatmap as $cell)
                    @php
                        $f = $cell['fidelidad'];
                        $cls = match (true) {
                            $f === null => 'bg-white/[0.04] border border-white/[0.04]',
                            $f === 100 => 'bg-gold border border-gold/60',
                            $f >= 67 => 'bg-fiel/40 border border-fiel/40',
                            $f >= 34 => 'bg-parcial/40 border border-parcial/40',
                            default => 'bg-white/[0.06] border border-white/[0.08]',
                        };
                    @endphp
                    <div class="aspect-square rounded {{ $cls }} {{ $cell['is_today'] ? 'ring-1 ring-gold' : '' }}"
                         title="{{ $cell['date'] }} · {{ $f === null ? 'sin data' : $f.'%' }}">
                    </div>
                @endforeach
            </div>
            @if ($stats['dias_con_data'] === 0)
                <p class="text-xs text-text-secondary mt-3">Sin checks registrados todavía.</p>
            @endif
        </div>
    @endif

    {{-- Notas internas --}}
    <div class="bg-bg-card border border-white/[0.06] rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-serif text-xl">Notas internas</h2>
            <span class="text-xs text-text-secondary">Solo visibles para usted</span>
        </div>

        <form method="POST" action="{{ route('nutri.patients.notes.store', $patient) }}" class="mb-6">
            @csrf
            <textarea name="body" rows="3" required maxlength="5000"
                placeholder="Ej: Consulta del {{ now()->isoFormat('D MMM') }}: bajó 1kg. Subir carbos en días de entreno."
                class="w-full bg-bg/50 border border-white/[0.08] rounded-xl p-3 text-sm text-text-primary placeholder:text-text-secondary/50 focus:outline-none focus:border-gold/50 resize-none">{{ old('body') }}</textarea>
            <x-input-error :messages="$errors->get('body')" class="mt-2" />
            <div class="flex justify-end mt-3">
                <x-primary-button>Guardar nota</x-primary-button>
            </div>
        </form>

        @if ($notes->isEmpty())
            <p class="text-sm text-text-secondary text-center py-6">Sin notas todavía.</p>
        @else
            <div class="space-y-3">
                @foreach ($notes as $note)
                    <div class="bg-bg/40 border border-white/[0.04] rounded-xl p-4">
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <span class="text-xs text-text-secondary">
                                {{ $note->created_at->isoFormat('D MMM YYYY · HH:mm') }}
                            </span>
                            <form method="POST" action="{{ route('nutri.patients.notes.destroy', [$patient, $note]) }}"
                                onsubmit="return confirm('¿Eliminar esta nota?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-text-secondary/60 hover:text-evitado transition">
                                    Eliminar
                                </button>
                            </form>
                        </div>
                        <p class="text-sm text-text-primary whitespace-pre-line leading-relaxed">{{ $note->body }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
