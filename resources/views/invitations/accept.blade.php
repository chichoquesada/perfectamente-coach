<x-guest-layout>
    <p class="text-xs text-gold tracking-[0.25em] uppercase text-center mb-2">Invitación</p>
    <h2 class="font-serif text-2xl mb-2 text-center">
        {{ $nutri->name }} le invitó
    </h2>
    <p class="text-sm text-text-secondary text-center mb-6 leading-relaxed">
        Acepte para que su nutricionista pueda darle seguimiento dentro de PerfectaMENTE.
    </p>

    @if ($existingUser)
        <div class="bg-bg-card border border-white/[0.06] rounded-xl p-4 mb-4">
            <p class="text-sm text-text-secondary leading-relaxed">
                Ya existe una cuenta con <strong class="text-text-primary">{{ $email }}</strong>.
                Inicie sesión y vuelva a este enlace para aceptar la invitación.
            </p>
        </div>
        <div class="flex justify-end">
            <a href="{{ route('login') }}" class="text-sm text-gold underline hover:no-underline">
                Iniciar sesión
            </a>
        </div>
    @else
        <form method="POST" action="{{ route('invitation.accept', $token) }}">
            @csrf

            <div>
                <x-input-label for="email" value="Correo" />
                <x-text-input id="email" class="mt-1 opacity-70" type="email" :value="$email" disabled />
                <p class="text-xs text-text-secondary/60 mt-1.5">Su nutricionista usó este correo.</p>
            </div>

            <div class="mt-4">
                <x-input-label for="name" value="Nombre completo" />
                <x-text-input id="name" class="mt-1" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div class="mt-4">
                <x-input-label for="password" value="Contraseña" />
                <x-text-input id="password" class="mt-1" type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" />
                <p class="text-xs text-text-secondary/60 mt-1.5">
                    Mínimo 10 caracteres, con mayúsculas, números y al menos un símbolo.
                </p>
            </div>

            <div class="mt-4">
                <x-input-label for="password_confirmation" value="Confirmar contraseña" />
                <x-text-input id="password_confirmation" class="mt-1" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" />
            </div>

            <p class="text-xs text-text-secondary/70 mt-6 leading-relaxed">
                Al aceptar acepta los
                <a href="{{ route('legal.terms') }}" class="text-gold underline hover:no-underline" target="_blank">Términos</a>
                y la
                <a href="{{ route('legal.privacy') }}" class="text-gold underline hover:no-underline" target="_blank">Política de Privacidad</a>.
            </p>

            <div class="flex items-center justify-end mt-6">
                <x-primary-button>
                    Aceptar invitación
                </x-primary-button>
            </div>
        </form>
    @endif
</x-guest-layout>
