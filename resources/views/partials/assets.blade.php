{{-- Tema (light/dark): se aplica ANTES del paint para evitar flash. --}}
<script>
    (function () {
        try {
            var t = localStorage.getItem('theme');
            if (t !== 'light' && t !== 'dark') {
                t = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
            }
            var el = document.documentElement;
            el.classList.remove('light', 'dark');
            el.classList.add(t);
        } catch (e) {
            document.documentElement.classList.add('dark');
        }
    })();
</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,500;1,600&display=swap" rel="stylesheet">

<script src="https://cdn.tailwindcss.com"></script>
<script>
    // Tokens como rgb(var(--c-x) / <alpha-value>): el dark queda IDÉNTICO y las
    // opacidades (bg-bg/40, border-line/[0.06]) siguen funcionando en ambos temas.
    function pmColor(varName) {
        return 'rgb(var(' + varName + ') / <alpha-value>)';
    }
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    bg: pmColor('--c-bg'),
                    'bg-card': pmColor('--c-bg-card'),
                    gold: pmColor('--c-gold'),
                    'fiel': pmColor('--c-fiel'),
                    'parcial': pmColor('--c-parcial'),
                    'nofiel': pmColor('--c-nofiel'),
                    'evitado': pmColor('--c-nofiel'),
                    'text-primary': pmColor('--c-text-primary'),
                    'text-secondary': pmColor('--c-text-secondary'),
                    // Hairlines / overlays sutiles que invierten según el tema.
                    line: pmColor('--c-line'),
                },
                fontFamily: {
                    // Montserrat en toda la app (UI + titulares). Mantenemos el slot
                    // "serif" apuntando a Montserrat para no tener que editar cada
                    // titular que usa font-serif.
                    sans: ['Montserrat', 'system-ui', 'sans-serif'],
                    serif: ['Montserrat', 'system-ui', 'sans-serif'],
                },
                borderColor: {
                    DEFAULT: 'rgb(var(--c-line) / 0.06)',
                },
                // Estética más "arquitectónica": radios mínimos (no el look redondo
                // suave). Se sobreescribe el scale; `full` se mantiene para círculos
                // reales (toggles de ícono, spinners, barra de progreso).
                borderRadius: {
                    DEFAULT: '3px',
                    sm: '2px',
                    md: '4px',
                    lg: '5px',
                    xl: '6px',
                    '2xl': '8px',
                    '3xl': '10px',
                },
            }
        }
    }
</script>

<style>
    /* Dark (default / identidad de marca) */
    :root {
        --c-bg: 10 10 10;
        --c-bg-card: 26 26 26;
        --c-gold: 255 210 100;
        --c-fiel: 74 222 128;
        --c-parcial: 251 191 36;
        --c-nofiel: 239 68 68;
        --c-text-primary: 245 245 245;
        --c-text-secondary: 184 184 184;
        --c-line: 255 255 255;
    }
    /* Light */
    html.light {
        --c-bg: 247 245 240;        /* crema cálido */
        --c-bg-card: 255 255 255;
        --c-gold: 166 121 8;        /* gold profundo: legible como texto y como botón */
        --c-fiel: 22 163 74;
        --c-parcial: 217 119 6;
        --c-nofiel: 220 38 38;
        --c-text-primary: 26 26 26;
        --c-text-secondary: 90 90 90;
        --c-line: 17 24 39;         /* hairline oscuro sutil */
    }
    html { color-scheme: dark; }
    html.light { color-scheme: light; }
    [x-cloak] { display: none !important; }
    /* Íconos del toggle de tema */
    .pm-sun { display: none; }
    .pm-moon { display: block; }
    html.light .pm-sun { display: block; }
    html.light .pm-moon { display: none; }
</style>

<script>
    // Store global de la vista del chequeo (checklist | full). Vive fuera del
    // dashboard para que el toggle del header (navigation) y el dashboard
    // compartan el mismo estado. Default: preferencia guardada, o auto por
    // device (móvil → checklist, escritorio ≥1024px → detalle).
    document.addEventListener('alpine:init', () => {
        Alpine.store('mealView', {
            current: (function () {
                try { const s = localStorage.getItem('pm.mealView'); if (s === 'checklist' || s === 'full') return s; } catch (e) { /* ignore */ }
                try { return window.matchMedia('(min-width: 1024px)').matches ? 'full' : 'checklist'; } catch (e) { /* ignore */ }
                return 'checklist';
            })(),
            set(v) {
                this.current = v;
                try { localStorage.setItem('pm.mealView', v); } catch (e) { /* ignore */ }
            },
        });

        // HUD del día: progreso compartido entre el header (anillo) y el
        // dashboard. El componente dashboard() lo sincroniza con sync().
        Alpine.store('hud', {
            marked: 0, total: 0, racha: 0, microcopy: '', unit: 'comidas', fidelidad: 0, ready: false, celebrate: false,
            // El anillo muestra la FIDELIDAD del día: la MISMA métrica que el popup
            // del calendario, el heatmap, la racha y el análisis. El "X/Y ítems" de
            // abajo es el avance del registro (cuántos marcaste). Antes el anillo
            // mostraba avance y no cuadraba con el resto.
            get pct() { return this.fidelidad; },
            sync(d) {
                this.marked = d.marked; this.total = d.total;
                this.racha = d.racha; this.microcopy = d.microcopy;
                this.unit = d.unit || 'comidas';
                this.fidelidad = d.fidelidad ?? 0;
                this.ready = true;
            },
            fireCelebrate() {
                this.celebrate = true;
                setTimeout(() => { this.celebrate = false; }, 1800);
            },
        });
    });
</script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
