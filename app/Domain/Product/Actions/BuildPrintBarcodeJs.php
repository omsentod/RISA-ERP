<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Product;
use Illuminate\Support\Collection;

class BuildPrintBarcodeJs
{
    public function __construct(private GenerateBarcode $barcode) {}

    /**
     * Build JS that opens a hidden iframe and triggers print for the given product IDs.
     * Called from Filament ->action() closures — keeps user on the current page.
     *
     * @param array<int>|Collection<int> $productIds
     */
    public function handle(array|Collection $productIds, ?string $customLot = null, ?int $customQuantity = null): string
    {
        $ids = collect($productIds)->take(200)->all();

        $labels = Product::query()
            ->with(['registration'])
            ->whereIn('id', $ids)
            ->orderByRaw('FIELD(id,' . implode(',', array_map('intval', $ids)) . ')')
            ->get()
            ->map(fn (Product $p) => [
                'code' => $p->code,
                'name' => $p->name,
                'specification' => $p->specification ?? '',
                'nie_number' => $p->registration?->nie_number ?? '21302420095',
                'lot' => !empty($customLot) ? $customLot : ($p->default_lot ?? '012606110'),
                'quantity' => ($customQuantity !== null && $customQuantity > 0) ? $customQuantity : ($p->default_quantity ?? 1),
                'expired_at' => $p->registration?->expired_at ? $p->registration->expired_at->format('Y m') : '2026 06',
                'year_month' => now()->format('Y m'),
                'svg' => $this->barcode->svg($p->code, widthFactor: 2, height: 75),
            ])
            ->all();

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
