{{--
    Vista previa en vivo del plan.
    NO tiene x-data propio: se renderiza dentro del scope Alpine de planEditor()
    y lee directamente `data` / `lists`. Reutilizable en el panel sticky (desktop)
    y en el overlay (móvil).
--}}
<div class="space-y-4">
    {{-- Encabezado --}}
    <div class="flex items-center gap-2">
        <span class="text-base">👁</span>
        <p class="text-xs text-gold tracking-[0.25em] uppercase">Así lo verá el paciente</p>
    </div>

    {{-- Datos generales --}}
    <div class="bg-bg-card border border-white/[0.06] rounded-2xl p-4">
        <p class="text-xs text-gold tracking-[0.2em] uppercase mb-2">Plan</p>
        <h3 class="font-serif text-lg leading-tight"
            x-text="(data.paciente && data.paciente.nombre) ? data.paciente.nombre : 'Sin nombre todavía'"
            :class="(data.paciente && data.paciente.nombre) ? '' : 'text-text-secondary/50 italic'"></h3>
        <p x-show="data.metodologia" x-cloak class="text-xs text-text-secondary mt-1">
            Metodología: <span class="text-text-primary" x-text="data.metodologia"></span>
        </p>
        <div x-show="(data.objetivos && (data.objetivos.principal || data.objetivos.secundario))" x-cloak
             class="mt-2 space-y-0.5 text-xs text-text-secondary">
            <p x-show="data.objetivos.principal">🎯 <span class="text-text-primary" x-text="data.objetivos.principal"></span></p>
            <p x-show="data.objetivos.secundario" x-text="data.objetivos.secundario"></p>
        </div>
    </div>

    {{-- Comidas (regulares, entreno, competencia) --}}
    <template x-for="bucket in [
        { key: 'comidas', label: 'Comidas', accent: 'text-fiel' },
        { key: 'comidas_entreno', label: 'Extras día de entreno', accent: 'text-gold' },
        { key: 'comidas_competencia', label: 'Extras día de competencia', accent: 'text-gold' },
    ]" :key="bucket.key">
        <div x-show="data[bucket.key] && data[bucket.key].length > 0" x-cloak
             class="bg-bg-card border border-white/[0.06] rounded-2xl p-4">
            <p class="text-xs tracking-[0.2em] uppercase mb-3" :class="bucket.accent" x-text="bucket.label"></p>
            <div class="space-y-3">
                <template x-for="(comida, idx) in data[bucket.key]" :key="bucket.key + '-prev-' + idx">
                    <div class="border-l-2 border-white/10 pl-3">
                        <div class="flex items-center gap-2">
                            <span x-show="comida.icono_sugerido" x-text="comida.icono_sugerido" class="text-sm"></span>
                            <span class="font-serif text-sm"
                                  x-text="comida.nombre || 'Comida sin nombre'"
                                  :class="comida.nombre ? '' : 'text-text-secondary/50 italic'"></span>
                            <span x-show="comida.hora" x-text="comida.hora" class="text-xs text-text-secondary/60 ml-auto"></span>
                        </div>
                        <p x-show="comida.descripcion_plan" x-text="comida.descripcion_plan"
                           class="text-xs text-text-secondary mt-0.5"></p>
                        <ul x-show="previewLines(comida.opciones_text).length" class="mt-1.5 space-y-1">
                            <template x-for="(op, oi) in previewLines(comida.opciones_text)" :key="oi">
                                <li class="text-xs text-text-primary flex gap-1.5">
                                    <span class="text-fiel">·</span><span x-text="op"></span>
                                </li>
                            </template>
                        </ul>
                        <p x-show="comida.tip" x-cloak class="text-xs text-text-secondary/60 italic mt-1">
                            💡 <span x-text="comida.tip"></span>
                        </p>
                    </div>
                </template>
            </div>
        </div>
    </template>

    {{-- Permitidos --}}
    <template x-for="cat in [
        { key: 'proteinas_text', label: 'Proteínas', icon: '🥩' },
        { key: 'vegetales_text', label: 'Vegetales', icon: '🥬' },
        { key: 'bebidas_text', label: 'Bebidas', icon: '🥤' },
    ]" :key="cat.key">
        <div x-show="previewLines(lists[cat.key]).length" x-cloak
             class="bg-bg-card border border-white/[0.06] rounded-2xl p-4">
            <div class="flex items-center gap-2 mb-2">
                <span class="text-sm" x-text="cat.icon"></span>
                <h4 class="font-serif text-sm" x-text="cat.label"></h4>
                <span class="text-xs text-text-secondary/60" x-text="previewLines(lists[cat.key]).length"></span>
            </div>
            <div class="flex flex-wrap gap-1.5">
                <template x-for="(item, ii) in previewLines(lists[cat.key])" :key="ii">
                    <span class="text-xs px-2.5 py-1 bg-fiel/10 border border-fiel/20 text-text-primary rounded-full" x-text="item"></span>
                </template>
            </div>
        </div>
    </template>

    {{-- Evitar --}}
    <div x-show="previewLines(lists.evitar_text).length" x-cloak
         class="bg-bg-card border border-white/[0.06] rounded-2xl p-4">
        <p class="text-xs text-nofiel tracking-[0.2em] uppercase mb-2">Evitar</p>
        <div class="flex flex-wrap gap-1.5">
            <template x-for="(item, ii) in previewLines(lists.evitar_text)" :key="ii">
                <span class="text-xs px-2.5 py-1 bg-nofiel/10 border border-nofiel/20 text-text-primary rounded-full" x-text="item"></span>
            </template>
        </div>
    </div>

    {{-- Suplementos / Farmacología --}}
    <template x-for="sec in [
        { key: 'suplementos', label: 'Suplementos', emoji: '🥤' },
        { key: 'farmacologia', label: 'Farmacología', emoji: '💊' },
    ]" :key="sec.key">
        <div x-show="data[sec.key] && data[sec.key].filter(i => (i.nombre||'').trim()).length" x-cloak
             class="bg-bg-card border border-white/[0.06] rounded-2xl p-4">
            <p class="text-xs text-gold tracking-[0.2em] uppercase mb-3">
                <span x-text="sec.emoji"></span> <span x-text="sec.label"></span>
            </p>
            <div class="space-y-2">
                <template x-for="(item, idx) in data[sec.key]" :key="sec.key + '-prev-' + idx">
                    <div x-show="(item.nombre||'').trim()" class="flex items-start gap-2">
                        <span class="text-gold mt-0.5">·</span>
                        <div>
                            <div class="text-sm text-text-primary">
                                <span class="font-medium" x-text="item.nombre"></span>
                                <span x-show="item.dosis" class="text-text-secondary"> — <span x-text="item.dosis"></span></span>
                                <span x-show="item.frecuencia" class="text-text-secondary/70"> · <span x-text="item.frecuencia"></span></span>
                            </div>
                            <div x-show="item.nota" x-cloak class="text-xs text-text-secondary/60 italic mt-0.5" x-text="item.nota"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>

    {{-- Estado vacío --}}
    <div x-show="isPlanEmpty()" x-cloak
         class="bg-bg-card border border-dashed border-white/10 rounded-2xl p-6 text-center">
        <p class="text-xs text-text-secondary/60">El preview se irá llenando a medida que complete el plan.</p>
    </div>
</div>
