{{-- Toggle de vista del chequeo: Checklist ↔ Detalle.
     Vive en el header, a la par del theme-toggle. Controla el store global
     $store.mealView (compartido con el dashboard). El ícono muestra la vista
     ACTUAL; al tocar, alterna a la otra. --}}
<button
    type="button"
    @click="$store.mealView.set($store.mealView.current === 'checklist' ? 'full' : 'checklist')"
    :title="$store.mealView.current === 'checklist' ? 'Ver detalle del día' : 'Ver checklist rápido'"
    aria-label="Cambiar vista (checklist / detalle)"
    {{ $attributes->merge(['class' => 'inline-flex items-center justify-center h-9 w-9 rounded-full border border-line/10 text-text-secondary hover:text-text-primary hover:border-line/20 transition']) }}
>
    {{-- Checklist activo: ícono de lista --}}
    <svg x-show="$store.mealView.current === 'checklist'" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h11.5M8.25 12h11.5M8.25 17.25h11.5M3.75 6.75h.008v.008H3.75V6.75zM3.75 12h.008v.008H3.75V12zM3.75 17.25h.008v.008H3.75v-.008z" />
    </svg>
    {{-- Detalle activo: ícono de tarjetas --}}
    <svg x-show="$store.mealView.current === 'full'" x-cloak class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h12A2.25 2.25 0 0120.25 6v12A2.25 2.25 0 0118 20.25H6A2.25 2.25 0 013.75 18V6z" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.75h16.5M3.75 14.25h16.5" />
    </svg>
</button>
