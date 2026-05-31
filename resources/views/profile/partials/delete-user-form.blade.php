<section class="space-y-6">
    <header>
        <h2 class="font-serif text-xl text-red-400">
            Eliminar cuenta
        </h2>

        <p class="mt-1 text-sm text-text-secondary leading-relaxed">
            Al eliminar su cuenta, todos sus datos y recursos se borrarán de forma permanente. Antes de continuar, descargue cualquier información que desee conservar.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Eliminar cuenta</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6 bg-card">
            @csrf
            @method('delete')

            <h2 class="font-serif text-xl text-text-primary">
                ¿Está seguro de eliminar su cuenta?
            </h2>

            <p class="mt-1 text-sm text-text-secondary leading-relaxed">
                Esta acción es permanente. Ingrese su contraseña para confirmar que desea eliminar su cuenta de forma definitiva.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Contraseña" class="sr-only" />

                <x-password-input
                    id="password"
                    name="password"
                    class="mt-1 block w-3/4"
                    placeholder="Contraseña"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>

                <x-danger-button>
                    Eliminar cuenta
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
