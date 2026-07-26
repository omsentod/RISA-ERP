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
    public function handle(array|Collection $productIds): string
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
                'nie_number' => $p->registration?->nie_number ?? '-',
                'expired_at' => $p->registration?->expired_at ? $p->registration->expired_at->format('Y m') : '',
                'svg' => $this->barcode->svg($p->code, widthFactor: 2, height: 75),
            ])
            ->all();

        if (empty($labels)) {
            return "alert('Tidak ada produk untuk dicetak');";
        }

        $logoPath = public_path('assets/images/osfix.jpeg');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $html = view('partials.print-barcode-labels', ['labels' => $labels, 'logo' => $logoBase64])->render();
        $encoded = base64_encode($html);

        return <<<JS
        (() => {
            const existing = document.getElementById('__print_barcode_iframe__');
            if (existing) existing.remove();
            const iframe = document.createElement('iframe');
            iframe.id = '__print_barcode_iframe__';
            iframe.style.cssText = 'position:fixed;left:-9999px;top:0;width:1px;height:1px;border:0;';
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
