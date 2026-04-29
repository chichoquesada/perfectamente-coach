# Guía de Setup — Local + Deploy a WHM

## Parte 1: Setup Local (su PC)

### Paso 1: Instalar PHP y Composer

**Windows:**
1. Descargue **Laragon** (https://laragon.org/) — incluye PHP, MySQL, Composer, todo.
   - Alternativa: XAMPP (más viejo pero también funciona)
2. Instale PHP 8.2 o superior

**Mac:**
1. Instale **Laravel Herd** (https://herd.laravel.com/) — un click, todo listo.

**Linux:**
```bash
sudo apt install php8.2 php8.2-cli php8.2-mbstring php8.2-xml php8.2-curl php8.2-mysql php8.2-zip php8.2-bcmath
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
```

### Paso 2: Verificar instalación

Abra terminal y verifique:
```bash
php -v          # Debe mostrar PHP 8.2+
composer -v     # Debe mostrar Composer
```

### Paso 3: Cuentas necesarias (gratuitas)

| Servicio | URL | Para qué |
|----------|-----|----------|
| GitHub | github.com | Repo privado |
| Anthropic Console | console.anthropic.com | Claude API key (~$5 créditos free al inicio) |
| Stripe | stripe.com | Pagos (modo test gratis, solo cobra al vender) |
| Namecheap o GoDaddy | — | Dominio `perfectamente.app` (~$12/año) |

**No necesita:** Vercel, Supabase, Netlify (NADA de eso, todo va al WHM).

---

## Parte 2: Su servidor WHM/cPanel

### Lo que necesitamos verificar en su WHM

Antes de deploy, valide que tiene:

1. **PHP 8.2+** disponible
   - cPanel → "Select PHP Version" o "MultiPHP Manager"
   - Si solo tiene PHP 7.x: pídale al admin del WHM que active 8.2

2. **MySQL 5.7+** o **MariaDB 10.4+**
   - cPanel → "MySQL Databases"

3. **Acceso SSH** (idealmente)
   - cPanel → buscar "Terminal" o "SSH Access"
   - Si no tiene: se puede deploy por Git Version Control de cPanel

4. **Cron Jobs**
   - cPanel → "Cron Jobs"
   - Para Laravel scheduler

5. **Composer** instalado en el servidor
   - Si no está, se ejecuta vía SSH o se sube `vendor/` por FTP

### Crear la base de datos en cPanel

1. cPanel → MySQL Databases
2. Crear database: `perfectamente_db` (o el nombre que prefiera, cPanel le agrega prefijo)
3. Crear usuario MySQL con password fuerte
4. Asignar el usuario a la database con TODOS los privilegios
5. **Anote estos datos:**
   - DB name: `xxx_perfectamente_db`
   - DB user: `xxx_perfusr`
   - DB password: `[lo que puso]`
   - DB host: usualmente `localhost`

### Crear el subdominio (opcional pero recomendado)

Para tener `app.perfectamente.app` separado del marketing:
1. cPanel → Subdomains
2. Crear `app.perfectamente.app` apuntando a `/home/usuario/perfectamente-app/public`
3. (Cuando tenga el dominio comprado y apuntado al servidor)

---

## Parte 3: Deploy a WHM

### Opción A: Si tiene SSH (recomendado)

```bash
# Conectarse al servidor
ssh usuario@suserver.com

# Ir al directorio donde se va a alojar
cd ~/perfectamente-app

# Clonar el repo (privado, usar token de GitHub)
git clone https://github.com/SU_USUARIO/perfectamente-coach.git .

# Instalar dependencias
composer install --no-dev --optimize-autoloader

# Configurar .env
cp .env.example .env
nano .env  # editar con datos reales del WHM

# Generar app key
php artisan key:generate

# Migrar DB
php artisan migrate --force

# Cache de configs (mejora performance)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Permisos
chmod -R 755 storage bootstrap/cache
```

**En cPanel, configurar el Document Root:**
- El dominio/subdominio debe apuntar a `/home/usuario/perfectamente-app/public` (NO a `perfectamente-app/` directo)
- Si no se puede cambiar el doc root, mover el contenido de `public/` al `public_html/` y editar `index.php` para que apunte a `../perfectamente-app/`

### Opción B: Sin SSH, solo cPanel + Git Version Control

1. cPanel → "Git Version Control" (en la sección Files)
2. "Create" → conectar el repo de GitHub (necesita SSH key del WHM agregada a GitHub)
3. Clone path: `/home/usuario/perfectamente-app`
4. Hacer "Pull or Deploy" cuando hay cambios

Para `composer install` sin SSH:
- Subir el folder `vendor/` por FTP (más pesado pero funciona)
- O contactar al admin del WHM para que ejecute composer

### Cron job para Laravel scheduler

cPanel → "Cron Jobs" → agregar:
```
* * * * * cd /home/usuario/perfectamente-app && php artisan schedule:run >> /dev/null 2>&1
```

---

## Parte 4: Workflow del día a día

### Para hacer un cambio:

```bash
# 1. En su PC
git checkout -b nueva-feature
# ... programa con Claude Code ...
git add .
git commit -m "Agregada feature X"
git push origin nueva-feature

# 2. Merge en GitHub (vía PR o directo a main)

# 3. En el servidor (SSH)
ssh usuario@suserver.com
cd ~/perfectamente-app
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan view:cache
```

### Script de deploy automático

Cree `deploy.sh` en el servidor para no escribir lo mismo siempre:

```bash
#!/bin/bash
cd ~/perfectamente-app
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "Deploy completo: $(date)"
```

Hacer ejecutable: `chmod +x deploy.sh`
Usar con: `./deploy.sh`

---

## Parte 5: Checklist antes de salir a producción

- [ ] `.env` con `APP_ENV=production` y `APP_DEBUG=false`
- [ ] `APP_KEY` generado (`php artisan key:generate`)
- [ ] DB credentials correctas en `.env`
- [ ] HTTPS habilitado en cPanel (Let's Encrypt gratis)
- [ ] `storage/` y `bootstrap/cache/` con permisos 755
- [ ] Configs cacheadas (`php artisan config:cache`)
- [ ] Cron job de scheduler activo
- [ ] Backup automatizado de DB (cPanel → Backups)
- [ ] Stripe en modo Live (cuando esté listo a vender)
- [ ] Anthropic API key en `.env` (no hardcoded)
- [ ] Email SMTP configurado y probado

---

## Costo total real

| Item | Costo |
|------|-------|
| WHM hosting | $0 (ya pagado) |
| Dominio `perfectamente.app` | ~$12/año |
| Claude API | ~$0.05 por extracción de plan (cobrado solo cuando se usa) |
| Stripe | 2.9% + $0.30 por venta (cobrado solo cuando vende) |
| **Costo fijo mensual** | **$1** |

Comparado con stack Vercel/Supabase: $45+/mes desde día 1.

---

## Cuando dude algo

Lea siempre primero:
1. `README.md` — visión del producto
2. `CONTEXT.md` — decisiones técnicas
3. Documentación oficial de Laravel: https://laravel.com/docs/11.x

Y si está atascado, el primer chat con Claude Code lo guía paso a paso.
