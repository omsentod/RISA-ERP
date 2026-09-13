<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Product;

class SaveProductLabelLayout
{
    /**
     * Menyimpan custom layout label stiker ke database per kode produk (SKU).
     *
     * @param array<string, array<string, mixed>> $layouts Array asosiatif [kode_produk => layout]
     * @return int Jumlah baris produk yang diperbarui
     */
    public function handle(array $layouts): int
    {
        $updatedCount = 0;

        foreach ($layouts as $code => $layout) {
            if (empty($code) || !is_array($layout)) {
                continue;
            }

            $cleanedLayout = [
                'title' => [
                    'transform' => $layout['title']['transform'] ?? 'none',
                    'fontSize' => $layout['title']['fontSize'] ?? '',
                    'height' => $layout['title']['height'] ?? '',
                ],
                'barcode' => [
                    'transform' => $layout['barcode']['transform'] ?? 'none',
                ],
            ];

            $updated = Product::where('code', trim($code))->update([
                'label_layout' => $cleanedLayout,
            ]);

            $updatedCount += $updated;
        }

        return $updatedCount;
    }
}
