<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Lora:ital,wght@0,400;0,500;0,600;1,400;1,500;1,600&display=swap" rel="stylesheet">

<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    bg: '#0a0a0a',
                    'bg-card': '#1a1a1a',
                    gold: '#FFD264',
                    'fiel': '#4ade80',
                    'parcial': '#fbbf24',
                    'nofiel': '#ef4444',
                    'text-primary': '#f5f5f5',
                    'text-secondary': '#b8b8b8',
                },
                fontFamily: {
                    sans: ['Inter', 'system-ui', 'sans-serif'],
                    serif: ['Lora', 'Georgia', 'serif'],
                },
                borderColor: {
                    DEFAULT: 'rgba(255,255,255,0.06)',
                },
            }
        }
    }
</script>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
