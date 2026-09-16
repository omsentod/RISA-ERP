# Rencana Implementasi: Sinkronisasi Nomor LOT pada Outbound & Surat Jalan

Dokumen ini menjelaskan rencana teknis untuk menyinkronkan nomor LOT dari produk ke transaksi pengiriman (Outbound) dan cetak Surat Jalan (SJ), dengan tetap mempertahankan format barcode kompak numerik (`{product_id}{qty_2digit}`) yang ringkas dan cepat discan.

## Ringkasan Masalah & Solusi

* **Kondisi Saat Ini:**
  * Barcode hanya menyimpan `product_id` dan `quantity` (contoh: `4701`).
  * Tabel `outbound_transaction_items` tidak memiliki kolom untuk menyimpan nomor LOT yang discan.
  * Ketika Surat Jalan (SJ) dicetak, sistem meng-generate nomor LOT baru secara on-the-fly (`GenerateDynamicLot`), sehingga nomor lot di SJ sering berbeda dengan nomor lot yang tertempel pada label stiker fisik barang.
* **Solusi yang Diusulkan:**
  * Menambahkan kolom `lot_number` pada tabel `outbound_transaction_items`.
  * Saat barcode produk discan di sesi Outbound, sistem otomatis mengisi nomor LOT default (berdasarkan grup produk, periode produksi, dan sequence).
  * Menampilkan kolom **No. Lot** yang dapat dicek dan diedit secara inline di halaman scan Outbound, sehingga petugas gudang dapat menyesuaikan jika barang fisik berasal dari batch cetak tertentu.
  * Memperbarui cetakan Surat Jalan ([print-surat-jalan.blade.php](file:///Users/prom4/Documents/GITHUB/RISA-ERP/resources/views/partials/print-surat-jalan.blade.php)) agar mencetak persis nilai `lot_number` dari item transaksi Outbound.

---

## User Review Required

> [!NOTE]
> Format barcode fisik tidak diubah. Scanner tetap membaca barcode ringkas (`4701`).

* Saat scan pertama kali, nomor lot akan otomatis terisi dengan lot aktif produk tersebut.
* Petugas scan di gudang dapat langsung melihat kolom "No. Lot" di tabel scan, dan dapat mengedit teksnya secara langsung jika diperlukan sebelum menyelesaikan sesi (menyimpan Surat Jalan).

---

## Proposed Changes

### 1. Database & Migrations

#### [NEW] `database/migrations/2026_09_16_100000_add_lot_number_to_outbound_transaction_items_table.php`
* Menambahkan kolom:
  * `lot_number` (`string`, nullable, ditempatkan setelah `quantity`).

---

### 2. Domain Stock & Actions

#### [MODIFY] [OutboundTransactionItem.php](file:///Users/prom4/Documents/GITHUB/RISA-ERP/app/Domain/Stock/Models/OutboundTransactionItem.php)
* Menambahkan `'lot_number'` ke properti `$fillable`.

#### [MODIFY] [AddScanToOutbound.php](file:///Users/prom4/Documents/GITHUB/RISA-ERP/app/Domain/Stock/Actions/AddScanToOutbound.php)
* Saat item baru dibuat di `addProduct()`, sistem meng-generate nomor LOT default menggunakan `GenerateDynamicLot::handle($product)` dan menyimpannya ke `lot_number`.

---

### 3. Filament UI & Livewire

#### [MODIFY] [ScanOutbound.php](file:///Users/prom4/Documents/GITHUB/RISA-ERP/app/Filament/Pages/ScanOutbound.php)
* Menambahkan method `updateItemLot(int $itemId, string $lotNumber)` untuk menyimpan perubahan nomor lot yang diedit oleh petugas di layar scan.

#### [MODIFY] [scan-outbound.blade.php](file:///Users/prom4/Documents/GITHUB/RISA-ERP/resources/views/filament/pages/scan-outbound.blade.php)
* Menambahkan kolom **No. Lot** di tabel daftar item scan dengan input teks yang tersambung ke `wire:change="updateItemLot(...)` atau `wire:model.blur`.

#### [MODIFY] [OutboundTransactionResource.php](file:///Users/prom4/Documents/GITHUB/RISA-ERP/app/Filament/Resources/OutboundTransactionResource.php)
* Menambahkan `lot_number` ke dalam Infolist `Daftar Item` pada halaman detail transaksi (View Record).

---

### 4. Surat Jalan Printing

#### [MODIFY] [print-surat-jalan.blade.php](file:///Users/prom4/Documents/GITHUB/RISA-ERP/resources/views/partials/print-surat-jalan.blade.php)
* Mengubah kolom `col-batch`:
  * Menggunakan `$item->lot_number` yang tersimpan di database transaksi.
  * Fallback hanya berjalan jika data transaksi lama belum memiliki `lot_number`.

---

## Verification Plan

### Automated Tests
* Menjalankan unit test untuk memastikan alur scan dan penyimpanan nomor lot berfungsi dengan baik:
  ```bash
  php artisan test tests/Feature/Domain/Stock/Actions/AddScanToOutboundTest.php
  ```
* Menambahkan test case baru untuk validasi bahwa item baru yang dibuat otomatis memiliki `lot_number`.

### Manual Verification
1. Jalankan migrasi database: `php artisan migrate`.
2. Buka modul Outbound di browser $\rightarrow$ Mulai Sesi Scan baru.
3. Lakukan scan barcode produk (contoh `4701`).
4. Pastikan item masuk ke tabel dan kolom **No. Lot** terisi otomatis (dan dapat diedit).
5. Selesaikan sesi dan klik **Cetak Surat Jalan**.
6. Verifikasi kolom **Batch Number** di cetakan Surat Jalan menampilkan nomor LOT yang sama persis dengan yang ada di transaksi.
