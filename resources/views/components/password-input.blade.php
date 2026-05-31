@props(['disabled' => false])

<div class="relative" x-data="{ show: false }">
    <input
        @disabled($disabled)
        x-bind:type="show ? 'text' : 'password'"
        {{ $attributes->merge(['class' => 'w-full bg-bg border border-white/10 text-text-primary placeholder-text-secondary/40 focus:border-gold focus:ring-1 focus:ring-gold rounded-lg px-3 py-2 pr-10 text-sm transition']) }}
    >
    <button
        type="button"
        @click="show = !show"
        tabindex="-1"
        :aria-label="show ? 'Ocultar contraseña' : 'Mostrar contraseña'"
        class="absolute inset-y-0 right-0 flex items-center pr-3 text-text-secondary/60 hover:text-gold transition select-none">
        <span x-show="!show">👁</span>
        <span x-show="show" x-cloak>🙈</span>
    </button>
</div>
