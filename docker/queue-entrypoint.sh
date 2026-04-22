#!/bin/bash
set -e

cd /var/www/html

# Wait for the app container to finish setup (vendor must exist)
echo ">>> Queue worker waiting for app setup ..."
until [ -f vendor/autoload.php ] && [ -f .env ]; do
    sleep 3
done

# Wait for MySQL
echo ">>> Waiting for MySQL ..."
DB_H="${DB_HOST:-mysql}"
DB_P="${DB_PORT:-3306}"
DB_U="${DB_USERNAME:-nubl}"
DB_PW="${DB_PASSWORD:-secret}"
until php -r "try{new PDO('mysql:host='.\$argv[1].';port='.\$argv[2],\$argv[3],\$argv[4]);}catch(Exception \$e){exit(1);}" "$DB_H" "$DB_P" "$DB_U" "$DB_PW" 2>/dev/null; do
    sleep 2
done

echo ">>> Starting queue worker ..."
exec "$@"
