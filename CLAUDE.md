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

## Aturan UI & Branding

**Kalau butuh customize UI, konsultasi ke subagent `ui-guardian` dulu.** Ringkasan aturannya:

**Hierarki customization (SELALU mulai dari yang paling ringan):**
1. **Panel config** di `AdminPanelProvider.php` (colors, brand, favicon, dark mode) — 5 menit
2. **Custom CSS theme** di `resources/css/filament/admin/theme.css` — untuk styling detail
3. **Publish Blade views** — hanya kalau butuh restructure HTML (hati-hati, upgrade cost)
4. **Custom Filament Page** dengan Blade sendiri di `resources/views/filament/pages/` — untuk non-CRUD
5. **Custom Widget/Field** — kalau built-in Filament tidak cukup

**Konvensi asset:**
- Brand assets di `public/assets/images/` — akses via `asset('assets/images/...')`
- Logo butuh 2 versi (light + dark background) kalau tidak neutral
- Favicon: PNG 512×512 di `public/assets/images/favicon-square.png`
- Compress image > 500 KB sebelum commit

**Aturan mutlak:**
- **Jangan** edit file di `vendor/` (hilang saat composer update)
- **Jangan** hardcode hex color di PHP/Blade — pakai `Color::` constant atau CSS var
- **Jangan** pakai `<script src="https://cdn...">` — install via npm, bundle via Vite
- **Wajib** test dark mode + responsive setiap kali ada custom CSS
- **Wajib** pakai `<x-filament-panels::page>` wrapper di custom Blade page

**Widget di `app/Filament/Widgets/`:**
- Set `$sort` (gap 10: 1, 10, 20, 30) supaya bisa insert baru tanpa renumbering
- Set `$columnSpan` eksplisit (`'full'`, `1`, `2`, atau per-breakpoint)
- Query > 100ms → wrap `Cache::remember(...)` 5 menit

**Interaksi & Navigasi — JANGAN buka tab/route baru untuk print/preview/quick-action:**
- Print, preview, quick edit, konfirmasi → pakai **modal Filament** atau **inline iframe via `$livewire->js()`**
- **Jangan** pakai `->openUrlInNewTab()` atau redirect ke route baru untuk flow print. Sidebar/topbar bisa render beda antar halaman → user bingung + kehilangan konteks
- **Jangan** bikin Filament Page hidden (`$shouldRegisterNavigation = false`) hanya untuk serve URL cetak — render HTML inline, base64-encode, inject via JS ke hidden iframe, trigger `iframe.contentWindow.print()`
- Contoh pattern print inline: lihat `app/Domain/Product/Actions/BuildPrintBarcodeJs.php`
- Tab baru **boleh** untuk: external link, PDF download yang user save, atau destination bookmarkable dengan URL sendiri

### Arsitektur Navigasi (PAKEM — JANGAN DIUBAH)

Navigasi RISA ERP menggunakan **3 tingkat hierarki**:

```
┌──────────────────────────────────────────────────────────────────┐
│ TOPBAR:  [Dashboard]  [Master Data ▾]  [Manajemen Akses]        │  ← Meta-Category
│                        ┌──────────┐                              │
│                        │ Produk   │  ← Dropdown (parent menu)   │
│                        │ (future) │                              │
│                        └──────────┘                              │
├──────────────┬───────────────────────────────────────────────────┤
│ SIDEBAR      │                                                   │
│              │                                                   │
│ Produk       │       Konten halaman...                           │  ← Group Label
│  Daftar      │                                                   │  ← Menu Item
│  Kategori    │                                                   │  ← Menu Item
│  NIE         │                                                   │  ← Menu Item
│  Keluar      │                                                   │  ← Menu Item
└──────────────┴───────────────────────────────────────────────────┘
```

**Komponen:**

| Tingkat | Di mana | Implementasi |
|---|---|---|
| Meta-Category (Topbar) | `top-navbar-menu.blade.php` | Array `$metaCategories` — dropdown jika punya >1 group, direct link jika 1:1 |
| Parent Menu (Sidebar Group) | Sidebar group label (`.fi-sidebar-group-label`) | `$navigationGroup` di Resource/Page (contoh: `'Produk'`) + `->navigationGroups([...])` di `AdminPanelProvider.php` |
| Menu Item (Sidebar Item) | Sidebar item link | `$navigationLabel` + `$navigationSort` di Resource/Page |

**Aturan wajib:**

1. **Sidebar** hanya menampilkan items dari NavigationGroup yang aktif → diatur oleh published view `resources/views/vendor/filament-panels/components/sidebar/index.blade.php`. **JANGAN hapus/ganti file ini.**
2. **Topbar** didrive oleh `filament.components.top-navbar-menu` via render hook `PanelsRenderHook::TOPBAR_START`. **JANGAN hapus.**
3. Setiap Resource/Page **wajib** set `$navigationGroup` — ini yang menjadi **sidebar group label** (bukan `$navigationParentItem`).
4. Halaman hidden (`$shouldRegisterNavigation = false`) **tetap wajib** set `$navigationGroup` agar sidebar fallback bisa menemukan grup yang tepat saat halaman tersebut aktif.
5. Meta-category dengan >1 sub-group akan render **dropdown** di topbar. Meta-category 1:1 render direct link.

**Cara menambah Parent Menu (sidebar group) baru di bawah meta-category yang sudah ada:**

1. Tambahkan nama group baru di `->navigationGroups([...])` pada `AdminPanelProvider.php`
2. Tambahkan nama group baru di array `groups` pada `$metaCategories` di `top-navbar-menu.blade.php`
3. Set `$navigationGroup = '<nama group baru>'` di setiap Resource/Page yang masuk group tersebut
4. Selesai — dropdown topbar otomatis menampilkan group baru, sidebar otomatis filter

**Cara menambah Meta-Category baru di topbar:**

1. Tambahkan entry baru di array `$metaCategories` di `top-navbar-menu.blade.php` (label, icon, groups)
2. Tambahkan nama group(s) di `->navigationGroups([...])` pada `AdminPanelProvider.php`
3. Set `$navigationGroup` yang sesuai di setiap Resource/Page

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
