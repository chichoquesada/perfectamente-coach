@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full bg-bg border border-white/10 text-text-primary placeholder-text-secondary/40 focus:border-gold focus:ring-1 focus:ring-gold rounded-lg px-3 py-2 text-sm transition']) }}>
