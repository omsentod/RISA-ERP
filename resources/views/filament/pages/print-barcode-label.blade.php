<x-filament-panels::page>
    @php
        $products = $this->getProducts();
    @endphp

    <style>
        @media print {
            @page {
                size: 40mm 30mm;
                margin: 0;
            }
            body, .fi-body, .fi-main, .fi-page {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .fi-topbar, .fi-sidebar, .fi-header, .no-print, aside, nav, header {
                display: none !important;
            }
            .print-sheet {
                display: block !important;
                background: white !important;
            }
            .print-label {
                page-break-after: always;
                page-break-inside: avoid;
                width: 40mm;
                height: 30mm;
                margin: 0;
                padding: 1mm;
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                border: none !important;
            }
            .print-label:last-child {
                page-break-after: auto;
            }
        }

        .print-sheet {
            display: grid;
            grid-template-columns: repeat(auto-fill, 40mm);
            gap: 4mm;
            padding: 1rem;
            background: #f3f4f6;
        }

        .print-label {
            width: 40mm;
            height: 30mm;
            padding: 1mm;
            box-sizing: border-box;
            background: white;
            border: 1px dashed #d1d5db;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: black;
        }

        .print-label svg {
            width: 100%;
            height: 15mm;
            display: block;
        }

        .print-label .code {
            font-family: 'Courier New', monospace;
            font-size: 7pt;
            font-weight: 700;
            margin-top: 0.5mm;
            text-align: center;
            line-height: 1;
        }

        .print-label .name {
            font-size: 5pt;
            text-align: center;
            line-height: 1.1;
            margin-top: 0.3mm;
            max-height: 6mm;
            overflow: hidden;
        }
    </style>

    @if ($products->isEmpty())
        <x-filament::section>
            <div class="text-center py-8 text-gray-500">
                Tidak ada produk untuk dicetak. Silakan pilih produk dari <a href="{{ \App\Filament\Resources\ProductResource::getUrl('index') }}" class="text-primary-600 underline">daftar produk</a>.
            </div>
        </x-filament::section>
    @else
        <div class="no-print flex items-center justify-between mb-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Siap cetak <strong>{{ $products->count() }}</strong> label (40×30mm — printer thermal).
            </div>
            <div class="flex gap-2">
                <x-filament::button icon="heroicon-o-printer" onclick="window.print()">
                    Cetak Sekarang
                </x-filament::button>
                <x-filament::button color="gray" tag="a" href="{{ \App\Filament\Resources\ProductResource::getUrl('index') }}">
                    Kembali ke Produk
                </x-filament::button>
            </div>
        </div>

        <div class="print-sheet">
            @foreach ($products as $product)
                <div class="print-label">
                    {!! $this->barcodeSvg($product->code) !!}
                    <div class="code">{{ $product->code }}</div>
                    <div class="name">{{ \Illuminate\Support\Str::limit($product->name, 40) }}</div>
                </div>
            @endforeach
        </div>

        <script>
            window.addEventListener('load', () => setTimeout(() => window.print(), 300));
        </script>
    @endif
</x-filament-panels::page>
