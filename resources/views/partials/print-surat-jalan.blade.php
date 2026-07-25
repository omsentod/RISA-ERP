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
    <div class="header">
        <div class="company">
            <h1>PT RISA IMPLANTAMA</h1>
            <div class="sub">Orthopedic Implant Manufacturer</div>
        </div>
        <div class="doc-info">
            <div class="title">SURAT JALAN</div>
            <table>
                <tr><td>No.</td><td>: <strong>{{ $transaction->doc_no }}</strong></td></tr>
                <tr><td>Tanggal</td><td>: {{ $transaction->doc_date?->format('d M Y') }}</td></tr>
            </table>
        </div>
    </div>

    <div class="meta">
        <div class="box">
            <div class="label">Tujuan</div>
            <div class="value">{{ $transaction->destination ?: '—' }}</div>
        </div>
        <div class="box">
            <div class="label">Dibuat oleh</div>
            <div class="value">{{ $transaction->creator?->name ?: '—' }}</div>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th class="no">#</th>
                <th class="code">Kode</th>
                <th>Nama Produk</th>
                <th class="qty">Qty</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transaction->items as $i => $item)
                <tr>
                    <td class="no">{{ $i + 1 }}</td>
                    <td class="code">{{ $item->product->code }}</td>
                    <td>{{ $item->product->name }}</td>
                    <td class="qty">{{ $item->quantity }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" style="text-align:right;">Total Unit:</td>
                <td class="qty">{{ $transaction->total_qty }}</td>
            </tr>
        </tbody>
    </table>

    @if ($transaction->notes)
        <div class="notes"><strong>Catatan:</strong> {{ $transaction->notes }}</div>
    @endif

    <div class="signatures">
        <div class="box">
            <div class="role">Dibuat oleh</div>
            <div class="name">{{ $transaction->creator?->name ?: '(Nama)' }}</div>
        </div>
        <div class="box">
            <div class="role">Diperiksa oleh</div>
            <div class="name">(Nama & Tanda tangan)</div>
        </div>
        <div class="box">
            <div class="role">Diterima oleh</div>
            <div class="name">(Nama & Tanda tangan)</div>
        </div>
    </div>
</body>
</html>
