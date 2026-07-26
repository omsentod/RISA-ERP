@php
    use Illuminate\Support\Carbon;

    $itemsCount = $transaction->items->count();
    $totalQty = (int) $transaction->items->sum('quantity');
    $waktu = $transaction->completed_at ?? $transaction->started_at;
    $logoData = null;
    $logoPath = public_path('assets/images/risa-logo.png');
    if (is_file($logoPath)) {
        $logoData = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
    }
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Jalan {{ $transaction->doc_no }}</title>
    <style>
        {!! file_get_contents(public_path('assets/css/print-surat-jalan.css')) !!}
    </style>
</head>
<body>
    <div class="container">

        {{-- ================= HEADER ================= --}}
        <div class="header">
            <div>
                <div class="brand">
                    @if ($logoData)
                        <img src="{{ $logoData }}" alt="RISA" class="logo">
                    @endif
                </div>
              
                <div class="company-address">
                    <p>Jl. Rungkut Industri III No. 86</p>
                    <p>Surabaya, Jawa Timur — Indonesia</p>
                    <p>Telp: +62 3151 90 6646 · risa.implantama@gmail.com</p>
                </div>
            </div>
            <div class="doc-header">
                <p class="title">Surat Jalan</p>
                <p class="doc-no">{{ $transaction->doc_no }}</p>
                <div class="doc-meta">
                    <p>Tanggal : <strong>{{ $transaction->doc_date?->translatedFormat('d F Y') }}</strong></p>
                </div>
            </div>
        </div>

        {{-- ================= SHIPMENT INFO ================= --}}
        <div class="shipment">
            <div>
                <div class="label">Tujuan Pengiriman</div>
                <div class="card">
                    <div class="primary">{{ $transaction->destination ?: '—' }}</div>
                    <div class="hint">Mohon periksa barang setelah diterima sesuai daftar kuantitas di bawah ini.</div>
                </div>
            </div>
            <div>
                <div class="label">Detail Pengiriman</div>
                <div class="card">
                    <div class="row"><strong>Penanggung Jawab:</strong> {{ $transaction->creator?->name ?: '—' }}</div>
                    <div class="row"><strong>Jenis Barang:</strong> {{ $itemsCount }} jenis</div>
                    <div class="row"><strong>Total Kuantitas:</strong> {{ $totalQty }} pcs</div>
                </div>
            </div>
        </div>

        {{-- ================= ITEMS TABLE ================= --}}
        <div class="items-wrap">
            <table class="items">
                <thead>
                    <tr>
                        <th class="no">No</th>
                        <th class="code">Kode Barcode</th>
                        <th>Nama Produk / Spesifikasi</th>
                        <th class="qty">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transaction->items as $i => $item)
                        <tr>
                            <td class="no">{{ $i + 1 }}</td>
                            <td class="code">{{ $item->product?->code }}</td>
                            <td>
                                @if (!empty($item->product?->specification))
                                    <div class="spec">{{ $item->product->specification }}</div>
                                    <div class="name">{{ $item->product?->name }}</div>
                                @else
                                    <div class="spec">{{ $item->product?->name }}</div>
                                @endif
                            </td>
                            <td class="qty">{{ $item->quantity }} pcs</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td class="label" colspan="3">Total Pengiriman</td>
                        <td class="total-qty">{{ $totalQty }} pcs</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- ================= NOTES ================= --}}
        @if ($transaction->notes)
            <div class="notes">
                <div class="label">Keterangan / Catatan Tambahan</div>
                <div class="body">{!! nl2br(e($transaction->notes)) !!}</div>
            </div>
        @endif

        {{-- ================= SIGNATURES ================= --}}
        <div class="signatures">
            <div class="box">
                <div class="role">Dibuat Oleh (Operator),</div>
                <div class="line">
                    <div class="name">{{ $transaction->creator?->name ?: '—' }}</div>
                    <div class="sub">Staff Logistik RISA</div>
                </div>
            </div>
            <div class="box">
                <div class="role">Dikirim Oleh (Ekspedisi),</div>
                <div class="line">
                    <div class="placeholder">......................</div>
                    <div class="sub">Nama Kurir / Tanda Tangan</div>
                </div>
            </div>
            <div class="box">
                <div class="role">Diterima Oleh,</div>
                <div class="line">
                    <div class="name">{{ $transaction->destination ?: '—' }}</div>
                    <div class="sub">Nama Penerima & Stempel</div>
                </div>
            </div>
        </div>

    </div>
</body>
</html>
