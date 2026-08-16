<?php

namespace App\Domain\Stock\Actions;

use App\Domain\Product\Models\Product;
use App\Domain\Stock\Models\OutboundTransaction;
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

    private function applyScan(OutboundTransaction $transaction, string $sku, int $qtyToAdd, string $originalCode): array
    {
        $product = $this->findProductBySku($sku) ?? Product::where('code', $originalCode)->first();
        if (!$product) {
            throw new RuntimeException("Kode \"{$originalCode}\" tidak ditemukan.");
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

    private function findProductBySku(string $sku): ?Product
    {
        return Product::query()
            ->where('code', $sku)
            ->orWhereRaw("REPLACE(code, ' ', '') = ?", [$sku])
            ->first();
    }
}
