# Contexto técnico para Claude Code

> **Para el Claude que va a continuar este proyecto en VS Code:**
> Lea este archivo COMPLETO antes de empezar. Toma 5 minutos y le ahorra horas.

---

## Quién es el usuario

**Chicho** — programador con experiencia (.NET MVC, C#, React, jQuery, Bootstrap), pero llevaba tiempo sin programar activamente. Atleta CrossFit Games Champion 2024 y fundador de PerfectaMENTE + Staygold Software.

**Cómo le gusta que le hablen:**
- Siempre "usted" (no "tú")
- Tono directo, Hormozi-style. Sin azúcar.
- Honestidad técnica: si algo no sirve, decir por qué.
- Pasos concretos, no teoría
- OK pushback si tiene mejor argumento

**Cómo trabajar con él:**
- Le gusta entender el "por qué" antes del "cómo"
- Aprecia análisis de negocio, no solo código
- Cuando da feedback, suele tener razón (es el cliente final)
- Le importa el costo. Optimiza para gratis o casi gratis.

---

## Estado actual del proyecto

**Ya hecho (en HTML como prototipo):**
- ✅ Landing page de venta
- ✅ Demo móvil funcional con:
  - Onboarding simulado de extracción IA
  - Tab "Hoy" con selector de modo (descanso/entreno/competencia)
  - Cards dinámicos según modo
  - Modal de check con detalle completo
  - Tab "Dashboard" con heatmap y análisis IA
  - Tab "Mi Plan" con lista de permitidos/evitar
  - Sistema de notificaciones locales
- ✅ JSON schema del plan validado con plan FODMAP real

**Falta (lo siguiente que toca construir):**

### Iteración 1 — MVP funcional (1-2 semanas)
1. Setup Laravel 11 + Breeze (auth)
2. Migrations: profiles, nutritional_plans, daily_checks, daily_modes, affiliate_referrals
3. Migrar landing HTML → `landing.blade.php`
4. Onboarding: subida PDF + endpoint `POST /api/extract-plan` (Claude API)
5. App core: tabs Hoy/Dashboard/Mi Plan en Blade + Alpine
6. Sistema de checks (Fiel/Parcial/No fiel) persistido en DB
7. Deploy a WHM por SSH

### Iteración 2 — Monetización (1 semana)
8. Laravel Cashier + Stripe Checkout
9. Webhook de Stripe
10. Página de billing
11. Gates de features (free vs pro)

### Iteración 3 — Afiliados (1 semana)
12. Sistema de códigos `/r/{codigo}`
13. Tracking de referidos con Stripe Connect
14. Dashboard de comisiones

### Iteración 4 — Retención
15. Cron jobs para emails (WHM crontab)
16. Email semanal con resumen + insight IA
17. Reportes PDF exportables

---

## Stack a usar

```
Local dev:    Laravel Herd (Mac) / XAMPP / Laragon (Windows)
Backend:      Laravel 11 + PHP 8.2+
DB:           MySQL/MariaDB (en WHM)
Templates:    Blade (server-side rendering)
Reactividad:  Alpine.js (CDN, sin build)
Estilos:      Tailwind CSS (CDN, sin build)
Auth:         Laravel Breeze (email + password, sin Inertia)
AI:           Claude API vía Guzzle/cURL
Pagos:        Laravel Cashier + Stripe
Storage:      Storage::disk('local') o disco WHM
Email:        Laravel Mail con SMTP del WHM
Cache:        File driver
Queue:        Database driver
Deploy:       Git pull por SSH al WHM
```

**NO usar:**
- ❌ Vite/Webpack para CSS (Tailwind por CDN)
- ❌ Node.js dependencies
- ❌ Inertia/Livewire (queremos simpleza)
- ❌ Vue/React (Alpine es suficiente)
- ❌ Redis/Memcached (file/db drivers son OK)
- ❌ Servicios pagos como Vercel/Supabase

---

## Schema de base de datos

```php
// database/migrations/xxxx_create_profiles_table.php
Schema::create('profiles', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('nombre')->nullable();
    $table->enum('plan_tier', ['free', 'pro'])->default('free');
    $table->string('stripe_customer_id')->nullable()->unique();
    $table->string('stripe_subscription_id')->nullable();
    $table->timestamp('trial_ends_at')->nullable();
    $table->string('affiliate_code')->nullable()->unique();
    $table->string('referred_by_code')->nullable();
    $table->json('calendario_entreno')->default(json_encode([
        'lun' => true, 'mar' => true, 'mie' => true, 'jue' => true,
        'vie' => true, 'sab' => true, 'dom' => false
    ]));
    $table->timestamps();
});

// xxxx_create_nutritional_plans_table.php
Schema::create('nutritional_plans', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('pdf_path')->nullable();
    $table->longText('raw_text')->nullable();
    $table->json('extracted_data');
    $table->string('metodologia')->nullable();
    $table->string('objetivo_principal')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// xxxx_create_daily_checks_table.php
Schema::create('daily_checks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('nutritional_plan_id')->constrained()->onDelete('cascade');
    $table->date('date');
    $table->string('item_id'); // 'desayuno', 'almuerzo', etc.
    $table->enum('status', ['fiel', 'parcial', 'nofiel']);
    $table->text('note')->nullable();
    $table->enum('mode', ['descanso', 'entreno', 'competencia'])->default('descanso');
    $table->timestamps();
    $table->unique(['user_id', 'date', 'item_id']);
});

// xxxx_create_daily_modes_table.php
Schema::create('daily_modes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->date('date');
    $table->enum('mode', ['descanso', 'entreno', 'competencia']);
    $table->timestamps();
    $table->unique(['user_id', 'date']);
});

// xxxx_create_affiliate_referrals_table.php
Schema::create('affiliate_referrals', function (Blueprint $table) {
    $table->id();
    $table->foreignId('affiliate_user_id')->constrained('users');
    $table->foreignId('referred_user_id')->constrained('users');
    $table->enum('status', ['pending', 'active', 'cancelled'])->default('pending');
    $table->decimal('total_earned_usd', 10, 2)->default(0);
    $table->timestamps();
    $table->unique('referred_user_id');
});
```

---

## Modelos Eloquent recomendados

```php
// app/Models/User.php (extender el de Breeze)
class User extends Authenticatable {
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function profile() {
        return $this->hasOne(Profile::class);
    }

    public function nutritionalPlans() {
        return $this->hasMany(NutritionalPlan::class);
    }

    public function activeNutritionalPlan() {
        return $this->hasOne(NutritionalPlan::class)->where('is_active', true);
    }

    public function dailyChecks() {
        return $this->hasMany(DailyCheck::class);
    }
}

// app/Models/NutritionalPlan.php
class NutritionalPlan extends Model {
    protected $casts = [
        'extracted_data' => 'array',
        'is_active' => 'boolean',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function checks() {
        return $this->hasMany(DailyCheck::class);
    }
}

// app/Models/DailyCheck.php
class DailyCheck extends Model {
    protected $casts = ['date' => 'date'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function plan() {
        return $this->belongsTo(NutritionalPlan::class, 'nutritional_plan_id');
    }
}
```

---

## Servicio de extracción de plan con Claude API

```php
// app/Services/ClaudeExtractorService.php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class ClaudeExtractorService
{
    private string $apiKey;
    private string $model = 'claude-opus-4-5';

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.key');
    }

    public function extractPlan(string $pdfPath): array
    {
        $pdfBase64 = base64_encode(file_get_contents($pdfPath));

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->model,
            'max_tokens' => 8000,
            'messages' => [[
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'document',
                        'source' => [
                            'type' => 'base64',
                            'media_type' => 'application/pdf',
                            'data' => $pdfBase64,
                        ],
                    ],
                    [
                        'type' => 'text',
                        'text' => $this->getExtractionPrompt(),
                    ],
                ],
            ]],
        ]);

        if (!$response->successful()) {
            throw new \Exception('Claude API failed: ' . $response->body());
        }

        $jsonText = $response->json('content.0.text');

        // Limpiar markdown si viene
        $jsonText = preg_replace('/^```json\s*|\s*```$/m', '', trim($jsonText));

        return json_decode($jsonText, true, 512, JSON_THROW_ON_ERROR);
    }

    private function getExtractionPrompt(): string
    {
        return <<<PROMPT
Eres un asistente experto en extraer planes nutricionales de documentos.

Te paso un PDF con un plan nutricional. Extrae TODA la información en JSON:

{
  "paciente": { "nombre": "string|null", "edad": "number|null", "altura_cm": "number|null", "peso_kg": "number|null" },
  "objetivos": { "principal": "string", "secundario": "string|null" },
  "metodologia": "string (ej: FODMAP, Low Carbs)",
  "comidas": [{
    "id": "slug-en-minusculas",
    "nombre": "string",
    "hora": "HH:MM en formato 24h",
    "icono_sugerido": "emoji",
    "descripcion_plan": "string",
    "opciones": ["string"],
    "tip": "string|null",
    "notas": ["string"]
  }],
  "comidas_entreno": [...],
  "comidas_competencia": [...],
  "suplementos_diarios": [...],
  "suplementos_entreno": [...],
  "permitidos": {
    "vegetales": [...], "ensaladas": [...], "proteinas": [...],
    "tuberculos": [...], "bebidas": [...], "especias": [...],
    "snacks_ansiedad": [...]
  },
  "evitar": [...],
  "comida_libre": "string|null",
  "validacion": { "completitud": "alta|media|baja", "advertencias": ["..."] }
}

REGLAS CRÍTICAS:
1. EXCLUIR de la app: testosterona, HCG, anabólicos, hormonas. Responsabilidad médica.
2. Conservar TODAS las opciones del plan, no resumir.
3. Si detecta variantes por entreno vs descanso, separarlas.
4. Devolver SOLO el JSON. Sin markdown, sin backticks, sin explicaciones.
PROMPT;
    }
}
```

Configurar en `config/services.php`:

```php
'anthropic' => [
    'key' => env('ANTHROPIC_API_KEY'),
],
```

---

## Variables de entorno

```bash
# .env
APP_NAME=PerfectaMENTE
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://perfectamente.app

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

ANTHROPIC_API_KEY=

STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=

MAIL_MAILER=smtp
MAIL_HOST=mail.suserver.com
MAIL_PORT=587
MAIL_USERNAME=noreply@perfectamente.app
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@perfectamente.app
MAIL_FROM_NAME="PerfectaMENTE"
```

---

## Comandos de arranque

```bash
# 1. Crear proyecto Laravel
composer create-project laravel/laravel perfectamente-coach
cd perfectamente-coach

# 2. Instalar Breeze (auth simple sin Inertia)
composer require laravel/breeze --dev
php artisan breeze:install blade

# 3. Instalar Cashier para Stripe
composer require laravel/cashier

# 4. Migrar
php artisan migrate

# 5. Setup git
git init
git add .
git commit -m "Initial commit Laravel"
gh repo create perfectamente-coach --private --source=. --remote=origin --push

# 6. Servidor local
php artisan serve
# Visitar http://localhost:8000
```

---

## Estructura de rutas sugerida

```php
// routes/web.php
Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/r/{code}', [ReferralController::class, 'capture']);

// Auth (Breeze ya las crea)
// /login, /register, /logout

Route::middleware('auth')->group(function () {
    // Onboarding
    Route::get('/onboarding', [OnboardingController::class, 'show']);
    Route::post('/onboarding/upload-pdf', [OnboardingController::class, 'uploadPdf']);

    // App
    Route::get('/app', [AppController::class, 'hoy'])->name('app.hoy');
    Route::get('/app/dashboard', [AppController::class, 'dashboard'])->name('app.dashboard');
    Route::get('/app/plan', [AppController::class, 'plan'])->name('app.plan');

    // Acciones
    Route::post('/api/checks', [CheckController::class, 'store']);
    Route::post('/api/mode', [ModeController::class, 'store']);

    // Billing
    Route::get('/billing', [BillingController::class, 'show']);
    Route::post('/billing/checkout', [BillingController::class, 'checkout']);

    // Afiliados (solo nutricionistas)
    Route::get('/coach', [CoachController::class, 'dashboard']);
});

// Webhooks de Stripe (sin auth)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);
```

---

## Archivos de referencia visual

En `references/` hay archivos del prototipo. **Son la fuente de verdad visual**:

- `perfectamente-demo-mobile.html` → estructura de toda la app móvil
- `perfectamente-coach-landing.html` → landing page completa
- `plan_extraido.json` → ejemplo real de JSON del extractor

**Misión al migrar:** preservar 100% el diseño visual y el copy. Convertir HTML/JS a templates Blade + Alpine.

---

## Decisiones tomadas que NO se cambian

1. **Idioma siempre "usted"** — decisión de marca
2. **Sin gradientes saturados de color** como fondos de cards
3. **No incluir hormonas** en la app (responsabilidad médica)
4. **Modos del día separados** (no mezclar entreno con descanso)
5. **Free tier limitado pero útil** (1 plan, 7 días historial)
6. **Pro a $7/mes** — alineado con PerfectaMENTE Crash Course
7. **Afiliados 30%** — punto de venta clave para nutricionistas
8. **Stack PHP/Laravel** — ya tiene WHM, no usar SaaS pagos
9. **Tailwind y Alpine por CDN** — cero build step

---

## Pregunta filosófica del fundador

> *"¿Esto que estamos construyendo lo va a usar usted mismo todos los días?"*

Si la respuesta es no, hay que reconsiderar. Chicho construye lo que él mismo necesita primero. Esa es la garantía de calidad.

---

*Suerte. Construya algo que valga la pena.*
