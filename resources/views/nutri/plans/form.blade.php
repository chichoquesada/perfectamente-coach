<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="font-serif text-2xl">{{ $plan ? 'Editar plan' : 'Nuevo plan' }}</h1>
                <p class="text-sm text-text-secondary mt-1">
                    Construya el plan. Después podrá asignárselo a un paciente.
                </p>
            </div>
            <a href="{{ route('nutri.plans.index') }}" class="shrink-0 text-xs text-text-secondary hover:text-text-primary transition">
                ← Volver
            </a>
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="bg-nofiel/10 border border-nofiel/30 text-nofiel text-sm rounded-xl p-3 mb-4">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div x-data="planEditor(
              {{ \Illuminate\Support\Js::from($extracted) }},
              {{ \Illuminate\Support\Js::from($templates ?? collect()) }},
              {{ \Illuminate\Support\Js::from($methodologies->pluck('name')) }}
          )"
         class="lg:grid lg:grid-cols-[minmax(0,1fr)_22rem] lg:gap-6 lg:items-start">

    <form method="POST"
          action="{{ $plan ? route('nutri.plans.update', $plan) : route('nutri.plans.store') }}"
          @submit="syncToHidden()"
          class="min-w-0">
        @csrf
        @if ($plan) @method('PUT') @endif

        <input type="hidden" name="plan_data" x-ref="payload">

        {{-- Empezar desde un PDF (solo al crear) --}}
        @if ($plan === null)
            <div class="bg-bg-card border border-gold/20 rounded-2xl p-6 mb-6 relative">
                <x-input-label value="Empezar desde un PDF (opcional)" />
                <p class="text-xs text-text-secondary/60 mb-3">Suba el PDF del plan y la IA lo lee y rellena el formulario. Después revíselo y ajuste antes de guardar.</p>
                <div class="flex items-center gap-3 flex-wrap">
                    <label class="inline-flex items-center gap-2 text-sm cursor-pointer bg-gold text-black px-4 py-2 rounded-full font-bold hover:bg-gold/90 transition"
                           :class="pdfUploading ? 'opacity-40 pointer-events-none' : ''">
                        <span>📄 Subir PDF y analizar</span>
                        <input type="file" accept="application/pdf" class="hidden" @change="uploadPdf($event)">
                    </label>
                    <span x-show="pdfFileName" x-text="pdfFileName" x-cloak class="text-xs text-text-secondary"></span>
                </div>
                <p x-show="pdfError" x-text="pdfError" x-cloak class="text-nofiel text-sm mt-2"></p>

                <div x-show="pdfUploading" x-cloak
                     class="absolute inset-0 flex flex-col items-center justify-center bg-bg-card/95 backdrop-blur-sm rounded-2xl">
                    <div class="relative w-12 h-12 mb-3">
                        <div class="absolute inset-0 rounded-full border-2 border-line/10"></div>
                        <div class="absolute inset-0 rounded-full border-2 border-gold border-t-transparent animate-spin"></div>
                    </div>
                    <p class="text-sm text-text-secondary">Analizando el PDF con IA… (15-60s)</p>
                </div>
            </div>
        @endif

        {{-- Cargar desde plantilla (solo al crear) --}}
        @if ($plan === null && ($templates ?? collect())->isNotEmpty())
            <div class="bg-bg-card border border-gold/20 rounded-2xl p-6 mb-6">
                <x-input-label value="Cargar desde plantilla (opcional)" />
                <select @change="loadTemplate($event.target.value)"
                        class="mt-1 w-full bg-bg border border-line/10 text-text-primary focus:border-gold focus:ring-1 focus:ring-gold rounded-lg px-3 py-2 text-sm transition">
                    <option value="">— Empezar de cero —</option>
                    @foreach ($templates as $t)
                        <option value="{{ $t['id'] }}">{{ $t['name'] }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-text-secondary/60 mt-1">Copia el contenido de un plan existente para editarlo como uno nuevo.</p>
            </div>
        @endif

        {{-- Datos generales --}}
        <div class="bg-bg-card border border-line/[0.06] rounded-2xl p-6 mb-6">
            <h2 class="font-serif text-xl mb-4">Datos generales</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label value="Nombre del plan" />
                    <x-text-input class="mt-1 w-full" type="text" x-model="data.paciente.nombre" required />
                    <p class="text-xs text-text-secondary/60 mt-1">Nombre interno de la plantilla. Ej: "Definición 1745 cal", "Volumen 2800 cal". Al asignarlo a un paciente, se reemplaza por el nombre del paciente.</p>
                </div>
                <div>
                    <x-input-label value="Metodología (opcional)" />
                    <select x-show="!metodologiaCreating"
                            x-model="data.metodologia"
                            @change="onMethodologyChange($event)"
                            class="mt-1 w-full bg-bg border border-line/10 text-text-primary focus:border-gold focus:ring-1 focus:ring-gold rounded-lg px-3 py-2 text-sm transition">
                        <option value="">— Sin metodología —</option>
                        @foreach ($methodologies as $m)
                            <option value="{{ $m->name }}">{{ $m->name }}</option>
                        @endforeach
                        <option value="__nueva__">+ Nueva…</option>
                    </select>
                    <div x-show="metodologiaCreating" x-cloak class="mt-1 flex gap-2">
                        <input type="text" x-ref="newMethod" x-model="data.metodologia"
                               placeholder="Nombre de la metodología"
                               class="flex-1 bg-bg border border-line/10 text-text-primary focus:border-gold focus:ring-1 focus:ring-gold rounded-lg px-3 py-2 text-sm transition">
                        <button type="button" @click="cancelNewMethodology()"
                                class="shrink-0 text-xs px-3 text-text-secondary hover:text-text-primary transition">Cancelar</button>
                    </div>
                </div>
                <div>
                    <x-input-label value="Objetivo principal" />
                    <x-text-input class="mt-1 w-full" type="text" x-model="data.objetivos.principal" placeholder="Ej: Disminuir %grasa" />
                </div>
                <div>
                    <x-input-label value="Objetivo secundario (opcional)" />
                    <x-text-input class="mt-1 w-full" type="text" x-model="data.objetivos.secundario" placeholder="Ej: 1745 cal/día" />
                </div>
            </div>
        </div>

        {{-- Comidas --}}
        <template x-for="bucket in [
            { key: 'comidas', label: 'Comidas regulares', help: 'Las comidas que el paciente hace todos los días.' },
            { key: 'comidas_entreno', label: 'Extras día de entreno', help: 'Se agregan a las comidas regulares cuando el paciente marca día de entreno.' },
            { key: 'comidas_competencia', label: 'Extras día de competencia', help: 'Se agregan en días de competencia.' },
        ]" :key="bucket.key">
            <div class="bg-bg-card border border-line/[0.06] rounded-2xl p-6 mb-6">
                <div class="flex items-start justify-between mb-2 gap-3">
                    <div>
                        <h2 class="font-serif text-xl" x-text="bucket.label"></h2>
                        <p class="text-xs text-text-secondary mt-1" x-text="bucket.help"></p>
                    </div>
                    <button type="button" @click="addComida(bucket.key)"
                        class="shrink-0 text-xs px-3 py-1.5 rounded-full border border-gold/40 text-gold hover:bg-gold/10 transition">
                        + Comida
                    </button>
                </div>

                <template x-if="data[bucket.key].length === 0">
                    <p class="text-xs text-text-secondary/60 py-4 text-center">Sin comidas todavía.</p>
                </template>

                <div class="space-y-3 mt-3">
                    <template x-for="(comida, idx) in data[bucket.key]" :key="bucket.key + '-' + idx">
                        <div class="bg-bg/40 border border-line/[0.04] rounded-xl p-4">
                            <div class="grid sm:grid-cols-12 gap-3 mb-3">
                                <div class="sm:col-span-4">
                                    <label class="block text-xs text-text-secondary mb-1">Nombre</label>
                                    <input type="text" x-model="comida.nombre"
                                           placeholder="Ej: Desayuno"
                                           class="w-full bg-bg/50 border border-line/[0.08] rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-gold/50">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs text-text-secondary mb-1">Icono</label>
                                    <input type="text" x-model="comida.icono_sugerido"
                                           placeholder="🍳" maxlength="4"
                                           class="w-full bg-bg/50 border border-line/[0.08] rounded-lg px-3 py-2 text-sm text-center focus:outline-none focus:border-gold/50">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs text-text-secondary mb-1">Hora</label>
                                    <input type="text" x-model="comida.hora"
                                           placeholder="08:00"
                                           class="w-full bg-bg/50 border border-line/[0.08] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold/50">
                                </div>
                                <div class="sm:col-span-4">
                                    <label class="block text-xs text-text-secondary mb-1">Descripción corta</label>
                                    <input type="text" x-model="comida.descripcion_plan"
                                           placeholder="Ej: 1 vegetal + 2 harinas + 3 PR"
                                           class="w-full bg-bg/50 border border-line/[0.08] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold/50">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="block text-xs text-text-secondary mb-1">
                                    Opciones (una por línea)
                                </label>
                                <textarea x-model="comida.opciones_text" rows="4"
                                          placeholder="1 taza de gallo pinto + 2 huevos + vegetal&#10;2 tortillas + 3 huevos + vegetal"
                                          class="w-full bg-bg/50 border border-line/[0.08] rounded-lg px-3 py-2 text-sm text-text-primary placeholder:text-text-secondary/40 focus:outline-none focus:border-gold/50 resize-none"></textarea>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs text-text-secondary mb-1">Tip (opcional)</label>
                                    <input type="text" x-model="comida.tip"
                                           placeholder="Ej: Sin azúcar agregado"
                                           class="w-full bg-bg/50 border border-line/[0.08] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold/50">
                                </div>
                                <div class="flex items-end justify-end">
                                    <button type="button" @click="removeComida(bucket.key, idx)"
                                        class="text-xs px-3 py-2 rounded-lg text-text-secondary/60 hover:text-nofiel transition">
                                        Eliminar comida
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        {{-- Suplementos y Farmacología (estructurados) --}}
        <template x-for="sec in [
            { key: 'suplementos', label: 'Suplementos', help: 'Suplementación diaria del paciente. Aparece como checkeable en su tablero.', emoji: '🥤' },
            { key: 'farmacologia', label: 'Farmacología', help: 'Protocolo farmacológico. Aparece como checkeable en su tablero.', emoji: '💊' },
        ]" :key="sec.key">
            <div class="bg-bg-card border border-line/[0.06] rounded-2xl p-6 mb-6">
                <div class="flex items-start justify-between mb-2 gap-3">
                    <div>
                        <h2 class="font-serif text-xl"><span x-text="sec.emoji"></span> <span x-text="sec.label"></span></h2>
                        <p class="text-xs text-text-secondary mt-1" x-text="sec.help"></p>
                    </div>
                    <button type="button" @click="addItem(sec.key)"
                        class="shrink-0 text-xs px-3 py-1.5 rounded-full border border-gold/40 text-gold hover:bg-gold/10 transition">
                        <span x-text="'+ ' + (sec.key === 'suplementos' ? 'Suplemento' : 'Fármaco')"></span>
                    </button>
                </div>

                <template x-if="data[sec.key].length === 0">
                    <p class="text-xs text-text-secondary/60 py-4 text-center">Nada agregado todavía.</p>
                </template>

                <div class="space-y-3 mt-3">
                    <template x-for="(item, idx) in data[sec.key]" :key="sec.key + '-' + idx">
                        <div class="bg-bg/40 border border-line/[0.04] rounded-xl p-4">
                            <div class="grid sm:grid-cols-12 gap-3">
                                <div class="sm:col-span-4">
                                    <label class="block text-xs text-text-secondary mb-1">Nombre</label>
                                    <input type="text" x-model="item.nombre"
                                           placeholder="Ej: Creatina"
                                           class="w-full bg-bg/50 border border-line/[0.08] rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-gold/50">
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="block text-xs text-text-secondary mb-1">Dosis</label>
                                    <input type="text" x-model="item.dosis"
                                           placeholder="Ej: 5 g"
                                           class="w-full bg-bg/50 border border-line/[0.08] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold/50">
                                </div>
                                <div class="sm:col-span-4">
                                    <label class="block text-xs text-text-secondary mb-1">Frecuencia / horario</label>
                                    <input type="text" x-model="item.frecuencia"
                                           placeholder="Ej: 1 vez al día, en ayunas"
                                           class="w-full bg-bg/50 border border-line/[0.08] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold/50">
                                </div>
                                <div class="sm:col-span-1 flex items-end justify-end">
                                    <button type="button" @click="removeItem(sec.key, idx)"
                                        class="text-xs px-2 py-2 rounded-lg text-text-secondary/60 hover:text-nofiel transition" title="Eliminar">
                                        ✕
                                    </button>
                                </div>
                                <div class="sm:col-span-12">
                                    <label class="block text-xs text-text-secondary mb-1">Nota (opcional)</label>
                                    <input type="text" x-model="item.nota"
                                           placeholder="Ej: Suspender si hay molestias"
                                           class="w-full bg-bg/50 border border-line/[0.08] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold/50">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        {{-- Listas adicionales --}}
        <div class="bg-bg-card border border-line/[0.06] rounded-2xl p-6 mb-6">
            <h2 class="font-serif text-xl mb-4">Listas adicionales</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs text-text-secondary mb-1">Evitar (uno por línea)</label>
                    <textarea x-model="lists.evitar_text" rows="3"
                              class="w-full bg-bg/50 border border-line/[0.08] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold/50 resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs text-text-secondary mb-1">Proteínas permitidas (una por línea)</label>
                    <textarea x-model="lists.proteinas_text" rows="3"
                              class="w-full bg-bg/50 border border-line/[0.08] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold/50 resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs text-text-secondary mb-1">Vegetales permitidos (uno por línea)</label>
                    <textarea x-model="lists.vegetales_text" rows="3"
                              class="w-full bg-bg/50 border border-line/[0.08] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold/50 resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs text-text-secondary mb-1">Bebidas permitidas (una por línea)</label>
                    <textarea x-model="lists.bebidas_text" rows="3"
                              class="w-full bg-bg/50 border border-line/[0.08] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold/50 resize-none"></textarea>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('nutri.plans.index') }}" class="text-sm text-text-secondary hover:text-text-primary transition">
                Cancelar
            </a>
            <x-primary-button>{{ $plan ? 'Guardar cambios' : 'Crear plan' }}</x-primary-button>
        </div>
    </form>

    {{-- Panel de vista previa (desktop): pegado al scroll --}}
    <aside class="hidden lg:block lg:sticky lg:top-6 lg:max-h-[calc(100vh-3rem)] lg:overflow-y-auto -mr-1 pr-1">
        <x-plan-preview />
    </aside>

    {{-- Botón flotante para abrir el preview (móvil) --}}
    <button type="button" @click="previewOpen = true"
            class="lg:hidden fixed bottom-5 right-5 z-30 flex items-center gap-2 bg-gold text-black px-4 py-3 rounded-full font-bold shadow-lg shadow-black/40">
        <span>👁</span><span>Vista previa</span>
    </button>

    {{-- Overlay del preview (móvil) --}}
    <div x-show="previewOpen" x-cloak class="lg:hidden fixed inset-0 z-40">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="previewOpen = false"></div>
        <div x-show="previewOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
             class="absolute inset-x-0 bottom-0 top-14 bg-bg rounded-t-3xl border-t border-line/10 overflow-y-auto p-5">
            <div class="flex justify-end mb-3">
                <button type="button" @click="previewOpen = false"
                        class="text-sm text-text-secondary hover:text-text-primary transition">Cerrar ✕</button>
            </div>
            <x-plan-preview />
        </div>
    </div>

    </div>{{-- /grid wrapper x-data --}}

    <script>
        function planEditor(initial, templates, methodologyNames) {
            const arrayToText = (arr) => Array.isArray(arr) ? arr.join('\n') : '';
            const textToArray = (txt) => (txt || '').split('\n').map(s => s.trim()).filter(s => s.length > 0);

            // Construye {data, lists} a partir de un extracted_data crudo.
            // Compartido entre el init y loadTemplate().
            const hydrate = (raw) => {
                const src = JSON.parse(JSON.stringify(raw || {}));
                src.paciente = src.paciente || { nombre: '' };
                src.objetivos = src.objetivos || { principal: '', secundario: '' };
                src.permitidos = src.permitidos || {};

                ['comidas', 'comidas_entreno', 'comidas_competencia'].forEach(k => {
                    src[k] = (src[k] || []).map(c => ({
                        nombre: c.nombre || '',
                        hora: c.hora || '',
                        icono_sugerido: c.icono_sugerido || '',
                        descripcion_plan: c.descripcion_plan || '',
                        tip: c.tip || '',
                        opciones_text: arrayToText(c.opciones || []),
                    }));
                });

                ['suplementos', 'farmacologia'].forEach(k => {
                    src[k] = (src[k] || []).map(i => ({
                        nombre: i.nombre || '',
                        dosis: i.dosis || '',
                        frecuencia: i.frecuencia || '',
                        nota: i.nota || '',
                    }));
                });

                const lists = {
                    evitar_text: arrayToText(src.evitar || []),
                    proteinas_text: arrayToText(src.permitidos?.proteinas || []),
                    vegetales_text: arrayToText(src.permitidos?.vegetales || []),
                    bebidas_text: arrayToText(src.permitidos?.bebidas || []),
                };

                return { data: src, lists };
            };

            const seed = hydrate(initial);

            return {
                data: seed.data,
                lists: seed.lists,
                templates: templates || [],
                methodologyNames: methodologyNames || [],
                metodologiaCreating: false,
                pdfUploading: false,
                pdfError: null,
                pdfFileName: '',
                previewOpen: false,

                // Helpers de la vista previa
                previewLines(txt) {
                    return (txt || '').split('\n').map(s => s.trim()).filter(s => s.length > 0);
                },
                isPlanEmpty() {
                    const d = this.data, l = this.lists;
                    const noComidas = ['comidas', 'comidas_entreno', 'comidas_competencia']
                        .every(k => !(d[k] || []).length);
                    const noItems = ['suplementos', 'farmacologia']
                        .every(k => !(d[k] || []).filter(i => (i.nombre || '').trim()).length);
                    const noListas = ['evitar_text', 'proteinas_text', 'vegetales_text', 'bebidas_text']
                        .every(k => !this.previewLines(l[k]).length);
                    const noGenerales = !(d.paciente && d.paciente.nombre) && !d.metodologia
                        && !(d.objetivos && (d.objetivos.principal || d.objetivos.secundario));
                    return noComidas && noItems && noListas && noGenerales;
                },

                init() {
                    this.metodologiaCreating = !!this.data.metodologia
                        && !this.methodologyNames.includes(this.data.metodologia);
                },

                onMethodologyChange(e) {
                    if (e.target.value === '__nueva__') {
                        this.metodologiaCreating = true;
                        this.data.metodologia = '';
                        this.$nextTick(() => this.$refs.newMethod && this.$refs.newMethod.focus());
                    }
                },
                cancelNewMethodology() {
                    this.metodologiaCreating = false;
                    this.data.metodologia = '';
                },

                applyExtracted(raw) {
                    const h = hydrate(raw);
                    this.data = h.data;
                    this.lists = h.lists;
                    this.metodologiaCreating = !!this.data.metodologia
                        && !this.methodologyNames.includes(this.data.metodologia);
                },

                async uploadPdf(e) {
                    const f = e.target.files[0];
                    e.target.value = ''; // permite re-subir el mismo archivo
                    if (!f) return;
                    if (f.type !== 'application/pdf') { this.pdfError = 'El archivo debe ser un PDF.'; return; }
                    if (f.size > 10 * 1024 * 1024) { this.pdfError = 'Máximo 10 MB.'; return; }
                    this.pdfError = null;
                    this.pdfFileName = f.name;
                    this.pdfUploading = true;
                    try {
                        const fd = new FormData();
                        fd.append('pdf', f);
                        const res = await fetch('{{ route('nutri.plans.extractPdf') }}', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: fd,
                        });
                        const json = await res.json();
                        if (!res.ok) throw new Error(json.error || ('HTTP ' + res.status));
                        this.applyExtracted(json.data);
                    } catch (err) {
                        console.error('pdf extract failed', err);
                        this.pdfError = err.message || 'No se pudo procesar el PDF.';
                        this.pdfFileName = '';
                    } finally {
                        this.pdfUploading = false;
                    }
                },

                loadTemplate(id) {
                    if (!id) return;
                    const t = this.templates.find(t => String(t.id) === String(id));
                    if (!t) return;
                    const h = hydrate(t.data);
                    h.data.paciente = h.data.paciente || {};
                    h.data.paciente.nombre = (h.data.paciente.nombre || 'Plan') + ' (copia)';
                    this.data = h.data;
                    this.lists = h.lists;
                    this.metodologiaCreating = !!this.data.metodologia
                        && !this.methodologyNames.includes(this.data.metodologia);
                },

                addComida(bucket) {
                    this.data[bucket].push({
                        nombre: '', hora: '', icono_sugerido: '🍽️',
                        descripcion_plan: '', tip: '', opciones_text: '',
                    });
                },
                removeComida(bucket, idx) {
                    this.data[bucket].splice(idx, 1);
                },
                addItem(bucket) {
                    this.data[bucket].push({ nombre: '', dosis: '', frecuencia: '', nota: '' });
                },
                removeItem(bucket, idx) {
                    this.data[bucket].splice(idx, 1);
                },

                syncToHidden() {
                    const payload = JSON.parse(JSON.stringify(this.data));
                    ['comidas', 'comidas_entreno', 'comidas_competencia'].forEach(k => {
                        payload[k] = (payload[k] || []).map(c => ({
                            nombre: c.nombre,
                            hora: c.hora,
                            icono_sugerido: c.icono_sugerido,
                            descripcion_plan: c.descripcion_plan,
                            tip: c.tip,
                            opciones: textToArray(c.opciones_text),
                        }));
                    });
                    payload.suplementos = (payload.suplementos || []).filter(s => (s.nombre || '').trim() !== '');
                    payload.farmacologia = (payload.farmacologia || []).filter(s => (s.nombre || '').trim() !== '');
                    payload.evitar = textToArray(this.lists.evitar_text);
                    payload.permitidos = payload.permitidos || {};
                    payload.permitidos.proteinas = textToArray(this.lists.proteinas_text);
                    payload.permitidos.vegetales = textToArray(this.lists.vegetales_text);
                    payload.permitidos.bebidas = textToArray(this.lists.bebidas_text);
                    this.$refs.payload.value = JSON.stringify(payload);
                },
            };
        }
    </script>
</x-app-layout>
