@php
    use Illuminate\Support\Carbon;

    $items = $transaction->items;
    $totalQty = (int) $items->sum('quantity');
    $docDate = $transaction->doc_date ?? now();

    // Fill table with empty grid rows up to at least 20 rows for full NCR page appearance
    $minRows = 20;
    $actualCount = $items->count();
    $emptyRowsCount = max(0, $minRows - $actualCount);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Jalan Pengiriman {{ $transaction->doc_no }}</title>
    <style>
        {!! file_get_contents(public_path('assets/css/print-surat-jalan.css')) !!}
    </style>
</head>
<body>
    <div class="page">

        {{-- ================= TOP HEADER ================= --}}
        <div class="header-top">
            <div class="company-info">
                <div class="doc-sop">No Dok : RI-SOP-ADM-04-A</div>
                <div class="company-name">PT.Risa Implantama</div>
                <div class="company-addr">Jl. Raya Medokan Sawah Timur No 41</div>
                <div class="company-city">Surabaya</div>
            </div>
            <div class="meta-info">
                <div class="doc-no">No : {{ $transaction->doc_no }}</div>
                <div class="distributor">Dist: {{ $transaction->destination ?: '-' }}</div>
            </div>
        </div>

        {{-- ================= TITLE ================= --}}
        <div class="title-section">
            <h1 class="title">Surat Jalan Pengiriman</h1>
        </div>

        {{-- ================= ITEMS TABLE ================= --}}
        <table class="ncr-table">
            <thead>
                <tr>
                    <th class="col-no">No</th>
                    <th class="col-item">Item</th>
                    <th class="col-keterangan">Keterangan</th>
                    <th class="col-nie">NIE</th>
                    <th class="col-batch">Batch Number</th>
                    <th class="col-jumlah">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $index => $item)
                    @php
                        $product = $item->product;
                        $rawNie = $product?->registration?->nie_number ?? '21302420236';
                        $cleanNie = trim(preg_replace('/AKD\s*/i', '', $rawNie));
                        $formattedNie = 'AKD ' . $cleanNie;

                        $batchNumber = $product?->default_lot;
                        if (empty($batchNumber) && $product) {
                            $batchNumber = app(\App\Domain\Product\Actions\GenerateDynamicLot::class)->handle($product);
                        }
                        if (empty($batchNumber)) {
                            $batchNumber = '082607119';
                        }
                    @endphp
                    <tr>
                        <td class="col-no">{{ $index + 1 }}</td>
                        <td class="col-item">{{ $product?->code }}</td>
                        <td class="col-keterangan">{{ $product?->name }}</td>
                        <td class="col-nie">{{ $formattedNie }}</td>
                        <td class="col-batch">{{ $batchNumber }}</td>
                        <td class="col-jumlah">{{ $item->quantity }} Pcs</td>
                    </tr>
                @endforeach

                @for ($i = 0; $i < $emptyRowsCount; $i++)
                    <tr class="empty-row">
                        <td class="col-no"></td>
                        <td class="col-item"></td>
                        <td class="col-keterangan"></td>
                        <td class="col-nie"></td>
                        <td class="col-batch"></td>
                        <td class="col-jumlah"></td>
                    </tr>
                @endfor
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="total-label">Total</td>
                    <td class="total-value">{{ $totalQty }} Pcs</td>
                </tr>
            </tfoot>
        </table>

        {{-- ================= SIGNATURES (2 PLACES ONLY: LEFT & RIGHT) ================= --}}
        <div class="signature-section">
            <div class="sig-box sig-left">
                <div class="sig-title">Yang Menyerahkan</div>
                <div class="sig-space"></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box sig-right">
                <div class="sig-date">Sby, {{ $docDate->translatedFormat('d F Y') }}</div>
                <div class="sig-title">Yang Menerima</div>
                <div class="sig-space"></div>
                <div class="sig-line"></div>
            </div>
        </div>

    </div>
</body>
</html>
