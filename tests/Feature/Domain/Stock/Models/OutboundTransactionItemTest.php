<?php

namespace Tests\Feature\Domain\Stock\Models;

use App\Domain\Product\Models\Product;
use App\Domain\Stock\Models\OutboundTransaction;
use App\Domain\Stock\Models\OutboundTransactionItem;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutboundTransactionItemTest extends TestCase
{
    use RefreshDatabase;

    protected function makeItem(): OutboundTransactionItem
    {
        $tx = OutboundTransaction::create([
            'doc_no' => 'SJ-20260726-001',
            'doc_date' => '2026-07-26',
            'status' => OutboundTransaction::STATUS_DRAFT,
            'total_qty' => 0,
        ]);

        return $tx->items()->create([
            'product_id' => Product::factory()->create()->id,
            'quantity' => 2,
            'scanned_at' => now(),
        ]);
    }

    public function test_transaction_relation_returns_parent_transaction(): void
    {
        $item = $this->makeItem();

        $this->assertInstanceOf(OutboundTransaction::class, $item->transaction);
        $this->assertSame('SJ-20260726-001', $item->transaction->doc_no);
    }

    public function test_product_relation_returns_related_product(): void
    {
        $item = $this->makeItem();

        $this->assertInstanceOf(Product::class, $item->product);
    }

    public function test_unique_constraint_prevents_duplicate_product_in_same_transaction(): void
    {
        $item = $this->makeItem();

        $this->expectException(QueryException::class);

        $item->transaction->items()->create([
            'product_id' => $item->product_id,
            'quantity' => 1,
            'scanned_at' => now(),
        ]);
    }
}
