{{-- Toggle light/dark. Sin dependencias: JS puro + clase en <html> + localStorage.
     La visibilidad de los íconos se controla en partials/assets.blade.php (.pm-sun/.pm-moon). --}}
<button type="button"
    onclick="(function(el){var toLight=!el.classList.contains('light');el.classList.toggle('light',toLight);el.classList.toggle('dark',!toLight);try{localStorage.setItem('theme',toLight?'light':'dark');}catch(e){}})(document.documentElement)"
    title="Cambiar tema claro / oscuro"
    aria-label="Cambiar tema claro u oscuro"
    {{ $attributes->merge(['class' => 'inline-flex items-center justify-center h-9 w-9 rounded-full border border-line/10 text-text-secondary hover:text-text-primary hover:border-line/20 transition']) }}>
    {{-- Luna (visible en dark) --}}
    <svg class="pm-moon h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3a7 7 0 009.79 9.79z" />
    </svg>
    {{-- Sol (visible en light) --}}
    <svg class="pm-sun h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
        <circle cx="12" cy="12" r="4" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32l1.41-1.41" />
    </svg>
</button>
