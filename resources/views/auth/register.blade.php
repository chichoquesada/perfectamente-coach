<x-guest-layout>
    <h2 class="font-serif text-2xl mb-6 text-center">Cree su cuenta</h2>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div>
            <x-input-label for="name" value="Nombre" />
            <x-text-input id="name" class="mt-1" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" class="mt-1" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Contraseña" />
            <x-password-input id="password" class="mt-1" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
            <p class="text-xs text-text-secondary/60 mt-1.5">
                Mínimo 10 caracteres, con mayúsculas, números y al menos un símbolo.
            </p>
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Confirmar contraseña" />
            <x-password-input id="password_confirmation" class="mt-1" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <p class="text-xs text-text-secondary/70 mt-6 leading-relaxed">
            Al crear su cuenta acepta nuestros
            <a href="{{ route('legal.terms') }}" class="text-gold underline hover:no-underline" target="_blank">Términos</a>
            y la
            <a href="{{ route('legal.privacy') }}" class="text-gold underline hover:no-underline" target="_blank">Política de Privacidad</a>.
        </p>

        <div class="flex items-center justify-between mt-6">
            <a class="text-sm text-text-secondary hover:text-text-primary transition" href="{{ route('login') }}">
                ¿Ya tiene cuenta?
            </a>

            <x-primary-button>
                Registrarse
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
