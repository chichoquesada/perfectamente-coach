@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'text-sm text-fiel']) }}>
        {{ $status }}
    </div>
@endif
