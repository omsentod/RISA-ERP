<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BuildPrintBarcodeJs
{
    /**
     * Qty selalu 2 digit di akhir data barcode (01-99).
     * Decoder: 2 digit terakhir = qty, sisanya = product ID.
     */
    private const BARCODE_QTY_DIGITS = 2;

    private const BARCODE_LOT_DIGITS = 9;

    private const MAX_LABELS_PER_BATCH = 200;

    // Code 128C: setiap 2 digit = 1 simbol → barcode lebih pendek, spasi lebih jelas.
    // widthFactor 2 sudah cukup karena jumlah bar sudah setengahnya.
    private const BARCODE_WIDTH_FACTOR = 2;

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
        ?string $yearMonth = null,
    ): string {
        $itemsMap = $this->normalizeItems($printItems, $customSequence, $customDuplicateCount, $customQuantityPerLabel);
        $ids = array_slice(array_keys($itemsMap), 0, self::MAX_LABELS_PER_BATCH);

        if (empty($ids)) {
            return "alert('Tidak ada produk untuk dicetak');";
        }

        $products = Product::query()
            ->with(['registration'])
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Product $p) => array_search($p->id, $ids))
            ->values();

        $labels = $this->renderLabels($products, $itemsMap, $this->resolvePrintDate($yearMonth));

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

    private function resolvePrintDate(?string $yearMonth): Carbon
    {
        if (empty($yearMonth)) {
            return now();
        }

        return Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth();
    }

    private function renderLabels(Collection $products, array $itemsMap, Carbon $date): array
    {
        $labels = [];
        $lotGen = app(GenerateDynamicLot::class);

        foreach ($products as $p) {
            $config = $itemsMap[$p->id] ?? [];
            $sequence = $this->resolveSequence($config['sequence'] ?? null, $lotGen);
            $lot = $lotGen->handle($p, $sequence, $date);
            $duplicateCount = max(1, (int) ($config['duplicate_count'] ?? 0));
            $qtyPerLabel = max(1, (int) ($config['quantity_per_label'] ?? 0) ?: (int) ($p->default_quantity ?? 1));

            $barcodeData = $this->encodeCompactBarcode($p->id, $qtyPerLabel, $lot);
            $svg = $this->renderBarcodeSvg($barcodeData);
            $formattedName = $this->formatName->handle($p->name);
            $cleanNie = trim(preg_replace('/AKD\s*/i', '', $p->registration?->nie_number ?? self::NIE_FALLBACK));

            $row = [
                'product_id' => $p->id,
                'raw_name' => $p->name,
                'code' => $p->code,
                'name' => $formattedName,
                'specification' => $p->specification ?? '',
                'nie_number' => $cleanNie,
                'lot' => $lot,
                'quantity' => $qtyPerLabel,
                'expired_at' => $p->registration?->expired_at?->format('Y m') ?? self::EXPIRY_FALLBACK,
                'year_month' => $date->format('Y m'),
                'svg' => $svg,
                'label_layout' => $p->label_layout,
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

    /**
     * Encode data barcode kompak: product ID + qty 2 digit + nomor LOT 9 digit (pure numerik).
     *
     * Format: {productId_even}{qty_2digit}{lot_9digit}
     * Product ID di-pad agar panjang keseluruhan selalu GENAP (syarat Code 128C).
     *
     * Contoh: ID=47 (2 digit) + qty(2) + lot(9) = 13 → total ganjil → pad ID ke 4 digit:
     *   "0047" + "01" + "122608099" = "004701122608099" (14 digit?) → masih ganjil?
     *   → pad lagi ke total genap dengan leading 0 di depan keseluruhan string.
     *
     * Contoh: ID=3617 (4 digit) + qty(2) + lot(9) = 15 → ganjil → prepend "0" → 16 EVEN ✓
     */
    private function encodeCompactBarcode(int $productId, int $qty, ?string $lot = null): string
    {
        $qtyStr  = str_pad((string) min($qty, 99), self::BARCODE_QTY_DIGITS, '0', STR_PAD_LEFT);
        $base    = (string) $productId . $qtyStr;

        if ($lot !== null) {
            $cleanLot = preg_replace('/\D/', '', $lot);
            if (strlen($cleanLot) === self::BARCODE_LOT_DIGITS) {
                $full = $base . $cleanLot;
                // Pastikan panjang genap agar valid untuk Code 128C
                if (strlen($full) % 2 !== 0) {
                    $full = '0' . $full;
                }

                return $full;
            }
        }

        // Tanpa lot: pad jika ganjil
        if (strlen($base) % 2 !== 0) {
            $base = '0' . $base;
        }

        return $base;
    }

    private function renderBarcodeSvg(string $data): string
    {
        // Gunakan Code 128C: setiap 2 digit numerik = 1 simbol barcode.
        // Hasilnya: jumlah bar setengahnya, spasi putih (quiet zone) 2× lebih lebar.
        // Jauh lebih mudah dibaca oleh scanner 1D pada printer thermal berkualitas rendah.
        $svg = $this->barcode->svgCode128C($data, widthFactor: self::BARCODE_WIDTH_FACTOR, height: self::BARCODE_HEIGHT);

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
            
            const printWindow = window.open('', '_blank');
            if (printWindow) {
                printWindow.document.open();
                printWindow.document.write(html);
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
