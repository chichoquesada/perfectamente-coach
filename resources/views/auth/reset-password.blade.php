<x-guest-layout>
    <h2 class="font-serif text-2xl mb-6 text-center">Crear nueva contraseña</h2>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" class="mt-1" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Nueva contraseña" />
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

        <div class="flex items-center justify-end mt-6">
            <x-primary-button>
                Guardar nueva contraseña
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
