<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Jalan {{ $transaction->doc_no }}</title>
    <style>
        @page { size: A5; margin: 10mm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; font-family: 'Helvetica Neue', Arial, sans-serif; color: #000; background: #fff; font-size: 10pt; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8mm; padding-bottom: 4mm; border-bottom: 2px solid #000; }
        .company { }
        .company h1 { font-size: 14pt; margin: 0; letter-spacing: 0.5px; }
        .company .sub { font-size: 8pt; color: #444; }
        .doc-info { text-align: right; }
        .doc-info .title { font-size: 12pt; font-weight: 700; margin-bottom: 2mm; }
        .doc-info table { font-size: 9pt; }
        .doc-info td { padding: 0.5mm 1mm; }
        .doc-info td:first-child { color: #666; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 4mm; margin-bottom: 6mm; }
        .meta .box { padding: 2mm; border: 1px solid #ccc; border-radius: 2mm; }
        .meta .label { font-size: 8pt; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 1mm; }
        .meta .value { font-size: 10pt; font-weight: 500; }
        table.items { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 6mm; }
        table.items th { background: #f3f4f6; padding: 2mm; text-align: left; border-bottom: 2px solid #000; font-weight: 600; }
        table.items td { padding: 2mm; border-bottom: 1px solid #ddd; vertical-align: top; }
        table.items .qty { text-align: center; font-weight: 600; }
        table.items .no { text-align: center; color: #666; width: 8mm; }
        table.items .code { font-family: 'Courier New', monospace; font-weight: 600; width: 30mm; }
        .total-row td { font-weight: 700; font-size: 10pt; background: #f9fafb; border-top: 2px solid #000; padding: 3mm 2mm; }
        .signatures { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 5mm; margin-top: 10mm; }
        .signatures .box { text-align: center; }
        .signatures .box .role { font-size: 9pt; margin-bottom: 12mm; color: #666; }
        .signatures .box .name { border-top: 1px solid #000; padding-top: 2mm; font-size: 9pt; }
        .notes { font-size: 8pt; color: #555; margin-top: 4mm; padding: 2mm; background: #f9fafb; border-left: 3px solid #ccc; }
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
