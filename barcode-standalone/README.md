# RISA Standalone Barcode System 🏷️

Sistem Manajemen & Verifikasi Barcode Standalone untuk PT RISA IMPLANTAMA.

Aplikasi ini dapat dijalankan **secara mandiri (tanpa Laravel/framework)** cukup dengan Python atau 1-klik file `.bat`.

---

## 🚀 Fitur Utama Barcode

1. **Dashboard & Katalog Barcode (`index.html` / `barcodes.html`)**
   - Menampilkan seluruh katalog produk dan barcode dari file `BARCODE.xlsx`.
   - Pencarian real-time (Kode / Nama Produk / Spesifikasi).
   - Filter dropdown berdasarkan Spesifikasi.
   - Opsi Kustomisasi Cetak: Jumlah kolom A4, margin kertas, jarak antar kolom/baris, tinggi label, tinggi barcode, ukuran font title.
   - Opsi Tampil/Sembunyikan: Judul produk, spesifikasi, kode produk, border label.
   - Mode **Grid View** & Mode **Preview Cetak A4**.
   - Cetak langsung ke Printer Stiker / PDF.
   - Unduh barcode format PNG / Salin Kode 1-klik.
   - Mode Tema Terang & Gelap.

2. **Scanner & Verifikasi Barcode (`checker.html`)**
   - **Scanner Gun Physical**: Cukup arahkan barcode scanner ke produk, sistem otomatis membaca data secara instan.
   - **Kamera Scanner HP / Webcam**: Pindai barcode langsung via kamera HP/laptop.
   - **Upload / Drag & Drop Foto**: Pindai barcode dari file gambar/foto.
   - Notifikasi Audio Beep & Vibrasi HP saat barcode terverifikasi.

3. **Pendataan & Outbound Barang Keluar (`pendataan.html`)**
   - **Input Pendataan Outbound**: Pindai barcode dengan Handheld Scanner Gun atau input manual untuk menyusun daftar barang keluar per dokumen/surat jalan.
   - **Riwayat & Laporan Outbound**: Menyimpan riwayat pencatatan transaksi secara mandiri di penyimpanan lokal (`localStorage`).
   - **Cetak Surat Jalan & Struk Ringkasan**: Cetak dokumen bukti pendataan pengiriman barang keluar.
   - **Rekap Stok & Pergerakan Produk**: Menghitung akumulasi total unit barang keluar per kode produk.
   - **Backup & Export Data**: Ekspor cadangan data pendataan ke file JSON atau CSV Excel.

4. **Generator Barcode Otomatis (`generate_barcodes.py`)**
   - Membaca `BARCODE.xlsx` dan memproses seluruh baris produk.
   - Menghasilkan vektor SVG (responsive, tanpa bug unit `mm`).
   - Menghasilkan gambar PNG resolusi tinggi (300 DPI) untuk printer laser 1D scanner.
   - Menyusun `barcodes_data.js` untuk pemuatan data instan di browser.

---

## 💻 Cara Menjalankan

### **Metode 1: Double Click File Batch (Sangat Mudah)**
Double click file:
- `JALANKAN_SISTEM.bat` atau `START_SERVER.bat`

---

### **Metode 2: Lewat Command Prompt / Terminal**
Buka terminal di folder ini, lalu jalankan:
```bash
python run_server.py
```

---

## 🌐 Cara Menyalakan Ngrok (Untuk Akses dari HP / Internet)

Buka terminal baru di folder ini, lalu ketik:
```powershell
.\ngrok.exe http 8000
```
atau dengan path absolut Laragon:
```powershell
C:\laragon\bin\ngrok\ngrok.exe http 8000
```

Buka URL HTTPS hasil dari ngrok di browser HP Anda.
