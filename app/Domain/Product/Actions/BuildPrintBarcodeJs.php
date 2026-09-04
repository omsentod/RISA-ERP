<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Product;
use Illuminate\Support\Collection;

class BuildPrintBarcodeJs
{
    private const BARCODE_QTY_DELIMITER = '*';

    private const MAX_LABELS_PER_BATCH = 200;

    private const BARCODE_WIDTH_THRESHOLD = 10;

    private const BARCODE_WIDTH_FACTOR_COMPACT = 2;

    private const BARCODE_WIDTH_FACTOR_WIDE = 3;

    private const BARCODE_HEIGHT = 70;

    private const NIE_FALLBACK = '21302420095';

    private const EXPIRY_FALLBACK = '2026 06';

    public function __construct(
        private GenerateBarcode $barcode,
        private FormatProductNameForPrint $formatName,
    ) {}

    public function handle(
        array|Collection $printItems,
        ?string $customSequence = null,
        ?int $customDuplicateCount = null,
        ?int $customQuantityPerLabel = null,
    ): string {
        $itemsMap = $this->normalizeItems($printItems, $customSequence, $customDuplicateCount, $customQuantityPerLabel);
        $ids = array_slice(array_keys($itemsMap), 0, self::MAX_LABELS_PER_BATCH);

        if (empty($ids)) {
            return "alert('Tidak ada produk untuk dicetak');";
        }

        $products = Product::query()
            ->with(['registration'])
            ->whereIn('id', $ids)
            ->orderByRaw('FIELD(id,' . implode(',', array_map('intval', $ids)) . ')')
            ->get();

        $labels = $this->renderLabels($products, $itemsMap);

        if (empty($labels)) {
            return "alert('Tidak ada produk untuk dicetak');";
        }

        $html = view('partials.print-barcode-labels', [
            'labels' => $labels,
            'symbols' => $this->loadSymbolsBase64(),
        ])->render();

        return $this->buildIframeScript(base64_encode($html));
    }

    private function normalizeItems(
        array|Collection $printItems,
        ?string $customSequence,
        ?int $customDuplicateCount,
        ?int $customQuantityPerLabel,
    ): array {
        $map = [];
        foreach ($printItems as $item) {
            $isArray = is_array($item) && isset($item['product_id']);
            $pid = (int) ($isArray ? $item['product_id'] : $item);
            $map[$pid] = [
                'sequence' => $isArray ? ($item['sequence'] ?? $customSequence) : $customSequence,
                'duplicate_count' => $isArray
                    ? (int) ($item['duplicate_count'] ?? $item['quantity'] ?? $customDuplicateCount ?? 0)
                    : (int) ($customDuplicateCount ?? 0),
                'quantity_per_label' => $isArray
                    ? (int) ($item['quantity_per_label'] ?? $customQuantityPerLabel ?? 0)
                    : (int) ($customQuantityPerLabel ?? 0),
            ];
        }

        return $map;
    }

    private function renderLabels(Collection $products, array $itemsMap): array
    {
        $labels = [];
        $now = now();
        $lotGen = app(GenerateDynamicLot::class);

        foreach ($products as $p) {
            $config = $itemsMap[$p->id] ?? [];
            $sequence = $this->resolveSequence($config['sequence'] ?? null, $lotGen);
            $lot = $lotGen->handle($p, $sequence);
            $duplicateCount = max(1, (int) ($config['duplicate_count'] ?? 0));
            $qtyPerLabel = max(1, (int) ($config['quantity_per_label'] ?? 0) ?: (int) ($p->default_quantity ?? 1));

            $barcodeData = str_replace(' ', '', $p->code) . self::BARCODE_QTY_DELIMITER . $qtyPerLabel;
            $svg = $this->renderBarcodeSvg($barcodeData);
            $formattedName = $this->formatName->handle($p->name);
            $cleanNie = trim(preg_replace('/AKD\s*/i', '', $p->registration?->nie_number ?? self::NIE_FALLBACK));

            $row = [
                'code' => $p->code,
                'name' => $formattedName,
                'specification' => $p->specification ?? '',
                'nie_number' => $cleanNie,
                'lot' => $lot,
                'quantity' => $qtyPerLabel,
                'expired_at' => $p->registration?->expired_at?->format('Y m') ?? self::EXPIRY_FALLBACK,
                'year_month' => $now->format('Y m'),
                'svg' => $svg,
            ];

            for ($i = 0; $i < $duplicateCount; $i++) {
                $labels[] = $row;
            }
        }

        return $labels;
    }

    private function resolveSequence(?string $customSequence, GenerateDynamicLot $lotGen): string
    {
        $sequence = !empty($customSequence) ? $customSequence : $lotGen->getTodaySequenceString();
        $lotGen->recordPrintActivity($sequence);

        return $sequence;
    }

    private function renderBarcodeSvg(string $data): string
    {
        $widthFactor = strlen($data) > self::BARCODE_WIDTH_THRESHOLD
            ? self::BARCODE_WIDTH_FACTOR_COMPACT
            : self::BARCODE_WIDTH_FACTOR_WIDE;

        $svg = $this->barcode->svg($data, widthFactor: $widthFactor, height: self::BARCODE_HEIGHT);

        return str_replace('<svg ', '<svg preserveAspectRatio="none" ', $svg);
    }

    private function loadSymbolsBase64(): string
    {
        $path = public_path('assets/images/btw_symbols_block.png');

        return file_exists($path)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($path))
            : '';
    }

    private function buildIframeScript(string $encodedHtml): string
    {
        return <<<JS
        (() => {
            const bytes = Uint8Array.from(atob('{$encodedHtml}'), c => c.charCodeAt(0));
            const html = new TextDecoder('utf-8').decode(bytes);
            
            // Tambahkan tombol print mengambang
            const printBtnHtml = `
                <div class="no-print" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
                    <button onclick="window.print()" style="padding: 15px 25px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.1); font-size: 16px;">
                        🖨️ CETAK SEKARANG
                    </button>
                </div>
            `;
            const finalHtml = html.replace('</body>', printBtnHtml + '</body>');

            const printWindow = window.open('', '_blank');
            if (printWindow) {
                printWindow.document.open();
                printWindow.document.write(finalHtml);
                printWindow.document.close();
                
                // Beri waktu sebentar agar font dan gambar termuat
                setTimeout(() => {
                    printWindow.focus();
                }, 200);
            } else {
                alert('Pop-up diblokir oleh browser. Tolong izinkan pop-ups untuk membuka halaman cetak.');
            }
        })();
        JS;
    }
}
