<x-guest-layout>
    <h2 class="font-serif text-2xl mb-4 text-center">Confirme su contraseña</h2>

    <div class="mb-6 text-sm text-text-secondary leading-relaxed">
        Esta es un área protegida. Por favor confirme su contraseña antes de continuar.
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div>
            <x-input-label for="password" value="Contraseña" />
            <x-text-input id="password" class="mt-1" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="flex justify-end mt-6">
            <x-primary-button>
                Confirmar
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
