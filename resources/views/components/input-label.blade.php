@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-xs text-text-secondary uppercase tracking-wider mb-1.5']) }}>
    {{ $value ?? $slot }}
</label>
