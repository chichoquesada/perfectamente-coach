<x-guest-layout>
    <h2 class="font-serif text-2xl mb-3 text-center">Recuperar contraseña</h2>

    <p class="mb-6 text-sm text-text-secondary text-center">
        Indíquenos su correo y le enviaremos un enlace para crear una nueva.
    </p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" class="mt-1" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a class="text-sm text-text-secondary hover:text-text-primary transition" href="{{ route('login') }}">
                ← Volver a entrar
            </a>

            <x-primary-button>
                Enviar enlace
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
