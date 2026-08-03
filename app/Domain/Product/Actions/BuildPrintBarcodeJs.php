<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Product;
use Illuminate\Support\Collection;

class BuildPrintBarcodeJs
{
    public function __construct(
        private GenerateBarcode $barcode,
        private FormatProductNameForPrint $formatName,
    ) {}

    /**
     * Build JS that opens a hidden iframe and triggers print for the given product IDs.
     * Called from Filament ->action() closures — keeps user on the current page.
     *
     * @param array<int>|Collection<int> $productIds
     */
    public function handle(array|Collection $printItems, ?string $customSequence = null, ?int $customQuantity = null): string
    {
        $itemsMap = [];
        $ids = [];

        foreach ($printItems as $item) {
            if (is_array($item) && isset($item['product_id'])) {
                $pid = (int) $item['product_id'];
                $ids[] = $pid;
                $itemsMap[$pid] = [
                    'sequence' => $item['sequence'] ?? $customSequence,
                    'quantity' => isset($item['quantity']) ? (int) $item['quantity'] : $customQuantity,
                ];
            } else {
                $pid = (int) $item;
                $ids[] = $pid;
                $itemsMap[$pid] = [
                    'sequence' => $customSequence,
                    'quantity' => $customQuantity,
                ];
            }
        }

        $ids = collect($ids)->unique()->take(200)->all();

        if (empty($ids)) {
            return "alert('Tidak ada produk untuk dicetak');";
        }

        $products = Product::query()
            ->with(['registration'])
            ->whereIn('id', $ids)
            ->orderByRaw('FIELD(id,' . implode(',', array_map('intval', $ids)) . ')')
            ->get();

        $labels = [];

        foreach ($products as $p) {
            $config = $itemsMap[$p->id] ?? [];
            
            $itemSeq = !empty($config['sequence']) ? $config['sequence'] : $customSequence;
            
            if (!empty($itemSeq)) {
                app(\App\Domain\Product\Actions\GenerateDynamicLot::class)->recordPrintActivity($itemSeq);
            } else {
                $todaySeq = app(\App\Domain\Product\Actions\GenerateDynamicLot::class)->getTodaySequenceString();
                app(\App\Domain\Product\Actions\GenerateDynamicLot::class)->recordPrintActivity($todaySeq);
            }

            $itemLot = app(\App\Domain\Product\Actions\GenerateDynamicLot::class)->handle($p, $itemSeq);
            
            $itemQty = (isset($config['quantity']) && $config['quantity'] > 0) 
                ? $config['quantity'] 
                : (($customQuantity !== null && $customQuantity > 0) ? $customQuantity : 1);

            $formattedName = $this->formatName->handle($p->name);

            $rawNie = $p->registration?->nie_number ?? '21302420095';
            $cleanNie = trim(preg_replace('/AKD\s*/i', '', $rawNie));

            $barcodeData = str_replace(' ', '', $p->code);
            $rawSvg = $this->barcode->svg($barcodeData, widthFactor: 3, height: 70);
            $svg = str_replace('<svg ', '<svg preserveAspectRatio="none" ', $rawSvg);

            for ($i = 0; $i < $itemQty; $i++) {
                $labels[] = [
                    'code' => $p->code,
                    'name' => $formattedName,
                    'specification' => $p->specification ?? '',
                    'nie_number' => $cleanNie,
                    'lot' => $itemLot,
                    'quantity' => $p->default_quantity ?? 1,
                    'expired_at' => $p->registration?->expired_at ? $p->registration->expired_at->format('Y m') : '2026 06',
                    'year_month' => now()->format('Y m'),
                    'svg' => $svg,
                ];
            }
        }

        if (empty($labels)) {
            return "alert('Tidak ada produk untuk dicetak');";
        }

        $symbolsPath = public_path('assets/images/btw_symbols_block.png');
        $symbolsBase64 = '';
        if (file_exists($symbolsPath)) {
            $symbolsBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($symbolsPath));
        }

        $html = view('partials.print-barcode-labels', ['labels' => $labels, 'symbols' => $symbolsBase64])->render();
        $encoded = base64_encode($html);

        return <<<JS
        (() => {
            const existing = document.getElementById('__print_barcode_iframe__');
            if (existing) existing.remove();
            const iframe = document.createElement('iframe');
            iframe.id = '__print_barcode_iframe__';
            iframe.style.cssText = 'position:fixed;left:-9999px;top:0;width:90mm;height:50mm;border:0;visibility:hidden;';
            document.body.appendChild(iframe);
            const doc = iframe.contentDocument || iframe.contentWindow.document;
            const bytes = Uint8Array.from(atob('{$encoded}'), c => c.charCodeAt(0));
            const html = new TextDecoder('utf-8').decode(bytes);
            doc.open();
            doc.write(html);
            doc.close();
            const doPrint = () => {
                try {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                } catch (e) { console.error('Print failed', e); }
                setTimeout(() => iframe.remove(), 3000);
            };
            if (iframe.contentDocument.readyState === 'complete') {
                setTimeout(doPrint, 150);
            } else {
                iframe.onload = () => setTimeout(doPrint, 150);
            }
        })();
        JS;
    }
}
