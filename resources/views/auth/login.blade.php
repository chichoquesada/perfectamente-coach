<x-guest-layout>
    <h2 class="font-serif text-2xl mb-6 text-center">Bienvenido de vuelta</h2>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" class="mt-1" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Contraseña" />
            <x-password-input id="password" class="mt-1" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="flex items-center justify-between mt-4">
            <label for="remember_me" class="inline-flex items-center text-sm text-text-secondary cursor-pointer">
                <input id="remember_me" type="checkbox" class="rounded bg-bg border-line/15 text-gold focus:ring-gold focus:ring-offset-0" name="remember">
                <span class="ms-2">Recordarme</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-text-secondary hover:text-gold underline transition" href="{{ route('password.request') }}">
                    ¿Olvidó su contraseña?
                </a>
            @endif
        </div>

        <div class="flex items-center justify-between mt-6">
            <a class="text-sm text-text-secondary hover:text-text-primary transition" href="{{ route('register') }}">
                ¿No tiene cuenta?
            </a>

            <x-primary-button>
                Entrar
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
