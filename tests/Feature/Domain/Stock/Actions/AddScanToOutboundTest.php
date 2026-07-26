<?php

namespace Tests\Feature\Domain\Stock\Actions;

use App\Domain\Product\Models\Product;
use App\Domain\Stock\Actions\AddScanToOutbound;
use App\Domain\Stock\Models\OutboundTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AddScanToOutboundTest extends TestCase
{
    use RefreshDatabase;

    protected function makeDraft(): OutboundTransaction
    {
        return OutboundTransaction::create([
            'doc_no' => 'SJ-20260726-001',
            'doc_date' => '2026-07-26',
            'status' => OutboundTransaction::STATUS_DRAFT,
            'total_qty' => 0,
        ]);
    }

    public function test_adds_new_product_as_item_with_quantity_one(): void
    {
        $product = Product::factory()->create(['code' => 'OF 1010 04']);
        $tx = $this->makeDraft();

        [$item, $isNew] = app(AddScanToOutbound::class)->handle($tx, 'OF 1010 04');

        $this->assertTrue($isNew);
        $this->assertSame($product->id, $item->product_id);
        $this->assertSame(1, $item->quantity);
        $this->assertNotNull($item->scanned_at);
        $this->assertDatabaseCount('outbound_transaction_items', 1);
    }

    public function test_increments_quantity_when_same_product_scanned_again(): void
    {
        Product::factory()->create(['code' => 'OF 1010 04']);
        $tx = $this->makeDraft();

        app(AddScanToOutbound::class)->handle($tx, 'OF 1010 04');
        [$item, $isNew] = app(AddScanToOutbound::class)->handle($tx, 'OF 1010 04');

        $this->assertFalse($isNew);
        $this->assertSame(2, $item->quantity);
        $this->assertDatabaseCount('outbound_transaction_items', 1);
    }

    public function test_respects_custom_quantity_argument(): void
    {
        Product::factory()->create(['code' => 'OF 1010 04']);
        $tx = $this->makeDraft();

        [$item] = app(AddScanToOutbound::class)->handle($tx, 'OF 1010 04', 5);

        $this->assertSame(5, $item->quantity);
    }

    public function test_recalculates_total_qty_across_all_items(): void
    {
        Product::factory()->create(['code' => 'OF 1010 04']);
        Product::factory()->create(['code' => 'OF 5131 25']);
        $tx = $this->makeDraft();

        app(AddScanToOutbound::class)->handle($tx, 'OF 1010 04');
        app(AddScanToOutbound::class)->handle($tx, 'OF 1010 04');
        app(AddScanToOutbound::class)->handle($tx, 'OF 5131 25', 3);

        $tx->refresh();
        $this->assertSame(5, $tx->total_qty);
    }

    public function test_throws_when_product_code_not_found(): void
    {
        $tx = $this->makeDraft();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Produk dengan kode "TIDAK-ADA" tidak ditemukan.');

        app(AddScanToOutbound::class)->handle($tx, 'TIDAK-ADA');
    }

    public function test_throws_when_transaction_not_draft(): void
    {
        Product::factory()->create(['code' => 'OF 1010 04']);
        $tx = $this->makeDraft();
        $tx->update(['status' => OutboundTransaction::STATUS_COMPLETED]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Transaksi sudah selesai / dibatalkan, tidak bisa tambah item lagi.');

        app(AddScanToOutbound::class)->handle($tx, 'OF 1010 04');
    }
}
