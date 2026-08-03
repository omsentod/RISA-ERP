<?php

namespace App\Domain\Stock\Actions;

use App\Domain\Stock\Models\OutboundTransaction;

class BuildPrintSuratJalanJs
{
    /**
     * Render surat jalan (A5) + return JS untuk trigger print via hidden iframe.
     * Pattern sama seperti BuildPrintBarcodeJs — user tetap di halaman.
     */
    public function handle(OutboundTransaction $transaction): string
    {
        $transaction->load(['items.product.registration', 'creator']);

        $html = view('partials.print-surat-jalan', ['transaction' => $transaction])->render();
        $encoded = base64_encode($html);

        return <<<JS
        (() => {
            const existing = document.getElementById('__print_surat_jalan_iframe__');
            if (existing) existing.remove();
            const iframe = document.createElement('iframe');
            iframe.id = '__print_surat_jalan_iframe__';
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
