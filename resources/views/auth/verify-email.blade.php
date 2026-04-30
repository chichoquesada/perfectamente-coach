<x-guest-layout>
    <h2 class="font-serif text-2xl mb-4 text-center">Verifique su correo</h2>

    <div class="mb-4 text-sm text-text-secondary leading-relaxed">
        Gracias por registrarse. Antes de continuar, confirme su dirección de correo dando clic en el enlace que le acabamos de enviar. Si no lo recibió, con gusto le enviamos otro.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 text-sm text-emerald-400">
            Se envió un nuevo enlace de verificación al correo que indicó al registrarse.
        </div>
    @endif

    <div class="mt-6 flex items-center justify-between gap-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>
                Reenviar verificación
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-text-secondary hover:text-gold underline transition">
                Cerrar sesión
            </button>
        </form>
    </div>
</x-guest-layout>
