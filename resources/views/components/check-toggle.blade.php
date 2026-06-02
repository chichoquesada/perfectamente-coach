@props(['itemId', 'compact' => false])

{{-- Control de estado del check (Fiel / Parcial / No fiel / NA).
     - Variante normal (full): fila full-width de 4 columnas, targets de 48px,
       ícono sobre etiqueta. Para la vista "Detalle".
     - Variante compacta: 36px, ícono + etiqueta en línea. Para la vista "Checklist".
     Las expresiones Alpine (checks, loading, setStatus) viven en el x-data del
     dashboard padre. --}}
<div {{ $attributes->merge(['class' => 'grid grid-cols-4 gap-1.5 bg-line/5 rounded-xl p-1']) }}>
    @foreach ([
        'fiel' => ['✓', 'Fiel', 'bg-fiel text-black'],
        'parcial' => ['~', 'Parcial', 'bg-parcial text-black'],
        'nofiel' => ['✗', 'No fiel', 'bg-nofiel text-white'],
        'na' => ['NA', 'No aplica', 'bg-line/40 text-text-primary'],
    ] as $st => [$icon, $label, $activeClass])
        <button
            type="button"
            @click="setStatus('{{ $itemId }}', '{{ $st }}')"
            :disabled="loading['{{ $itemId }}']"
            :class="checks['{{ $itemId }}'] === '{{ $st }}'
                ? '{{ $activeClass }} shadow-sm'
                : 'text-text-secondary/60 hover:text-text-primary hover:bg-line/5'"
            @class([
                'rounded-lg font-bold transition disabled:opacity-40 active:scale-95 flex items-center justify-center',
                'flex-col gap-0.5 h-12' => ! $compact,
                'gap-1 h-9' => $compact,
            ])
        >
            <span @class(['leading-none', 'text-base' => ! $compact, 'text-sm' => $compact])>{{ $icon }}</span>
            {{-- En compacto, "NA" ya se entiende por su ícono → no repetimos "No aplica"
                 (evita que envuelva en celdas angostas). En detalle sí va el texto. --}}
            @unless ($compact && $st === 'na')
                <span class="text-[10px] font-semibold tracking-wide leading-none">{{ $label }}</span>
            @endunless
        </button>
    @endforeach
</div>
