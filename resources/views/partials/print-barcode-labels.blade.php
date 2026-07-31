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
                
                <!-- GROUP 1: Title & Logo (Logo on Right, Title on Left in portrait) -->
                <div class="group-1">
                    @if (!empty($logo))
                        <div class="logo-block">
                            <!-- <img src="{{ $logo }}" alt="Osfix Logo"> -->
                        </div>
                    @endif
                    <div class="v-text product-title">{{ $label['name'] }}</div>
                </div>
                
                <!-- GROUP 2: Caution Note -->
                <div class="group-2">
                    <div class="v-text caution-text">Caution : Only Use by<br>Orthopaedic Surgeon</div>
                </div>
                
                <!-- GROUP 3: NIE, REF, QTY -->
                <div class="group-3">
                    @if (!empty($label['nie_number']) && $label['nie_number'] !== '-')
                        <div class="v-text nie-text">NIE {{ $label['nie_number'] }}</div>
                    @endif
                    <div class="v-text ref-text">REF {{ $label['code'] }}</div>
                    <div class="v-text qty-text">QTY 1</div>
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

                <!-- GROUP 5: Produksi & ISO badges (ISO on Right, Produksi on Left in portrait) -->
                <div class="group-5">
                    <!-- Top (Portrait Right): ISO Badges -->
                    <div class="iso-badges-col">
                        <div class="v-text iso-badge">ISO 9001</div>
                        <div class="v-text iso-badge">ISO 13485</div>
                        <div class="v-text iso-badge">ISO 45001</div>
                    </div>
                    
                    <!-- Bottom (Portrait Left): Produksi Info -->
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
