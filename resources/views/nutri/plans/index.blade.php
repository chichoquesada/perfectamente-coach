<x-app-layout>
    <x-slot name="header">
        <h1 class="font-serif text-2xl">Planes</h1>
        <p class="text-sm text-text-secondary mt-1">Su biblioteca de planes nutricionales. Cree uno y luego asígnelo a un paciente.</p>
    </x-slot>

    @if (session('status'))
        <div class="bg-fiel/10 border border-fiel/30 text-fiel text-sm rounded-xl p-3 mb-4">
            {{ session('status') }}
        </div>
    @endif

    <div class="bg-bg-card border border-white/[0.06] rounded-2xl p-6 sm:p-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="font-serif text-xl">Mis planes</h2>
            <a href="{{ route('nutri.plans.create') }}"
               class="bg-gold text-black px-4 py-2 rounded-full font-bold text-sm hover:bg-gold/90 transition">
                Nuevo plan
            </a>
        </div>

        @if ($plans->isEmpty())
            <div class="text-center py-12">
                <div class="text-5xl font-serif italic text-gold mb-4">~</div>
                <p class="text-text-secondary text-sm max-w-sm mx-auto mb-6">
                    Aún no tiene planes. Cree uno y úselo como plantilla para sus pacientes.
                </p>
                <a href="{{ route('nutri.plans.create') }}"
                   class="inline-flex items-center gap-2 bg-gold text-black px-5 py-2.5 rounded-full font-bold text-sm hover:bg-gold/90 transition">
                    Crear primer plan <span aria-hidden="true">→</span>
                </a>
            </div>
        @else
            <div class="space-y-2">
                @foreach ($plans as $p)
                    @php
                        $name = $p->extracted_data['paciente']['nombre'] ?? 'Plan sin nombre';
                        $obj = $p->extracted_data['objetivos']['principal'] ?? null;
                        $comidasCount = count($p->extracted_data['comidas'] ?? []);
                    @endphp
                    <div class="flex items-center justify-between bg-bg/50 border border-white/[0.04] rounded-xl p-3 gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="font-serif text-base truncate">{{ $name }}</div>
                            <div class="text-xs text-text-secondary truncate">
                                {{ $obj ?: 'Sin objetivo' }} · {{ $comidasCount }} {{ $comidasCount === 1 ? 'comida' : 'comidas' }}
                                · {{ $p->created_at->isoFormat('D MMM YYYY') }}
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ route('nutri.plans.edit', $p) }}"
                               class="text-xs px-3 py-1.5 rounded-full border border-white/10 text-text-secondary hover:text-text-primary hover:border-white/20 transition">
                                Editar
                            </a>
                            <form method="POST" action="{{ route('nutri.plans.duplicate', $p) }}">
                                @csrf
                                <button type="submit"
                                    class="text-xs px-3 py-1.5 rounded-full border border-gold/30 text-gold hover:bg-gold/10 transition">
                                    Duplicar
                                </button>
                            </form>
                            <form method="POST" action="{{ route('nutri.plans.destroy', $p) }}"
                                  onsubmit="return confirm('¿Eliminar el plan {{ $name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs px-3 py-1.5 rounded-full border border-white/10 text-text-secondary/60 hover:text-evitado hover:border-evitado/30 transition">
                                    Eliminar
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
