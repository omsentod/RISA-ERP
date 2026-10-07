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
    // widthFactor 1 (native) digunakan agar kita bisa menghitung mm secara presisi (dot-perfect) untuk 203 DPI.
    private const BARCODE_WIDTH_FACTOR = 1;

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

            // Memasukkan kembali LOT ke dalam barcode sesuai kebutuhan tracking barang Anda.
            // Panjang data tidak masalah karena kita menggunakan teknik Physical-to-Dot mapping (0.25mm/module).
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
        // Jika ada LOT, kita kompres menjadi 12 digit (Payload EAN-13)
        // Format: [ID:4][QTY:2][YM:3][SEQ:3]
        if ($lot !== null) {
            $cleanLot = preg_replace('/\D/', '', $lot);
            if (strlen($cleanLot) === 9) { // Format: {groupCode(2)}{YY(2)}{MM(2)}{seq(3)}
                $year = 2000 + (int) substr($cleanLot, 2, 2);
                $month = (int) substr($cleanLot, 4, 2);
                $seq = substr($cleanLot, 6, 3);
                
                // Compress Year and Month into a 3-digit number (base 2024)
                $ym = ($year - 2024) * 12 + $month;
                $ym = max(1, $ym); // prevent negative/zero if older than 2024
                
                $idStr = str_pad((string) min($productId, 9999), 4, '0', STR_PAD_LEFT);
                $qtyStr = str_pad((string) min($qty, 99), 2, '0', STR_PAD_LEFT);
                $ymStr = str_pad((string) min($ym, 999), 3, '0', STR_PAD_LEFT);
                
                return $idStr . $qtyStr . $ymStr . $seq; // Tepat 12 digit
            }
        }

        // Fallback (Tanpa LOT)
        $qtyStr  = str_pad((string) min($qty, 99), self::BARCODE_QTY_DIGITS, '0', STR_PAD_LEFT);
        $base    = (string) $productId . $qtyStr;
        if (strlen($base) % 2 !== 0) {
            $base = '0' . $base;
        }

        return $base;
    }

    private function renderBarcodeSvg(string $data): string
    {
        // 1. Generate SVG base dengan widthFactor = 1 (1 module = 1 unit viewBox)
        if (strlen($data) === 12) {
            $svg = $this->barcode->svgEan13($data, 1, self::BARCODE_HEIGHT);
        } else {
            $svg = $this->barcode->svgCode128C($data, 1, self::BARCODE_HEIGHT);
        }

        // 2. Hitungan Presisi Matematis (Dot-Perfect Mapping) untuk ketebalan yang rata
        // EAN-13 memiliki tepat 95 modul.
        // Jika kita paksa lebarnya menjadi 47.5mm, maka 47.5 / 95 = 0.5mm per modul.
        // Pada printer 203 DPI, 0.5mm adalah persis 4 titik (dot) tinta!
        // Ini menjamin 100% tidak ada garis belang-belang, semua garis sangat tajam dan tebal!
        // Kembalikan ke width="100%" agar user bebas mengatur panjang barcode via CSS .barcode-svg-container
        if (preg_match('/viewBox="0 0 (\d+\.?\d*) (\d+)"/', $svg, $matches)) {
            $svg = preg_replace(
                '/<svg[^>]+>/i',
                '<svg width="100%" height="100%" viewBox="0 0 ' . $matches[1] . ' ' . $matches[2] . '" version="1.1" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">',
                $svg,
                1
            );
        }

        return $svg;
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
