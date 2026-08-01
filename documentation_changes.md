# Dokumentasi Perubahan Sistem Cetak Label Barcode RISA-ERP

Dokumen ini mencatat seluruh perubahan kode, aset, skema database, dan antarmuka pengguna (UI) yang dilakukan pada fitur Cetak Label Stiker Thermal.

---

## 1. Ringkasan Tujuan Perubahan

1. **Perbaikan Tampilan Gambar & Teks Terpotong**:
   - Memperbaiki pemotongan gambar simbol medis & ISO badges (segitiga *Non-Sterile* dan lingkaran *ISO 45001*).
   - Memperbaiki pemotongan teks vertikal *"Surabaya - Jawa Timur"* pada blok informasi produksi.
2. **Penambahan Teks Material & Periode Cetak**:
   - Menambahkan teks `Mat 316L` dan periode tahun-bulan saat ini (contoh: `2026 08`) pada posisi sebelah kanan/bawah area senggang.
3. **Fitur Pengelolaan Nomor LOT Dinamis**:
   - Menampilkan nomor `LOT` tepat di atas `QTY 1` pada label.
   - Menyediakan modal form input nomor `LOT` sebelum proses cetak di Filament Admin.
   - Menyimpan nomor `LOT` secara otomatis sebagai nilai *default* untuk cetak berikutnya pada produk yang bersangkutan.

---

## 2. Rincian File yang Diubah & Ditambahkan

### A. Database & Migration
#### 1. `database/migrations/2026_08_01_100000_add_default_lot_to_products_table.php` `[NEW]`
- **Deskripsi**: File migrasi untuk menambahkan kolom `default_lot` pada tabel `products`.
- **Perubahan Skema**:
  ```php
  $table->string('default_lot', 50)
        ->nullable()
        ->default('012606110')
        ->after('specification')
        ->comment('Nomor LOT default untuk cetak label');
  ```

#### 2. `app/Domain/Product/Models/Product.php` `[MODIFY]`
- **Deskripsi**: Menambahkan properti `default_lot` ke dalam daftar atribut yang dapat diisi (*fillable*).
- **Perubahan**:
  ```php
  protected $fillable = [
      'product_category_id',
      'registration_id',
      'code',
      'name',
      'specification',
      'default_lot', // [DITAMBAHKAN]
      'description',
      'is_published',
      'published_at',
      'published_by',
  ];
  ```

---

### B. Logic & Filament Action
#### 3. `app/Domain/Product/Actions/BuildPrintBarcodeJs.php` `[MODIFY]`
- **Deskripsi**: Menyesuaikan action pembentuk script percetakan label.
- **Perubahan**:
  - Menambahkan parameter optional `$customLot` pada method `handle(array|Collection $productIds, ?string $customLot = null)`.
  - Mengirimkan kunci `'lot'` (berisi `$customLot` atau `$p->default_lot`) dan `'year_month'` (berisi `now()->format('Y m')`) ke view Blade.

#### 4. `app/Filament/Resources/ProductResource.php` `[MODIFY]`
- **Deskripsi**: Menambahkan field `default_lot` pada form resource produk dan menambahkan modal input form pada action cetak label.
- **Perubahan**:
  - **Form Produk**: Menambahkan input `Forms\Components\TextInput::make('default_lot')`.
  - **Tindakan Cetak Tunggal (`printLabel`)**: Menambahkan form modal input LOT yang otomatis meng-update `default_lot` saat disubmit.
  - **Tindakan Cetak Terpilih (`printLabelsBulk`)**: Menambahkan form modal input LOT massal untuk meng-update `default_lot` seluruh barang yang dipilih.

---

### C. Aset, CSS & Template View Label
#### 5. `public/assets/images/btw_symbols_block.png` `[MODIFY]`
- **Deskripsi**: Mengganti file gambar 6 simbol medis & ISO badge dengan hasil *crop* presisi dari aset utama `btw_asset_png_1.png`.
- **Perbaikan**: Gambar dibuat tegak lurus (*vertical orientation*) dengan *padding* putih yang cukup di sekelilingnya, sehingga puncak segitiga *Non-Sterile* dan lingkaran *ISO 45001* tidak lagi terpotong.

#### 6. `public/assets/css/print-barcode-labels.css` `[MODIFY]`
- **Deskripsi**: Menyesuaikan tata letak CSS label stiker thermal 90mm x 50mm.
- **Perubahan**:
  - `.symbols-block`: Mengatur ukuran `18mm x 22mm` dengan `object-fit: contain` tanpa `overflow: hidden` atau rotasi CSS tambahan.
  - `.produksi-block`: Mengatur tinggi `22mm`, font `5.5pt`, dan line-height `1.15` agar baris *"Surabaya - Jawa Timur"* muat tanpa terpotong.
  - `.group-mat` & `.mat-text`: Menambahkan styling untuk posisi `Mat 316L` dan tahun bulan di sebelah kanan (posisi bawah saat diputar).
  - `.lot-text`: Menambahkan styling teks baris `LOT`.

#### 7. `resources/views/partials/print-barcode-labels.blade.php` `[MODIFY]`
- **Deskripsi**: Template utama HTML untuk percetakan label.
- **Perubahan**:
  - **Group 3**: Menambahkan baris `LOT` di atas `QTY 1`:
    ```html
    <div class="v-text lot-text">LOT {{ $label['lot'] ?? '012606110' }}</div>
    ```
  - **Group 4B**: Menambahkan blok informasi material dan tahun-bulan:
    ```html
    <div class="group-mat">
        <div class="v-text mat-text">
            Mat 316L<br>
            {{ $label['year_month'] ?? now()->format('Y m') }}
        </div>
    </div>
    ```

---

## 3. Alur Kerja Penggunaan Fitur Baru (Untuk Admin)

1. **Membuka Daftar Produk / Edit Produk**:
   - Admin dapat menentukan **Nomor LOT Default** pada form detail produk jika diperlukan.
2. **Mencetak Label Produk**:
   - Admin mengklik tombol **"Cetak Label"** (atau memilih beberapa produk lalu mengklik **"Cetak Label Terpilih"**).
   - Sebuah **Pop-up Form Modal** akan muncul menampilkan Nomor LOT default yang tersimpan.
   - Admin dapat langsung mengklik cetak atau mengubah beberapa digit angka sesuai *batch/kategori* barang saat ini.
3. **Otomatisasi**:
   - Nomor LOT yang baru saja dimasukkan akan otomatis tersimpan sebagai nilai default baru untuk produk tersebut.
   - Label akan langsung terenkripsi dan dikirim ke printer stiker thermal tanpa *page refresh*.
