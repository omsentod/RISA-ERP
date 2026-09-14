<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Product;

class SaveProductLabelLayout
{
    /**
     * Menyimpan custom layout label stiker ke database per nama produk (atau kode produk jika cocok).
     *
     * @param array<string, array<string, mixed>> $layouts Array asosiatif [nama_produk => layout]
     * @return int Jumlah baris produk yang diperbarui
     */
    public function handle(array $layouts): int
    {
        $updatedCount = 0;

        foreach ($layouts as $key => $layout) {
            if (empty($key) || !is_array($layout)) {
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

            $trimmedKey = trim($key);

            // 1. Prioritaskan pencarian berdasarkan nama produk
            $updated = Product::where('name', $trimmedKey)->update([
                'label_layout' => $cleanedLayout,
            ]);

            // 2. Jika tidak ada produk dengan nama tersebut, fallback ke kode produk (SKU)
            if ($updated === 0) {
                $updated = Product::where('code', $trimmedKey)->update([
                    'label_layout' => $cleanedLayout,
                ]);
            }

            $updatedCount += $updated;
        }

        return $updatedCount;
    }
}
