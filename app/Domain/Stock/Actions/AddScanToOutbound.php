<?php

namespace App\Domain\Stock\Actions;

use App\Domain\Product\Models\Product;
use App\Domain\Stock\Exceptions\AmbiguousScanException;
use App\Domain\Stock\Models\OutboundTransaction;
use Illuminate\Support\Collection;
use RuntimeException;

class AddScanToOutbound
{
    /**
     * Pattern lama: kode_produk*qty (misal "OF101004*5")
     */
    private const BARCODE_SKU_QTY_PATTERN = '/^(.+?)\*(\d+)$/';

    /**
     * Pattern baru: pure angka, minimal 3 digit (misal "4701" = ID 47, qty 01).
     * 2 digit terakhir = qty, sisanya = product ID.
     */
    private const COMPACT_BARCODE_PATTERN = '/^\d{3,}$/';

    private const COMPACT_QTY_DIGITS = 2;

    public function handle(OutboundTransaction $transaction, string $code): array
    {
        if (!$transaction->isDraft()) {
            throw new RuntimeException('Transaksi sudah selesai / dibatalkan, tidak bisa tambah item lagi.');
        }

        $cleanCode = str_replace(' ', '', $code);

        // 1. Coba format baru (pure angka: product_id + qty 2 digit)
        $compactResult = $this->tryCompactFormat($transaction, $cleanCode);
        if ($compactResult !== null) {
            return $compactResult;
        }

        // 2. Fallback ke format lama (kode_produk*qty)
        if (preg_match(self::BARCODE_SKU_QTY_PATTERN, $cleanCode, $matches)) {
            return $this->applyScan($transaction, $matches[1], max(1, (int) $matches[2]), $cleanCode);
        }

        // 3. Fallback: kode produk tanpa qty
        return $this->applyScan($transaction, $cleanCode, 1, $code);
    }

    /**
     * Coba parse sebagai barcode kompak (pure numerik).
     * Format: {product_id}{qty_2digit}  — contoh: "4701" = ID 47, qty 01.
     *
     * Return null jika bukan format kompak atau product ID tidak ditemukan.
     */
    private function tryCompactFormat(OutboundTransaction $transaction, string $cleanCode): ?array
    {
        if (!preg_match(self::COMPACT_BARCODE_PATTERN, $cleanCode)) {
            return null;
        }

        $len = strlen($cleanCode);
        if ($len < 3) {
            return null;
        }

        $productId = (int) substr($cleanCode, 0, $len - self::COMPACT_QTY_DIGITS);
        $qty = max(1, (int) substr($cleanCode, -self::COMPACT_QTY_DIGITS));

        $product = Product::find($productId);
        if (!$product) {
            // Product ID tidak ditemukan → bukan format kompak,
            // biarkan fallback ke pencarian by kode lama
            return null;
        }

        return $this->addProduct($transaction, $product, $qty);
    }

    public function addProduct(OutboundTransaction $transaction, Product $product, int $qtyToAdd): array
    {
        if (!$transaction->isDraft()) {
            throw new RuntimeException('Transaksi sudah selesai / dibatalkan, tidak bisa tambah item lagi.');
        }

        $item = $transaction->items()->firstOrNew(['product_id' => $product->id]);
        $isNew = !$item->exists;

        if ($isNew) {
            $item->quantity = $qtyToAdd;
            $item->scanned_at = now();
        } else {
            $item->quantity += $qtyToAdd;
        }
        $item->save();

        $transaction->recalculateTotalQty();

        return [
            'item' => $item->fresh(['product']),
            'isNew' => $isNew,
            'product' => $product,
            'qtyAdded' => $qtyToAdd,
        ];
    }

    private function applyScan(OutboundTransaction $transaction, string $sku, int $qtyToAdd, string $originalCode): array
    {
        $matches = $this->findProductsBySku($sku, $originalCode);

        if ($matches->isEmpty()) {
            throw new RuntimeException("Kode \"{$originalCode}\" tidak ditemukan.");
        }

        if ($matches->count() > 1) {
            throw new AmbiguousScanException($matches, $originalCode, $qtyToAdd);
        }

        return $this->addProduct($transaction, $matches->first(), $qtyToAdd);
    }

    /**
     * @return Collection<int, Product>
     */
    private function findProductsBySku(string $sku, string $originalCode): Collection
    {
        return Product::query()
            ->where('code', $originalCode)
            ->orWhere('code', $sku)
            ->orWhereRaw("REPLACE(code, ' ', '') = ?", [$sku])
            ->get();
    }
}

