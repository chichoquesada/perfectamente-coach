<x-app-layout>
    <x-slot name="header">
        <h1 class="font-serif text-2xl">Su cartera</h1>
        <p class="text-sm text-text-secondary mt-1">Adherencia y estado de sus pacientes en una sola pantalla.</p>
    </x-slot>

    @if (session('status'))
        <div class="bg-fiel/10 border border-fiel/30 text-fiel text-sm rounded-xl p-3 mb-4">
            {{ session('status') }}
        </div>
    @endif

    <div x-data="{ open: false }" x-cloak>
    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="bg-bg-card border border-line/[0.06] rounded-xl p-4 text-center">
            <div class="font-serif text-2xl text-gold">{{ $counts['active'] }}</div>
            <div class="text-xs text-text-secondary mt-1">Pacientes activos</div>
        </div>
        <div class="bg-bg-card border border-line/[0.06] rounded-xl p-4 text-center">
            <div class="font-serif text-2xl text-text-primary">{{ $counts['invited'] }}</div>
            <div class="text-xs text-text-secondary mt-1">Invitaciones pendientes</div>
        </div>
        <div class="bg-bg-card border border-line/[0.06] rounded-xl p-4 text-center">
            <div class="font-serif text-2xl text-text-secondary/60">{{ $counts['archived'] }}</div>
            <div class="text-xs text-text-secondary mt-1">Archivados</div>
        </div>
    </div>

    <div class="bg-bg-card border border-line/[0.06] rounded-2xl p-6 sm:p-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="font-serif text-xl">Pacientes</h2>
            <button type="button" @click="open = true"
                class="bg-gold text-black px-4 py-2 rounded-full font-bold text-sm hover:bg-gold/90 transition">
                Invitar paciente
            </button>
        </div>

        @if ($patients->isEmpty())
            <div class="text-center py-12">
                <div class="text-5xl font-serif italic text-gold mb-4">~</div>
                <p class="text-text-secondary text-sm max-w-sm mx-auto mb-6">
                    Su cartera está vacía. Invite a su primer paciente para empezar a ver su adherencia.
                </p>
                <button type="button" @click="open = true"
                    class="inline-flex items-center gap-2 bg-gold text-black px-5 py-2.5 rounded-full font-bold text-sm hover:bg-gold/90 transition">
                    Invitar paciente <span aria-hidden="true">→</span>
                </button>
            </div>
        @else
            <div class="space-y-2">
                @foreach ($patients as $p)
                    @if ($p->pivot->status === 'active')
                        <a href="{{ route('nutri.patients.show', $p) }}"
                           class="flex items-center justify-between bg-bg/50 border border-line/[0.04] rounded-xl p-3 hover:border-gold/30 transition">
                            <div class="min-w-0">
                                <div class="font-serif text-base truncate">{{ $p->name ?? $p->pivot->invitation_email }}</div>
                                <div class="text-xs text-text-secondary truncate">{{ $p->email ?? $p->pivot->invitation_email }}</div>
                            </div>
                            <span class="shrink-0 text-xs px-3 py-1 rounded-full bg-fiel/15 text-fiel border border-fiel/30">
                                Activo
                            </span>
                        </a>
                    @else
                        <div class="flex items-center justify-between bg-bg/50 border border-line/[0.04] rounded-xl p-3">
                            <div class="min-w-0">
                                <div class="font-serif text-base truncate">{{ $p->name ?? $p->pivot->invitation_email }}</div>
                                <div class="text-xs text-text-secondary truncate">{{ $p->email ?? $p->pivot->invitation_email }}</div>
                            </div>
                            <span class="shrink-0 text-xs px-3 py-1 rounded-full
                                @if ($p->pivot->status === 'invited') bg-parcial/15 text-parcial border border-parcial/30
                                @else bg-line/5 text-text-secondary border border-line/10
                                @endif">
                                {{ ['invited' => 'Invitado', 'archived' => 'Archivado'][$p->pivot->status] ?? $p->pivot->status }}
                            </span>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    {{-- Modal: invitar paciente --}}
    <div x-show="open" x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4"
         @keydown.escape.window="open = false"
         @click.self="open = false">
        <div class="bg-bg-card border border-line/[0.08] rounded-2xl p-6 sm:p-8 w-full max-w-md"
             @click.stop x-transition>
            <h3 class="font-serif text-xl mb-2">Invitar paciente</h3>
            <p class="text-sm text-text-secondary mb-5 leading-relaxed">
                Le enviaremos un correo con un enlace para que cree su cuenta y aparezca en su cartera.
            </p>

            <form method="POST" action="{{ route('nutri.invitations.store') }}">
                @csrf

                <div>
                    <x-input-label for="invite_email" value="Correo del paciente" />
                    <x-text-input id="invite_email" class="mt-1 w-full" type="email" name="email" :value="old('email')" required />
                    <x-input-error :messages="$errors->get('email')" />
                </div>

                <div class="mt-4">
                    <x-input-label for="invite_name" value="Nombre (opcional)" />
                    <x-text-input id="invite_name" class="mt-1 w-full" type="text" name="name" :value="old('name')" />
                    <x-input-error :messages="$errors->get('name')" />
                </div>

                <div class="flex items-center justify-end gap-3 mt-6">
                    <button type="button" @click="open = false"
                        class="text-sm text-text-secondary hover:text-text-primary transition">
                        Cancelar
                    </button>
                    <x-primary-button>Enviar invitación</x-primary-button>
                </div>
            </form>
        </div>
    </div>
    </div>
</x-app-layout>
