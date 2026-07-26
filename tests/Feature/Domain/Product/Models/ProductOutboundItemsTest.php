<?php

namespace Tests\Feature\Domain\Product\Models;

use App\Domain\Product\Models\Product;
use App\Domain\Stock\Models\OutboundTransaction;
use App\Domain\Stock\Models\OutboundTransactionItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductOutboundItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_outbound_items_relation_returns_related_items(): void
    {
        $product = Product::factory()->create();
        $tx = OutboundTransaction::create([
            'doc_no' => 'SJ-20260726-001',
            'doc_date' => '2026-07-26',
            'status' => OutboundTransaction::STATUS_COMPLETED,
            'total_qty' => 3,
        ]);
        $tx->items()->create([
            'product_id' => $product->id,
            'quantity' => 3,
            'scanned_at' => now(),
        ]);

        $this->assertCount(1, $product->outboundItems);
        $this->assertInstanceOf(OutboundTransactionItem::class, $product->outboundItems->first());
        $this->assertSame(3, $product->outboundItems->first()->quantity);
    }

    public function test_aggregate_queries_used_by_rekap_view(): void
    {
        // Simulasi pattern query di ListOutboundTransactions::buildRekapTable
        $product = Product::factory()->create();

        $txCompleted = OutboundTransaction::create([
            'doc_no' => 'SJ-A', 'doc_date' => '2026-07-25',
            'status' => OutboundTransaction::STATUS_COMPLETED, 'total_qty' => 5,
        ]);
        $txCompleted->items()->create(['product_id' => $product->id, 'quantity' => 5, 'scanned_at' => '2026-07-25 10:00:00']);

        $txDraft = OutboundTransaction::create([
            'doc_no' => 'SJ-B', 'doc_date' => '2026-07-26',
            'status' => OutboundTransaction::STATUS_DRAFT, 'total_qty' => 99,
        ]);
        $txDraft->items()->create(['product_id' => $product->id, 'quantity' => 99, 'scanned_at' => '2026-07-26 10:00:00']);

        $result = Product::query()
            ->withSum(
                ['outboundItems as total_qty_out' => fn ($q) => $q->whereHas('transaction', fn ($qq) => $qq->where('status', OutboundTransaction::STATUS_COMPLETED))],
                'quantity'
            )
            ->where('id', $product->id)
            ->first();

        // Hanya menghitung dari transaksi completed, mengabaikan draft
        $this->assertEquals(5, $result->total_qty_out);
    }
}
