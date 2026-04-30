<x-app-layout>
    <x-slot name="header">
        <p class="text-xs text-gold tracking-[0.3em] uppercase mb-2">Paso 1 de 1</p>
        <h1 class="font-serif text-3xl">Comencemos. <em class="text-gold not-italic font-serif italic">Suba su plan.</em></h1>
        <p class="text-sm text-text-secondary mt-2">
            La IA lo lee, lo organiza y le entrega su tablero diario. PDF de su nutricionista, médico o coach.
        </p>
    </x-slot>

    <div
        x-data="{
            file: null,
            dragging: false,
            uploading: false,
            error: null,
            select(f) {
                this.error = null;
                if (!f) return;
                if (f.type !== 'application/pdf') { this.error = 'Solo PDF.'; return; }
                if (f.size > 10 * 1024 * 1024) { this.error = 'Máximo 10 MB.'; return; }
                this.file = f;
            },
            submit(e) {
                if (!this.file) { e.preventDefault(); this.error = 'Suba un PDF primero.'; return; }
                this.uploading = true;
            }
        }"
        class="bg-bg-card border border-white/[0.06] rounded-2xl p-6 sm:p-10"
    >
        <form action="{{ route('onboarding.upload') }}" method="POST" enctype="multipart/form-data" @submit="submit($event)">
            @csrf

            <label
                for="pdf-input"
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="dragging = false; select($event.dataTransfer.files[0])"
                :class="dragging ? 'border-gold bg-gold/5' : 'border-white/15 hover:border-white/30'"
                class="block border-2 border-dashed rounded-xl p-10 text-center cursor-pointer transition"
            >
                <input
                    id="pdf-input"
                    type="file"
                    name="pdf"
                    accept="application/pdf"
                    class="hidden"
                    @change="select($event.target.files[0])"
                    required
                >

                <template x-if="!file">
                    <div>
                        <div class="text-5xl mb-4 opacity-50">📄</div>
                        <p class="font-serif text-xl mb-2">Arrastre su PDF aquí</p>
                        <p class="text-sm text-text-secondary">o haga click para seleccionarlo</p>
                        <p class="text-xs text-text-secondary/60 mt-4">Máximo 10 MB</p>
                    </div>
                </template>

                <template x-if="file">
                    <div>
                        <div class="text-4xl mb-4">✓</div>
                        <p class="font-serif text-lg" x-text="file.name"></p>
                        <p class="text-xs text-text-secondary mt-1" x-text="(file.size / 1024 / 1024).toFixed(2) + ' MB'"></p>
                        <p class="text-xs text-gold mt-3 underline">Cambiar archivo</p>
                    </div>
                </template>
            </label>

            <p x-show="error" x-text="error" class="text-nofiel text-sm mt-3"></p>
            <x-input-error :messages="$errors->get('pdf')" />

            <button
                type="submit"
                :disabled="uploading || !file"
                :class="(uploading || !file) ? 'opacity-40 cursor-not-allowed' : ''"
                class="w-full mt-6 inline-flex items-center justify-center gap-2 bg-gold text-black px-5 py-3 rounded-full font-bold text-sm hover:bg-gold/90 transition"
            >
                <span x-show="!uploading">Subir y analizar mi plan <span aria-hidden="true">→</span></span>
                <span x-show="uploading" x-cloak>Subiendo…</span>
            </button>

            <p class="text-xs text-text-secondary/60 text-center mt-4 italic font-serif">
                Su PDF queda solo en su cuenta. No lo compartimos.
            </p>
        </form>
    </div>

    <div class="mt-8 text-sm text-text-secondary text-center">
        <p>¿Quiere probar la app sin un plan real todavía?</p>
        <a href="#" class="text-gold underline mt-2 inline-block opacity-50 cursor-not-allowed">Usar plan demo (próximamente)</a>
    </div>
</x-app-layout>
