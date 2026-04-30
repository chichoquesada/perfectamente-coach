<x-app-layout>
    <x-slot name="header">
        <h1 class="font-serif text-2xl">Hoy</h1>
        <p class="text-sm text-text-secondary mt-1">{{ now()->isoFormat('dddd, D [de] MMMM') }}</p>
    </x-slot>

    <div class="bg-bg-card border border-white/[0.06] rounded-2xl p-8 text-center">
        <div class="text-5xl font-serif italic text-gold mb-4">~</div>
        <h2 class="font-serif text-xl mb-2">Aún no ha subido su plan</h2>
        <p class="text-text-secondary text-sm mb-6 max-w-sm mx-auto">
            Suba el PDF de su nutricionista. La IA lo lee, lo organiza y le entrega su tablero diario.
        </p>
        <a href="#" class="inline-flex items-center gap-2 bg-gold text-black px-5 py-2.5 rounded-full font-bold text-sm hover:bg-gold/90 transition">
            Subir mi plan <span aria-hidden="true">→</span>
        </a>
        <p class="text-xs text-text-secondary/60 mt-4 italic font-serif">próximamente</p>
    </div>
</x-app-layout>
