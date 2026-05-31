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

    <div x-data="{ assignOpen: false, assignPlan: { id: null, name: '' } }"
         class="bg-bg-card border border-line/[0.06] rounded-2xl p-6 sm:p-8">
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
                    <div class="flex items-center justify-between bg-bg/50 border border-line/[0.04] rounded-xl p-3 gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="font-serif text-base truncate">{{ $name }}</div>
                            <div class="text-xs text-text-secondary truncate">
                                {{ $obj ?: 'Sin objetivo' }} · {{ $comidasCount }} {{ $comidasCount === 1 ? 'comida' : 'comidas' }}
                                · {{ $p->created_at->isoFormat('D MMM YYYY') }}
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button"
                                @click="assignPlan = { id: {{ $p->id }}, name: @js($name) }; assignOpen = true"
                                class="text-xs px-3 py-1.5 rounded-full bg-gold/15 border border-gold/30 text-gold font-semibold hover:bg-gold/25 transition">
                                Asignar
                            </button>
                            <a href="{{ route('nutri.plans.edit', $p) }}"
                               class="text-xs px-3 py-1.5 rounded-full border border-line/10 text-text-secondary hover:text-text-primary hover:border-line/20 transition">
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
                                <button type="submit" class="text-xs px-3 py-1.5 rounded-full border border-line/10 text-text-secondary/60 hover:text-evitado hover:border-evitado/30 transition">
                                    Eliminar
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Modal: asignar plan a paciente --}}
        <div x-cloak x-show="assignOpen"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-transition.opacity>
            <div class="absolute inset-0 bg-black/70" @click="assignOpen = false"></div>
            <div class="relative bg-bg-card border border-line/10 rounded-2xl p-6 w-full max-w-md shadow-2xl"
                 x-transition>
                <h3 class="font-serif text-lg mb-1">Asignar a paciente</h3>
                <p class="text-xs text-text-secondary mb-5">
                    Se creará una copia activa de <span class="text-text-primary font-medium" x-text="assignPlan.name"></span>
                    para el paciente. Su nombre será “Nombre del paciente - Plan N”.
                </p>

                @if ($patients->isEmpty())
                    <div class="text-sm text-text-secondary bg-bg/50 border border-line/[0.06] rounded-xl p-4 mb-4">
                        No tiene pacientes activos todavía. Invite a un paciente desde el panel para poder asignarle un plan.
                    </div>
                    <div class="flex justify-end">
                        <button type="button" @click="assignOpen = false"
                            class="text-sm px-4 py-2 rounded-full border border-line/10 text-text-secondary hover:text-text-primary transition">
                            Cerrar
                        </button>
                    </div>
                @else
                    <form method="POST" :action="'{{ url('nutri/planes') }}/' + assignPlan.id + '/asignar'">
                        @csrf
                        <label class="block text-xs text-text-secondary mb-2">Paciente</label>
                        <select name="patient_id" required
                            class="w-full bg-bg border border-line/10 rounded-xl px-3 py-2.5 text-sm text-text-primary focus:border-gold/50 focus:outline-none mb-5">
                            <option value="" disabled selected>Elija un paciente…</option>
                            @foreach ($patients as $patient)
                                <option value="{{ $patient->id }}">{{ $patient->name }} — {{ $patient->email }}</option>
                            @endforeach
                        </select>
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="assignOpen = false"
                                class="text-sm px-4 py-2 rounded-full border border-line/10 text-text-secondary hover:text-text-primary transition">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="text-sm px-4 py-2 rounded-full bg-gold text-black font-bold hover:bg-gold/90 transition">
                                Asignar plan
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
