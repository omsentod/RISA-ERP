<?php

namespace App\Domain\Stock\Actions;

use App\Domain\Product\Models\Product;
use App\Domain\Stock\Exceptions\AmbiguousScanException;
use App\Domain\Stock\Models\OutboundTransaction;
use Illuminate\Support\Collection;
use RuntimeException;

class AddScanToOutbound
{
    private const BARCODE_SKU_QTY_PATTERN = '/^(.+?)\*(\d+)$/';

    public function handle(OutboundTransaction $transaction, string $code): array
    {
        if (!$transaction->isDraft()) {
            throw new RuntimeException('Transaksi sudah selesai / dibatalkan, tidak bisa tambah item lagi.');
        }

        $cleanCode = str_replace(' ', '', $code);

        if (preg_match(self::BARCODE_SKU_QTY_PATTERN, $cleanCode, $matches)) {
            return $this->applyScan($transaction, $matches[1], max(1, (int) $matches[2]), $cleanCode);
        }

        return $this->applyScan($transaction, $cleanCode, 1, $code);
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
