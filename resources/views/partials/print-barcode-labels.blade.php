<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak Label Stiker Thermal</title>
    <style>
        {!! file_get_contents(public_path('assets/css/print-barcode-labels.css')) !!}
    </style>
</head>
<body>
    @foreach ($labels as $label)
        <div class="sheet">
            <div class="label">
                
                <!-- GROUP 1: Product Title (Reserved space at top for pre-printed OSFIX logo on paper) -->
                <div class="group-1">
                    <div class="logo-spacer"></div>
                    <div class="v-text product-title">{{ $label['name'] }}</div>
                </div>
                
                <!-- GROUP 2: Caution Note -->
                <div class="group-2">
                    <div class="v-text caution-text">Caution : Only Use by<br>Orthopaedic Surgeon</div>
                </div>
                
                <!-- GROUP 3: NIE, REF, LOT, QTY -->
                <div class="group-3">
                    @if (!empty($label['nie_number']) && $label['nie_number'] !== '-')
                        <div class="v-text nie-text">NIE {{ $label['nie_number'] }}</div>
                    @endif
                    <div class="v-text ref-text">REF {{ $label['code'] }}</div>
                    <div class="v-text lot-text">LOT {{ $label['lot'] ?? '012606110' }}</div>
                    <div class="v-text qty-text">QTY {{ $label['quantity'] ?? 1 }}</div>
                </div>

                <!-- GROUP 4: Barcode & Code -->
                <div class="group-4">
                    <div class="barcode-wrapper">
                        <div class="barcode-svg-container">
                            {!! $label['svg'] !!}
                        </div>
                    </div>
                    <div class="v-text barcode-num">{{ $label['code'] }}</div>
                </div>

                <!-- GROUP 4B: Material Info & Current Year Month -->
                <div class="group-mat">
                    <div class="v-text mat-text">
                        Mat 316L<br>
                        {{ $label['year_month'] ?? now()->format('Y m') }}
                    </div>
                </div>

                <!-- GROUP 5: Medical Symbols, ISO Badges & Produksi Info (Leftmost) -->
                <div class="group-5">
                    @if (!empty($symbols))
                        <div class="symbols-block">
                            <img src="{{ $symbols }}" alt="Medical Symbols & ISO Badges">
                        </div>
                    @endif
                    
                    <div class="v-text produksi-block">
                        Produksi<br>
                        PT RISA Implantama<br>
                        Surabaya - Jawa Timur<br>
                        KEMENKES RI - AKD
                    </div>
                </div>
                
            </div>
        </div>
    @endforeach
</body>
</html>
