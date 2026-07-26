#!/usr/bin/env bash
# ============================================================================
# RISA-ERP — Deployment Script (Hostinger Shared Hosting)
# ============================================================================
# Dijalankan via SSH setelah `git pull` (atau via Hostinger auto-deploy hook).
#
# USAGE (manual):
#   ssh u123456@hostinger.com
#   cd domains/erp.namadomain.co.id/public_html
#   bash deploy.sh
#
# USAGE (auto via hPanel Git):
#   Set "Command to run after deployment": bash deploy.sh
# ============================================================================

set -euo pipefail

echo "▶ RISA-ERP deploy started at $(date '+%Y-%m-%d %H:%M:%S')"

# ------------------------------------------------------------------------
# 1. Cek prasyarat
# ------------------------------------------------------------------------
if [ ! -f .env ]; then
    echo "❌ .env tidak ditemukan. Copy .env.production.example → .env dan isi dulu."
    exit 1
fi

if [ ! -f artisan ]; then
    echo "❌ File artisan tidak ada — bukan Laravel project atau salah folder."
    exit 1
fi

# Cek PHP version (Hostinger biasanya alias php8.3)
PHP_BIN=$(command -v php8.3 || command -v php)
echo "▶ Menggunakan PHP: $($PHP_BIN -v | head -1)"

# ------------------------------------------------------------------------
# 2. Maintenance mode ON (user lihat "Be right back")
# ------------------------------------------------------------------------
$PHP_BIN artisan down --render="errors::503" --retry=30 || true

# ------------------------------------------------------------------------
# 3. Composer install (production, no-dev)
# ------------------------------------------------------------------------
echo "▶ Composer install..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# ------------------------------------------------------------------------
# 3b. Auto-generate APP_KEY kalau kosong (first deploy)
# ------------------------------------------------------------------------
if ! grep -qE '^APP_KEY=base64:.+' .env; then
    echo "▶ APP_KEY kosong — generate baru..."
    $PHP_BIN artisan key:generate --force --no-interaction
fi

# ------------------------------------------------------------------------
# 4. Migrasi database (force karena non-interactive)
# ------------------------------------------------------------------------
echo "▶ Menjalankan migration..."
$PHP_BIN artisan migrate --force --no-interaction

# Cek apakah tabel roles kosong (first deploy) → warn user untuk seed
if $PHP_BIN artisan tinker --execute="exit(\Spatie\Permission\Models\Role::count() === 0 ? 0 : 1);" 2>/dev/null; then
    echo ""
    echo "⚠  Role masih kosong — ini deploy pertama. Setelah script selesai, jalankan:"
    echo "     bash seed-initial.sh"
    echo ""
fi

# ------------------------------------------------------------------------
# 5. Storage symlink (idempotent, aman dijalankan berkali-kali)
# ------------------------------------------------------------------------
$PHP_BIN artisan storage:link --force || true

# ------------------------------------------------------------------------
# 6. Clear + cache config supaya prod lebih cepat
# ------------------------------------------------------------------------
echo "▶ Rebuild cache..."
$PHP_BIN artisan config:clear
$PHP_BIN artisan route:clear
$PHP_BIN artisan view:clear
$PHP_BIN artisan event:clear

$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache
$PHP_BIN artisan event:cache

# Filament cache (opsional — mempercepat resource discovery)
$PHP_BIN artisan filament:cache-components || true

# ------------------------------------------------------------------------
# 7. Icons + shield permissions (idempotent)
# ------------------------------------------------------------------------
$PHP_BIN artisan icons:cache || true

# Kalau ada permission baru dari resource baru, refresh Shield
# $PHP_BIN artisan shield:generate --all --panel=admin --no-interaction || true

# ------------------------------------------------------------------------
# 8. File permissions
# ------------------------------------------------------------------------
echo "▶ Set permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chmod 600 .env 2>/dev/null || true

# ------------------------------------------------------------------------
# 9. Maintenance mode OFF
# ------------------------------------------------------------------------
$PHP_BIN artisan up

echo "✅ Deploy selesai — $(date '+%Y-%m-%d %H:%M:%S')"
echo "▶ Commit: $(git log -1 --format='%h %s' 2>/dev/null || echo 'N/A')"
echo ""
echo "Cek: https://erp.<namadomain>.co.id"
