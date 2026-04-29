# PerfectaMENTE Coach

La app que lo hace fiel a su plan. Suba el PDF de su nutricionista. La IA lo lee, lo organiza y le entrega un tablero diario donde solo tiene que hacer check.

## Documentación

- [Visión del producto](docs/README.md)
- [Decisiones técnicas](docs/CONTEXT.md)
- [Setup local + deploy WHM](docs/SETUP.md)
- [Quick start](docs/QUICK-START.md)

## Stack

Laravel 11 + Blade + Alpine.js (CDN) + Tailwind (CDN) + MySQL + Gemini API + Stripe.

Sin build step. Deploy por `git pull` SSH al WHM.

## Local dev

```bash
# Crear DB en MySQL local
mysql -u root -p -e "CREATE DATABASE perfectamente_coach"

# Configurar .env (DB_PASSWORD, GEMINI_API_KEY)
cp .env.example .env
php artisan key:generate

# Migrar
php artisan migrate

# Servidor
php artisan serve
```

## Referencias visuales

`references/` contiene los HTML del prototipo validado. **Fuente de verdad visual.**
