<?php

namespace Tests\Feature\Domain\Stock\Models;

use App\Domain\Product\Models\Product;
use App\Domain\Stock\Models\OutboundTransaction;
use App\Domain\Stock\Models\OutboundTransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutboundTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function makeTx(array $attrs = []): OutboundTransaction
    {
        return OutboundTransaction::create(array_merge([
            'doc_no' => 'SJ-20260726-001',
            'doc_date' => '2026-07-26',
            'status' => OutboundTransaction::STATUS_DRAFT,
            'total_qty' => 0,
        ], $attrs));
    }

    protected function addItem(OutboundTransaction $tx, Product $product, int $qty): OutboundTransactionItem
    {
        return $tx->items()->create([
            'product_id' => $product->id,
            'quantity' => $qty,
            'scanned_at' => now(),
        ]);
    }

    public function test_items_relation_returns_related_items(): void
    {
        $tx = $this->makeTx();
        $product = Product::factory()->create();
        $this->addItem($tx, $product, 2);

        $this->assertCount(1, $tx->items);
        $this->assertInstanceOf(OutboundTransactionItem::class, $tx->items->first());
    }

    public function test_creator_relation_returns_user(): void
    {
        $user = User::factory()->create();
        $tx = $this->makeTx(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $tx->creator);
        $this->assertSame($user->id, $tx->creator->id);
    }

    public function test_status_helpers_reflect_current_status(): void
    {
        $draft = $this->makeTx(['doc_no' => 'SJ-A', 'status' => OutboundTransaction::STATUS_DRAFT]);
        $done = $this->makeTx(['doc_no' => 'SJ-B', 'status' => OutboundTransaction::STATUS_COMPLETED]);
        $cancel = $this->makeTx(['doc_no' => 'SJ-C', 'status' => OutboundTransaction::STATUS_CANCELLED]);

        $this->assertTrue($draft->isDraft());
        $this->assertFalse($draft->isCompleted());

        $this->assertTrue($done->isCompleted());
        $this->assertFalse($done->isDraft());

        $this->assertTrue($cancel->isCancelled());
        $this->assertFalse($cancel->isDraft());
    }

    public function test_recalculate_total_qty_sums_all_item_quantities(): void
    {
        $tx = $this->makeTx();
        $p1 = Product::factory()->create();
        $p2 = Product::factory()->create();
        $this->addItem($tx, $p1, 3);
        $this->addItem($tx, $p2, 4);

        $tx->recalculateTotalQty();

        $this->assertSame(7, $tx->fresh()->total_qty);
    }

    public function test_soft_delete_hides_from_default_query_but_kept_in_db(): void
    {
        $tx = $this->makeTx();
        $tx->delete();

        $this->assertNull(OutboundTransaction::find($tx->id));
        $this->assertNotNull(OutboundTransaction::withTrashed()->find($tx->id));
    }

    public function test_deleting_transaction_cascades_to_items(): void
    {
        $tx = $this->makeTx();
        $product = Product::factory()->create();
        $this->addItem($tx, $product, 1);

        $tx->forceDelete();

        $this->assertDatabaseCount('outbound_transaction_items', 0);
    }
}
