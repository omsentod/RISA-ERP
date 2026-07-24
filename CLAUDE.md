# RISA ERP — Panduan Project untuk Claude

Sistem ERP internal untuk **produsen implan tulang (orthopedic implant)**. Industri alkes yang regulated (ISO 13485, CDAKB, BPOM).

## Stack

- **Backend**: Laravel 12 + PHP 8.3
- **Admin panel**: Filament 3
- **Database**: MySQL 8 (via MAMP lokal port 8889, socket `/Applications/MAMP/tmp/mysql/mysql.sock`)
- **Frontend**: Blade + Livewire (via Filament) + Vite
- **Hosting production**: Shared hosting Hostinger (PHP 8.3.30, MySQL, git deployment, cron support)

## Constraint Hosting yang Wajib Diingat

Karena deploy ke **shared hosting Hostinger**, hindari fitur yang butuh long-running process:

- **JANGAN pakai queue async default** — pakai `sync` driver atau `database` + schedule cron
- **JANGAN pakai WebSocket / Broadcasting real-time** yang butuh service terpisah
- **JANGAN asumsikan ada Redis** — pakai `database` driver untuk cache/session/queue
- Cron jalan via 1 entry: `* * * * * php artisan schedule:run` — semua schedule ditulis di kode Laravel
- Import file besar → batasi ukuran (contoh: max 1000 baris per batch), jangan andalkan queue worker
- **Composer install di-lock ke PHP 8.3.30** (`config.platform.php` di composer.json) supaya package tidak pull dependency yang butuh PHP > 8.3

## Struktur Project (DDD lite)

```
app/
  Domain/                          # Business logic per bounded context
    Product/
      Models/                      # Eloquent models
      Actions/                     # Single-purpose business actions (invokable)
      Data/                        # DTOs / Data objects
    Registration/                  # NIE (Nomor Izin Edar) domain
      Models/
      Actions/
      Data/
    Shared/                        # Cross-domain utilities
      Concerns/                    # Traits reusable antar domain
      Enums/                       # Enum global
      Support/                     # Helper classes
  Filament/                        # Admin panel resources (di-generate saat butuh)
    Resources/
  Http/                            # Standard Laravel controllers/middleware
  Models/                          # (kosong — semua model pindah ke Domain/*/Models)
  Providers/
```

**Aturan:**
- Model baru → `app/Domain/<Context>/Models/`, namespace `App\Domain\<Context>\Models`
- Business logic kompleks → **Action class** di `app/Domain/<Context>/Actions/`, single method `handle()` atau `__invoke()`
- DTO untuk transfer data → `app/Domain/<Context>/Data/` (bisa pakai `spatie/laravel-data` nanti)
- Model TIDAK menyentuh model dari domain lain langsung — pakai Action / Query service
- Filament Resource boleh langsung akses Model (Filament = presentation layer)

## Konvensi Naming

| Item | Konvensi | Contoh |
|---|---|---|
| Table | plural snake_case | `products`, `product_registrations` |
| Model | singular PascalCase | `Product`, `Registration` |
| Migration | descriptive snake_case | `2026_07_25_create_products_table` |
| Column | snake_case | `product_code`, `nie_number` |
| PK | `id` (bigint auto-increment) | — |
| FK | `<singular>_id` | `product_id`, `registration_id` |
| Boolean | prefix `is_` / `has_` / `can_` | `is_published`, `has_expired` |
| Timestamp | suffix `_at` | `published_at`, `expired_at` |
| Enum | English lowercase snake_case | `status = 'active'` |
| Route | plural kebab-case | `/products`, `/product-registrations` |

## Aturan Database & Migration

- **Semua tabel master data** wajib punya: `id`, `created_at`, `updated_at`, `deleted_at` (soft delete)
- **Tabel transaksi/audit** wajib punya kolom user pencipta: `created_by`, `updated_by`, `deleted_by` (nullable FK ke `users`)
- **Field yang publish ke website** (via `RISA-APP` DB) — tandai di model dengan attribute/comment
- **NIE (Nomor Izin Edar)** disimpan di tabel terpisah `registrations`, produk many-to-many via pivot — 1 NIE bisa cover banyak SKU
- **Nomor Izin Edar expiry** wajib divalidasi sebelum tampil "aktif" di UI
- Migration harus **reversible** — `down()` method wajib diisi
- **Jangan pakai fitur MySQL-spesifik** yang tidak ada di MariaDB (untuk portabilitas)

## Aturan Filament Resource

- Setiap resource wajib punya: `getGloballySearchableAttributes()`, `getNavigationBadge()` kalau relevant
- Form field wajib punya validation rules eksplisit — jangan andalkan casts saja
- Kolom sensitif (harga, cost) — pakai `->visible(fn() => auth()->user()->can(...))` untuk permission gating
- Bulk action destructive (delete, force delete) — wajib pakai `->requiresConfirmation()`
- Filter untuk soft-deleted → `TrashedFilter::make()`
- Export/import bulk → pakai action async kalau ukuran > 100 records (walau di shared hosting jalan sync)

## Environment & Koneksi Database

- **Primary**: `mysql` connection → database `risa_erp` (ERP data utama)
- **Secondary**: `company_profile` connection → database `RISA-APP` (untuk fitur Publish/Takedown produk ke website — Fase 2+)
- Kalau perlu akses secondary: `DB::connection('company_profile')->table('products')->...`
- **JANGAN write ke `company_profile` tanpa via Action khusus** (misal `PublishProductToWebsite`) supaya audit trail-nya jelas

## Git Workflow

- Branch main: `main` (production-ready)
- Branch feature: `feature/<nama-fitur>` (contoh: `feature/product-crud`)
- Branch fix: `fix/<nama-issue>` (contoh: `fix/nie-expiry-validation`)
- Commit style: **Conventional Commits** — `feat:`, `fix:`, `refactor:`, `docs:`, `chore:`, `test:`
- Setiap commit ideally < 300 baris diff

## Testing

- Framework: PHPUnit (bawaan Laravel 12)
- Test wajib untuk: **Action classes** (business logic), **Model relationships**, **Feature test untuk endpoint kritis**
- Test menggunakan `RefreshDatabase` trait
- Filament resource tests pakai `Livewire::test(...)`

## Yang Tidak Dilakukan Otomatis oleh Claude

- **Jangan** jalankan `git push` tanpa konfirmasi eksplisit
- **Jangan** run `migrate:fresh` di database production
- **Jangan** modify `RISA-APP` database (baca-only kecuali ada Action khusus)
- **Jangan** commit `.env`, `storage/app/private/*`, `vendor/`, `node_modules/`
- **Jangan** hardcode credentials di config file
- **Jangan** delete file di `storage/app/reference/` (data referensi dari direktur)

## Perintah yang Sering Dipakai

```bash
# Development
php artisan serve                    # jalankan dev server (port 8000)
npm run dev                          # Vite dev server
composer dev                         # jalankan server + queue + logs + vite bersamaan

# Database
php artisan migrate:fresh --seed     # reset DB + seed (HATI-HATI, drop semua)
php artisan migrate                  # apply pending migrations
php artisan db:seed                  # jalankan seeder

# Filament
php artisan make:filament-user       # bikin admin user baru
php artisan make:filament-resource   # scaffold resource baru

# Code quality
./vendor/bin/pint                    # format PHP (auto)
./vendor/bin/pint --test             # cek format tanpa modify
php artisan test                     # jalankan test suite
```

## Referensi Data

- `storage/app/reference/BARCODE.xlsx` — master data produk dari direktur (896 non-locking + 694 locking implant)
- Kolom: `Spesifikasi | Kode | Nama Produk | NIE`
- Ini basis untuk seeder `ProductSeeder` (Fase MVP)

## Domain Knowledge Alkes

- **NIE (Nomor Izin Edar)** — izin BPOM/Kemenkes, berlaku 5 tahun, 1 NIE bisa cover banyak SKU. Contoh format: `AKD 21302420095`
- **Lot / Batch** — sekelompok produk yang diproduksi bersama, wajib traceable per unit untuk keperluan recall
- **Konsinyasi** — stok fisik di RS tapi milik perusahaan (loan set), harus terpisah di sistem
- **Expiry** — 2 jenis: expiry produk (sterilisasi) & expiry NIE (izin edar). Sistem harus alert keduanya
- **FEFO** (First Expired First Out) — bukan FIFO, prioritas keluarkan lot yang paling dekat expiry
