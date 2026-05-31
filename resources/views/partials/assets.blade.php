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
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Lora:ital,wght@0,400;0,500;0,600;1,400;1,500;1,600&display=swap" rel="stylesheet">

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
                    sans: ['Inter', 'system-ui', 'sans-serif'],
                    serif: ['Lora', 'Georgia', 'serif'],
                },
                borderColor: {
                    DEFAULT: 'rgb(var(--c-line) / 0.06)',
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

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
