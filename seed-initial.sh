#!/usr/bin/env bash
# ============================================================================
# RISA-ERP — Initial Seed Script (ONE-TIME only!)
# ============================================================================
# Jalankan MANUAL sekali saat first deploy. JANGAN dipanggil dari deploy.sh.
#
# USAGE:
#   bash seed-initial.sh
#
# CATATAN:
# - Idempotent — aman dijalankan ulang, tapi tidak perlu
# - AdminUserSeeder akan RESET password admin ke 'password' kalau dijalankan
#   ulang → jangan lupa ganti password admin setelah seed pertama
# - ProductSeeder butuh file storage/app/reference/BARCODE.xlsx (upload
#   manual dari lokal via SFTP kalau belum ada)
# ============================================================================

set -euo pipefail

echo "▶ RISA-ERP initial seed started at $(date '+%Y-%m-%d %H:%M:%S')"

if [ ! -f .env ]; then
    echo "❌ .env tidak ada. Setup .env dulu."
    exit 1
fi

PHP_BIN=$(command -v php8.3 || command -v php)

# ---------------------------------------------------------------------------
# Konfirmasi (kalau interaktif)
# ---------------------------------------------------------------------------
if [ -t 0 ]; then
    read -p "⚠  Script ini akan seed data awal (Role, Admin User, Product). Lanjutkan? [y/N] " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Dibatalkan."
        exit 0
    fi
fi

# ---------------------------------------------------------------------------
# 1. Role & Permission (Filament Shield)
# ---------------------------------------------------------------------------
echo "▶ Generate Shield permissions..."
$PHP_BIN artisan shield:generate --all --panel=admin --no-interaction || true

echo "▶ Seed Role..."
$PHP_BIN artisan db:seed --class=RoleSeeder --force --no-interaction

# ---------------------------------------------------------------------------
# 2. Department & Admin User
# ---------------------------------------------------------------------------
echo "▶ Seed Department..."
$PHP_BIN artisan db:seed --class=DepartmentSeeder --force --no-interaction

echo "▶ Seed Admin User..."
$PHP_BIN artisan db:seed --class=AdminUserSeeder --force --no-interaction

echo ""
echo "⚠  PENTING: password admin di-set ke 'password' (default seeder)."
echo "   GANTI SEKARANG:"
echo "     php artisan tinker"
echo "     >>> \App\Models\User::first()->update(['password' => \Hash::make('PasswordBaru123!')]);"
echo ""

# ---------------------------------------------------------------------------
# 3. Master Product (dari BARCODE.xlsx)
# ---------------------------------------------------------------------------
if [ -f storage/app/reference/BARCODE.xlsx ]; then
    echo "▶ Seed Product (import BARCODE.xlsx — ~2 menit)..."
    $PHP_BIN artisan db:seed --class=ProductSeeder --force --no-interaction
else
    echo "⚠  storage/app/reference/BARCODE.xlsx tidak ditemukan."
    echo "   Upload file via SFTP ke path itu, lalu jalankan:"
    echo "     php artisan db:seed --class=ProductSeeder --force"
fi

echo ""
echo "✅ Initial seed selesai — $(date '+%Y-%m-%d %H:%M:%S')"
