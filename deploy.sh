#!/usr/bin/env bash
# MEMO - server deploy. Code comes from git, data never does.
set -euo pipefail
cd "$(dirname "$0")"

say(){ printf "\n>> %s\n" "$1"; }

[ -f artisan ] || { echo "not a laravel root"; exit 1; }
[ -f .env ] || { echo ".env missing - create it once by hand"; exit 1; }

# APP_KEY wraps every video AES key. Changing it makes all encoded
# video permanently unplayable, so refuse to run without one.
grep -q "^APP_KEY=base64:" .env || { echo "APP_KEY empty - set once, never change"; exit 1; }

command -v ffmpeg >/dev/null || echo "WARNING: ffmpeg missing, transcoding will fail"

say "Backing up database"
mkdir -p storage/backups
if [ -f database/database.sqlite ]; then
  cp database/database.sqlite "storage/backups/db_$(date +%Y%m%d_%H%M%S).sqlite"
  ls -1t storage/backups/db_*.sqlite | tail -n +11 | xargs -r rm --
fi

say "Pulling main"
git fetch --all --prune
git reset --hard origin/main
git log -1 --pretty="%h %s"

say "Dependencies"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

say "Storage layout"
mkdir -p storage/app/private/{masters,hls,chunks,brand}
mkdir -p storage/app/public/brand
mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

say "Migrating"
php artisan migrate --force

say "Caches"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
[ -L public/storage ] || php artisan storage:link || true

say "Workers"
php artisan queue:restart || true

say "Done"