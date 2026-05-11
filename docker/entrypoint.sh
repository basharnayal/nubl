#!/bin/bash
set -e

cd /var/www/html

# ── Create .env from example if missing ──────────────────────────────────────
if [ ! -f .env ]; then
    echo ">>> Creating .env from .env.example ..."
    cp .env.example .env

    # Override defaults for Docker networking
    sed -i 's/^DB_HOST=.*/DB_HOST=mysql/'           .env
    sed -i 's/^DB_DATABASE=.*/DB_DATABASE=nubl/'     .env
    sed -i 's/^DB_USERNAME=.*/DB_USERNAME=nubl/'     .env
    sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=secret/'   .env
    sed -i 's/^REDIS_HOST=.*/REDIS_HOST=redis/'      .env
    sed -i 's/^CACHE_STORE=.*/CACHE_STORE=redis/'    .env
    sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=redis/' .env
    sed -i 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/' .env

    echo ">>> .env created with Docker defaults."
fi

# ── Install PHP deps if vendor is missing ────────────────────────────────────
if [ ! -f vendor/autoload.php ]; then
    echo ">>> Running composer install ..."
    composer install --no-interaction --optimize-autoloader
fi

# ── Generate app key if empty ────────────────────────────────────────────────
if grep -q "^APP_KEY=$" .env 2>/dev/null; then
    echo ">>> Generating application key ..."
    php artisan key:generate --force
fi

# ── Install JS deps if node_modules is missing ──────────────────────────────
if [ ! -f node_modules/.package-lock.json ]; then
    echo ">>> Running npm install ..."
    npm install
fi

# ── Build frontend assets if public/build is missing ────────────────────────
if [ ! -d public/build ] || [ -z "$(ls -A public/build 2>/dev/null)" ]; then
    echo ">>> Building frontend assets ..."
    npm run build
fi

# ── Remove stale Vite hot file (leftover from a previously-running dev server) ──
# When public/hot exists, Laravel's @vite directive routes asset URLs to the
# Vite dev server (localhost:5173). If that container isn't running, pages load
# without CSS/JS. Deleting it forces fallback to the built manifest.
if [ -f public/hot ]; then
    echo ">>> Removing stale public/hot file ..."
    rm -f public/hot
fi

# ── Wait for MySQL to be ready ───────────────────────────────────────────────
echo ">>> Waiting for MySQL ..."
DB_H="${DB_HOST:-mysql}"
DB_P="${DB_PORT:-3306}"
DB_U="${DB_USERNAME:-nubl}"
DB_PW="${DB_PASSWORD:-secret}"
until php -r "try{new PDO('mysql:host='.\$argv[1].';port='.\$argv[2],\$argv[3],\$argv[4]);}catch(Exception \$e){exit(1);}" "$DB_H" "$DB_P" "$DB_U" "$DB_PW" 2>/dev/null; do
    sleep 2
done
echo ">>> MySQL is ready."

# ── Run migrations ───────────────────────────────────────────────────────────
echo ">>> Running migrations ..."
php artisan migrate --force

echo ">>> NUBL is ready! Starting server ..."
exec "$@"
