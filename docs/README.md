# PerfectaMENTE Coach

**La app que lo hace fiel a su plan.**

Suba el PDF de su nutricionista. La IA lo lee, lo organiza y le entrega un tablero diario donde solo tiene que hacer check.

---

## 🎯 Resumen del producto

**Problema:** El PDF que el nutricionista le da al paciente termina perdido en WhatsApp a las 3 semanas. El paciente no falla porque el plan sea malo. Falla porque pierde el plan.

**Solución:** Sube el PDF → IA extrae y estructura → app le muestra tablero diario con todas sus comidas/suplementos → hace check (Fiel / Parcial / No fiel) → ve su % de fidelidad en tiempo real → recibe análisis IA semanal.

**Diferenciador:** Soporta **modos del día** (Descanso / Entreno / Competencia) con cards dinámicos. Ninguna app genérica entiende esto. Solo una hecha por un atleta.

---

## 👤 Fundador

**Chicho (Carlos Esteban Quesada Víquez)**
- CrossFit Games Champion 2024 (40-44 división)
- Fundador de PerfectaMENTE (mental coaching)
- Fundador de Staygold Software
- Programador con experiencia en .NET MVC, C#, React, jQuery
- Costa Rica · @chichoqv

---

## 💰 Modelo de negocio

### Para el usuario final (B2C)

**Free**
- 1 plan activo
- 7 días de historial
- Checks ilimitados al día
- Subida de plan con IA

**Pro — $7/mes USD o $59/año (ahorra $25)**
- Historial ilimitado
- Análisis IA semanal personalizado
- Múltiples planes activos
- Reportes exportables (PDF para enviar al nutri)
- Modos de día (entreno/descanso/competencia)
- Soporte directo

### Para nutricionistas (afiliados)

- **30% de comisión recurrente** por cada cliente referido
- Link único: `planperfectamente.com/r/{codigo}`
- Dashboard de ingresos
- No pagan nada, solo cobran

---

## 🛠️ Stack técnico (costo casi cero)

| Capa | Tecnología | Costo mensual |
|------|-----------|---------------|
| Hosting | **WHM/cPanel propio** | $0 (ya pagado) |
| Backend | **Laravel 11 + PHP 8.2+** | $0 |
| Database | **MySQL/MariaDB** | $0 (incluido) |
| Templates | **Blade** server-side | $0 |
| Reactividad | **Alpine.js** (CDN) | $0 |
| Estilos | **Tailwind CSS** (CDN) | $0 |
| Auth | **Laravel Breeze** | $0 |
| AI | **Claude API** | ~$0.05 por extracción |
| Pagos | **Stripe + Cashier** | 2.9% por venta |
| Storage | Disco local WHM | $0 |
| Email | SMTP del WHM | $0 |
| Dominio | `planperfectamente.com` | ~$1/mes (~$12/año) |

**Total fijo: ~$1/mes.** Variable solo cuando hay usuarios reales.

**Trade-offs:**
- ❌ Deploy manual con `git pull` + SSH (no Vercel mágico)
- ❌ Sin edge functions globales
- ✅ Stack que ya conoce (.NET MVC → Laravel transición natural)
- ✅ Sin vendor lock-in
- ✅ Cero riesgo financiero al arrancar

---

## 🎨 Sistema visual

**Estética:** Dark mode minimalista atleta. Coherente con @chichoqv.

**Colores:**
- `--bg: #0a0a0a` (negro principal)
- `--bg-card: #1a1a1a`
- `--gold: #FFD264` (acento principal)
- `--green: #4ade80` (fiel)
- `--amber: #fbbf24` (parcial)
- `--red: #ef4444` (no fiel)
- Texto principal `#f5f5f5`, secundario `#b8b8b8`

**Tipografía:**
- **Lora** (serif italic) para títulos, marcas, énfasis
- **Inter** para cuerpo, datos, UI

**Reglas:**
- Sin emojis en headers excepto identificadores (🌅 🍳 en cards de comida)
- Sin gradientes saturados como fondos
- Borde izquierdo de color en lugar de fondos coloreados
- Bordes sutiles `rgba(255,255,255,0.06)`

---

## ✍️ Tono y voz

**Idioma:** Español Latinoamericano. **Siempre "usted"**.

**Estilo:** Hormozi-direct + toque espiritual cristiano ocasional. Sin azúcar.

**Copy correcto:**
- "Su plan no falla porque sea malo. Falla porque el PDF se perdió en su WhatsApp hace tres semanas."
- "Hora del primer combate. Su versión campeona ya está en la cocina."
- "Esta es la que más se le escapa. No hoy."

**Reglas duras:**
- ❌ NO em dashes (—) en copy formal
- ❌ NO emojis en headers
- ❌ NO mencionar autores en frases de cierre
- ✅ "@chichoqv" como cierre cuando aplique
- ✅ Lora italic para cursivas

---

## 📦 Archivos del prototipo

En `references/`:
- `perfectamente-demo-mobile.html` — la app completa (fuente de verdad visual)
- `perfectamente-coach-landing.html` — landing
- `plan_extraido.json` — ejemplo JSON de IA

---

## 🤝 Siguiente paso

1. Lea `SETUP.md` para preparar el WHM
2. Lea `CONTEXT.md` para entender el plan técnico
3. Pegue `MENSAJE-PARA-CLAUDE-CODE.md` en su primer chat con Claude Code

---

*"La fidelidad diaria es lo que separa a los campeones del resto." — @chichoqv*
