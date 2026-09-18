@php
    use Illuminate\Support\Carbon;

    $items = $transaction->items;
    $totalQty = (int) $items->sum('quantity');
    $docDate = $transaction->doc_date ?? now();

    // Chunk items into pages (25 items per page for clean NCR continuous form fit)
    $itemsPerPage = 25;
    $chunks = $items->chunk($itemsPerPage);
    if ($chunks->isEmpty()) {
        $chunks = collect([collect()]);
    }
    $totalPages = $chunks->count();
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
    @foreach ($chunks as $pageIndex => $pageItems)
        @php
            $pageNumber = $pageIndex + 1;
            $isLastPage = ($pageNumber === $totalPages);

            // Empty rows handling:
            // If single page, fill up to 12 rows as default NCR standard.
            // If multi-page and on last page, fill up to at least 6 rows if fewer items so signature aligns nicely.
            if ($totalPages === 1) {
                $emptyRowsCount = max(0, 12 - $pageItems->count());
            } elseif ($isLastPage) {
                $emptyRowsCount = max(0, 6 - $pageItems->count());
            } else {
                $emptyRowsCount = 0;
            }
        @endphp

        <div class="page">

            {{-- ================= TOP HEADER ================= --}}
            <div class="header-top">
                <div class="company-info">
                    <div class="company-name">PT.RISA Implantama</div>
                    <div class="company-addr">Jl. Raya Medokan Sawah Timur No 41</div>
                    <div class="company-city">Surabaya</div>
                </div>
                <div class="meta-info">
                    <div class="doc-no">No : {{ $transaction->doc_no }}</div>
                    <div class="distributor">Dist: {{ $transaction->destination ?: '-' }}</div>
                    @if ($totalPages > 1)
                        <div class="page-no">Hal : {{ $pageNumber }} / {{ $totalPages }}</div>
                    @endif
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
                    @foreach ($pageItems as $itemIndex => $item)
                        @php
                            $rowNumber = ($pageIndex * $itemsPerPage) + $loop->iteration;
                            $product = $item->product;
                            $rawNie = $product?->registration?->nie_number ?? '21302420236';
                            $cleanNie = trim(preg_replace('/AKD\s*/i', '', $rawNie));
                            $formattedNie = 'AKD ' . $cleanNie;

                            $batchNumber = $item->lot_number;
                            if (empty($batchNumber) && $product) {
                                $batchNumber = $product->default_lot ?? app(\App\Domain\Product\Actions\GenerateDynamicLot::class)->handle($product);
                            }
                            if (empty($batchNumber)) {
                                $batchNumber = '082607119';
                            }

                            $cleanItemCode = rtrim($product?->code ?? '', '.');
                            $itemCodeDisplay = $cleanItemCode . (($product?->is_custom ?? false) ? '.' : '');
                        @endphp
                        <tr>
                            <td class="col-no">{{ $rowNumber }}</td>
                            <td class="col-item">{{ $itemCodeDisplay }}</td>
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
                    @if ($isLastPage)
                        <tr>
                            <td colspan="5" class="total-label">Total</td>
                            <td class="total-value">{{ $totalQty }} Pcs</td>
                        </tr>
                    @else
                        <tr>
                            <td colspan="5" class="total-label" style="font-style: italic; font-weight: normal;">(Bersambung ke Halaman {{ $pageNumber + 1 }}...)</td>
                            <td class="total-value" style="font-size: 8.5pt; color: #333;">Subtotal: {{ (int) $pageItems->sum('quantity') }} Pcs</td>
                        </tr>
                    @endif
                </tfoot>
            </table>

            {{-- ================= SIGNATURES (ONLY ON LAST PAGE) ================= --}}
            @if ($isLastPage)
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
            @endif

        </div>
    @endforeach
</body>
</html>
