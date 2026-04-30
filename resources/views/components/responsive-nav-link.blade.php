@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-2 border-gold text-start text-base font-medium text-gold bg-white/5 transition'
            : 'block w-full ps-3 pe-4 py-2 border-l-2 border-transparent text-start text-base font-medium text-text-secondary hover:text-text-primary hover:bg-white/5 transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
