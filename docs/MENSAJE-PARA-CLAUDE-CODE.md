# Mensaje inicial para Claude Code

> **Cópie este mensaje completo y péguelo en su primer chat con Claude Code en VS Code.**
> **Antes de pegarlo, agregue al chat los archivos: README.md, CONTEXT.md, SETUP.md y los HTML de la carpeta references/**

---

Hola Claude Code, estoy retomando un proyecto que empecé en claude.ai. Necesito que leas estos tres archivos COMPLETOS antes de hacer nada:

1. `README.md` — visión del producto, modelo de negocio, decisiones de marca
2. `CONTEXT.md` — instrucciones técnicas detalladas (schema DB, prompt de Claude API, estructura de rutas)
3. `SETUP.md` — guía específica de deploy en mi WHM/cPanel

En la carpeta `references/` hay 3 archivos del prototipo visual ya validado:
- `perfectamente-demo-mobile.html` (la app)
- `perfectamente-coach-landing.html` (landing)
- `plan_extraido.json` (ejemplo de JSON que produce el extractor IA)

**Use esos archivos como fuente de verdad visual** — al construir los Blade templates debe preservar el diseño y el copy exactos.

---

## Stack ya decidido (NO me sugiera cambios)

- **Laravel 11 + PHP 8.2** (es como .NET MVC en PHP, me siento cómodo)
- **Blade + Alpine.js + Tailwind CDN** (sin build step, deploy simple)
- **MySQL** en mi servidor WHM/cPanel propio
- **Claude API** para extracción de planes
- **Stripe + Laravel Cashier** para pagos
- **Deploy:** Git pull por SSH al WHM (sin Vercel ni servicios pagos)

**Costo objetivo: $0/mes fijos** (excepto dominio ~$1/mes y APIs por uso).

---

## Lo que quiero hacer hoy

Crear el proyecto Laravel desde cero con:
- Setup de Laravel 11 + Breeze (auth blade, sin Inertia)
- Migrations completas según el schema de CONTEXT.md
- Models con relaciones Eloquent
- Estructura de carpetas profesional
- Setup de variables de entorno
- Git inicializado y conectado a GitHub privado

**Antes de programar nada, hazme las preguntas que necesites** sobre:
- Si tengo Laravel Herd / XAMPP / Laragon instalado
- Si tengo Composer y PHP 8.2+ instalados
- Si tengo cuentas creadas en GitHub, Anthropic Console, Stripe
- Si el dominio ya está comprado
- Si ya verifiqué que mi WHM tiene PHP 8.2+ y MySQL listos
- Cualquier otra cosa que necesites saber

Después arrancamos paso a paso. **No corras a programar** — quiero entender cada decisión.

---

## Reglas importantes

1. **Háblame en "usted"** — no "tú"
2. **Tono directo Hormozi** — sin floreos, al grano
3. **Honestidad técnica** — si algo no es óptimo, dímelo claramente
4. **Soy programador con experiencia** (.NET MVC, C#, React, jQuery) pero llevaba tiempo sin programar — explícame el por qué de cada decisión
5. **Soy atleta y fundador** — el producto lo voy a usar yo mismo
6. **Optimiza para gratis** — no me sugieras servicios pagos a menos que sea absolutamente necesario
7. **No uses build steps innecesarios** — Tailwind y Alpine por CDN, sin Vite

Empecemos.
