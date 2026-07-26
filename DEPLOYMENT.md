# RISA-ERP — Deployment Guide (Hostinger Shared Hosting)

Panduan lengkap deploy RISA-ERP ke **Hostinger Premium/Business Shared Hosting** via **Git Auto-Deploy**.

**Target**: subdomain `https://erp.<namadomain>.co.id`
**Stack**: Laravel 12 + Filament 3 + PHP 8.3 + MySQL

---

## Prerequisites (yang harus sudah siap)

- [ ] Akun Hostinger **Premium/Business** (butuh SSH access + Git integration + PHP 8.3)
- [ ] Domain sudah terdaftar (subdomain akan dibuat dari domain existing)
- [ ] Repository di GitHub (private OK — Hostinger support token auth)
- [ ] Data test lama sudah di-cleanup (`OutboundTransaction` id 1-23 dengan timezone UTC)
- [ ] Semua perubahan sudah di-commit + push ke `main`

---

## Fase 1: Persiapan Lokal (5 menit)

### 1.1 Cleanup data test
```bash
php artisan tinker
```
```php
\App\Domain\Stock\Models\OutboundTransaction::whereBetween('id', [1, 23])->forceDelete();
```

### 1.2 Test build production di lokal
```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```
Cek `public/build/manifest.json` ada. Test aplikasi masih jalan.

### 1.3 Push kode ke GitHub
```bash
git add DEPLOYMENT.md .env.production.example .htaccess deploy.sh
git commit -m "chore: add production deployment configuration for Hostinger"
git push origin main
```

---

## Fase 2: Setup Hostinger hPanel (15 menit)

### 2.1 PHP Configuration
`hPanel → Advanced → PHP Configuration`
- PHP version: **8.3**
- Extensions aktif: `pdo_mysql`, `mbstring`, `gd`, `zip`, `intl`, `bcmath`, `curl`, `openssl`, `fileinfo`, `xml`
- `memory_limit`: **256M**
- `max_execution_time`: **120**
- `upload_max_filesize`: **20M**
- `post_max_size`: **25M**

### 2.2 Subdomain
`hPanel → Domains → Subdomains`
- Buat subdomain: `erp` under `<namadomain>.co.id`
- Document root: default `domains/erp.<namadomain>.co.id/public_html`

### 2.3 SSL (Let's Encrypt gratis)
`hPanel → Advanced → SSL`
- Install untuk `erp.<namadomain>.co.id`
- Enable "Force HTTPS"

### 2.4 Database MySQL
`hPanel → Databases → MySQL Databases`
- Buat DB: `u<XXXXXX>_risaerp`
- Buat user: `u<XXXXXX>_admin` dengan password kuat (min 20 char, generate via password manager)
- Assign user ke DB dengan **All Privileges**
- **CATAT credentials** — akan dipakai di `.env`

### 2.5 SSH Access
`hPanel → Advanced → SSH Access`
- Enable SSH
- Catat: hostname, port, username

Test dari lokal:
```bash
ssh -p 65002 u<XXXXXX>@<hostname>.hostinger.com
```

---

## Fase 3: Git Auto-Deploy Setup (10 menit)

### 3.1 Generate GitHub Deploy Key
Di server via SSH:
```bash
ssh-keygen -t ed25519 -f ~/.ssh/hostinger_deploy -N ""
cat ~/.ssh/hostinger_deploy.pub
```
Copy output → GitHub repo → **Settings → Deploy keys → Add**
- Title: `Hostinger Auto-Deploy`
- Key: paste
- **Allow write access**: NO (read-only cukup)

### 3.2 Setup Git Integration di Hostinger
`hPanel → Website → Auto Installer → Git`
- Repository URL: `git@github.com:<username>/RISA-ERP.git` (SSH)
- Branch: `main`
- Install path: `domains/erp.<namadomain>.co.id/public_html`
- **Save & Deploy**

### 3.3 Post-Deploy Command
Di hPanel Git config → "Command to run after deployment":
```bash
bash deploy.sh
```

**Cara kerja**: Setiap `git push origin main` → GitHub webhook trigger Hostinger → auto pull + jalankan `deploy.sh`.

---

## Fase 4: First-Time Configuration (10 menit)

### 4.1 SSH ke server
```bash
ssh -p 65002 u<XXXXXX>@<hostname>.hostinger.com
cd domains/erp.<namadomain>.co.id/public_html
```

### 4.2 Setup `.env` production
```bash
cp .env.production.example .env
nano .env
```
Isi semua placeholder:
- `APP_URL=https://erp.<namadomain>.co.id`
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (dari step 2.4)
- `MAIL_*` (kalau butuh email — skip dulu kalau belum)

Save (Ctrl+O, Enter, Ctrl+X).

Generate `APP_KEY`:
```bash
php artisan key:generate --force
```

Set permission:
```bash
chmod 600 .env
```

### 4.3 First-run install
```bash
bash deploy.sh
```

Kalau sukses, output akhir: `✅ Deploy selesai — <timestamp>`.

### 4.4 Seed data awal
```bash
php artisan db:seed --class=RoleSeeder --force
php artisan db:seed --class=AdminUserSeeder --force
php artisan shield:generate --all --panel=admin --no-interaction
```

Cek user admin:
```bash
php artisan tinker
>>> \App\Models\User::first();
```

**GANTI PASSWORD ADMIN** (default: `password`):
```bash
php artisan tinker
>>> \App\Models\User::first()->update(['password' => \Hash::make('GantiPasswordKuat123!')]);
```

### 4.5 Cron Job
`hPanel → Advanced → Cron Jobs → Create`
- Command:
  ```
  /usr/bin/php8.3 /home/u<XXXXXX>/domains/erp.<namadomain>.co.id/public_html/artisan schedule:run >> /dev/null 2>&1
  ```
- Frequency: `* * * * *` (every minute)

---

## Fase 5: Verifikasi Post-Deploy

Buka `https://erp.<namadomain>.co.id/admin` — smoke test:

- [ ] **Login page** muncul (background dark, RISA OrthoERP branding)
- [ ] Login dengan `admin@risa.co.id` + password baru
- [ ] **Dashboard** render — 4 stats produk + 3 stats outbound + 2 chart
- [ ] Filter Dashboard "Periode Laporan" → ganti preset → widget reactive update
- [ ] **Produk** → list 1455 records tampil
- [ ] **Cetak Barcode** row action → iframe modal print (bukan new tab, UTF-8 benar)
- [ ] **Produk Keluar → Scan Produk** → tambah item → complete session
- [ ] **Cetak Surat Jalan** → modal print + logo tampil + karakter `—`/`·` benar
- [ ] **Toggle Rekap Produk** → filter periode → klik row → modal jejak
- [ ] **Export Excel** → file `.xlsx` ter-download
- [ ] **Timezone** — jam WIB (bukan UTC)

Kalau ada yang error, cek log:
```bash
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log
```

---

## Fase 6: Ongoing Maintenance

### Update rutin (setiap ada perubahan)
Cukup:
```bash
# Di lokal
git add .
git commit -m "..."
git push origin main
```
Hostinger auto-pull + `deploy.sh` otomatis jalan. Tidak perlu SSH.

### Backup DB (weekly, wajib untuk alkes)
`hPanel → Files → Backups` — enable auto-backup (Business+).

Atau manual via cron:
```bash
# Weekly backup, keep 4 minggu terakhir
0 2 * * 0 mysqldump -u<user> -p<pass> <db> | gzip > ~/backups/risaerp_$(date +\%Y\%m\%d).sql.gz && find ~/backups -mtime +28 -delete
```

### Monitor
- **Uptime**: UptimeRobot gratis, hit `https://erp.<namadomain>.co.id/admin/login` tiap 5 menit
- **Log size**: `du -sh storage/logs/` — kalau > 500 MB, rotate manual
- **Error alerts**: setup Sentry gratis tier atau tail log via SSH

---

## Troubleshooting

### Error 500 setelah deploy
```bash
cd ~/domains/erp.<namadomain>.co.id/public_html
tail -50 storage/logs/laravel-$(date +%Y-%m-%d).log
```
Common:
- `.env` tidak ada / `APP_KEY` kosong → run `php artisan key:generate`
- `composer install` gagal → cek PHP version match (`php -v`)
- File permission → `chmod -R 775 storage bootstrap/cache`

### Aset (CSS/JS) tidak load
- Cek `public/build/manifest.json` ada di server
- Kalau tidak ada, `npm run build` di lokal + commit `public/build/*` → git push ulang
- **ATAU** aktifkan Node.js di hPanel + build di server (lebih ideal)

### 404 semua route
- `.htaccess` di root tidak terbaca → cek `AllowOverride All` di hPanel
- Rewrite ke `public/` gagal → verifikasi `.htaccess` root file ada

### Timezone salah lagi
- Cek `.env` `APP_TIMEZONE=Asia/Jakarta`
- Clear cache: `php artisan config:clear && php artisan config:cache`

### Excel export timeout
- Naikkan `max_execution_time` di hPanel PHP Config
- Kalau data > 10k, tambah `WithCustomChunkSize` di `RekapProdukKeluarExport`

---

## Rollback (kalau deploy rusak)

```bash
cd ~/domains/erp.<namadomain>.co.id/public_html
git log --oneline -10                      # cek commit hash terakhir yang OK
git reset --hard <hash>
bash deploy.sh
```

Kalau DB juga corrupted:
```bash
# Restore backup
gunzip < ~/backups/risaerp_20260701.sql.gz | mysql -u<user> -p<pass> <db>
```

---

## Kontak

- **Hostinger Support**: 24/7 live chat di hPanel
- **Repo issues**: `github.com/<username>/RISA-ERP/issues`
