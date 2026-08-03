<?php

namespace App\Domain\Stock\Actions;

use App\Domain\Product\Models\Product;
use App\Domain\Stock\Models\OutboundTransaction;
use RuntimeException;

class AddScanToOutbound
{
    /**
     * Cari produk by code, tambah ke transaksi (increment qty kalau sudah ada).
     * Return [item, isNewItem, product].
     *
     * @throws RuntimeException kalau produk tidak ketemu atau transaksi bukan draft
     */
    public function handle(OutboundTransaction $transaction, string $code, int $quantity = 1): array
    {
        if (!$transaction->isDraft()) {
            throw new RuntimeException('Transaksi sudah selesai / dibatalkan, tidak bisa tambah item lagi.');
        }

        $cleanCode = str_replace(' ', '', $code);
        $product = Product::query()
            ->where('code', $code)
            ->orWhereRaw("REPLACE(code, ' ', '') = ?", [$cleanCode])
            ->first();
        if (!$product) {
            throw new RuntimeException("Produk dengan kode \"{$code}\" tidak ditemukan.");
        }

        $item = $transaction->items()->firstOrNew(['product_id' => $product->id]);
        $isNew = !$item->exists;

        if ($isNew) {
            $item->quantity = $quantity;
            $item->scanned_at = now();
        } else {
            $item->quantity += $quantity;
        }
        $item->save();

        $transaction->recalculateTotalQty();

        return [$item->fresh(['product']), $isNew, $product];
    }
}
