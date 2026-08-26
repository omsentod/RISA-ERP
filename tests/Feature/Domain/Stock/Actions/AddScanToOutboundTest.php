<?php

namespace Tests\Feature\Domain\Stock\Actions;

use App\Domain\Product\Models\Product;
use App\Domain\Stock\Actions\AddScanToOutbound;
use App\Domain\Stock\Exceptions\AmbiguousScanException;
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

        $result = app(AddScanToOutbound::class)->handle($tx, 'OF 1010 04');

        $this->assertTrue($result['isNew']);
        $this->assertSame($product->id, $result['item']->product_id);
        $this->assertSame(1, $result['item']->quantity);
        $this->assertNotNull($result['item']->scanned_at);
        $this->assertDatabaseCount('outbound_transaction_items', 1);
    }

    public function test_increments_quantity_when_same_product_scanned_again(): void
    {
        Product::factory()->create(['code' => 'OF 1010 04']);
        $tx = $this->makeDraft();

        app(AddScanToOutbound::class)->handle($tx, 'OF 1010 04');
        $result = app(AddScanToOutbound::class)->handle($tx, 'OF 1010 04');

        $this->assertFalse($result['isNew']);
        $this->assertSame(2, $result['item']->quantity);
        $this->assertDatabaseCount('outbound_transaction_items', 1);
    }

    public function test_reads_quantity_from_barcode_pattern(): void
    {
        Product::factory()->create(['code' => 'OF 1010 04']);
        $tx = $this->makeDraft();

        $result = app(AddScanToOutbound::class)->handle($tx, 'OF 1010 04*5');

        $this->assertSame(5, $result['item']->quantity);
    }

    public function test_recalculates_total_qty_across_all_items(): void
    {
        Product::factory()->create(['code' => 'OF 1010 04']);
        Product::factory()->create(['code' => 'OF 5131 25']);
        $tx = $this->makeDraft();

        app(AddScanToOutbound::class)->handle($tx, 'OF 1010 04');
        app(AddScanToOutbound::class)->handle($tx, 'OF 1010 04');
        app(AddScanToOutbound::class)->handle($tx, 'OF 5131 25*3');

        $tx->refresh();
        $this->assertSame(5, $tx->total_qty);
    }

    public function test_throws_when_product_code_not_found(): void
    {
        $tx = $this->makeDraft();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Kode "TIDAK-ADA" tidak ditemukan.');

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

    public function test_throws_ambiguous_when_code_owned_by_multiple_products(): void
    {
        Product::factory()->create(['code' => 'DUP 001', 'name' => 'Produk A']);
        Product::factory()->create(['code' => 'DUP 001', 'name' => 'Produk B']);
        $tx = $this->makeDraft();

        try {
            app(AddScanToOutbound::class)->handle($tx, 'DUP 001*4');
            $this->fail('AmbiguousScanException was not thrown.');
        } catch (AmbiguousScanException $e) {
            $this->assertCount(2, $e->candidates);
            $this->assertSame(4, $e->qtyToAdd);
            $this->assertDatabaseCount('outbound_transaction_items', 0);
        }
    }

    public function test_add_product_adds_specific_product_chosen_from_candidates(): void
    {
        Product::factory()->create(['code' => 'DUP 001', 'name' => 'Produk A']);
        $chosen = Product::factory()->create(['code' => 'DUP 001', 'name' => 'Produk B']);
        $tx = $this->makeDraft();

        $result = app(AddScanToOutbound::class)->addProduct($tx, $chosen, 4);

        $this->assertTrue($result['isNew']);
        $this->assertSame($chosen->id, $result['item']->product_id);
        $this->assertSame(4, $result['item']->quantity);
        $this->assertDatabaseCount('outbound_transaction_items', 1);
    }
}
