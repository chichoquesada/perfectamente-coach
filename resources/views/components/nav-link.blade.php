@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center text-sm font-medium text-gold border-b border-gold pb-0.5 transition'
            : 'inline-flex items-center text-sm font-medium text-text-secondary hover:text-text-primary transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
