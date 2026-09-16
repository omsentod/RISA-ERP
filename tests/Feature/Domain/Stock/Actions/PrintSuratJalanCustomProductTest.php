<?php

namespace Tests\Feature\Domain\Stock\Actions;

use App\Domain\Product\Models\Product;
use App\Domain\Stock\Actions\AddScanToOutbound;
use App\Domain\Stock\Models\OutboundTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrintSuratJalanCustomProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_surat_jalan_appends_dot_for_custom_products(): void
    {
        // 1. Create a custom product and a standard product
        $customProduct = Product::factory()->custom()->create([
            'code' => 'OF 1076 05LR',
            'name' => 'Custom Plate 5 Holes Left Right',
        ]);

        $standardProduct = Product::factory()->create([
            'code' => 'OF 1010 04',
            'name' => 'Standard Plate 4 Holes',
            'is_custom' => false,
        ]);

        // 2. Outbound transaction
        $tx = OutboundTransaction::create([
            'doc_no' => 'SJ-20260916-001',
            'doc_date' => '2026-09-16',
            'status' => OutboundTransaction::STATUS_DRAFT,
            'total_qty' => 0,
        ]);

        $adder = app(AddScanToOutbound::class);
        $adder->addProduct($tx, $customProduct, 2, '122609001');
        $adder->addProduct($tx, $standardProduct, 1, '122609002');

        $tx->update(['status' => OutboundTransaction::STATUS_COMPLETED]);
        $tx->load(['items.product.registration', 'creator']);

        // 3. Render Surat Jalan view
        $html = view('partials.print-surat-jalan', ['transaction' => $tx])->render();

        // 4. Assert custom product code ends with dot
        $this->assertStringContainsString('OF 1076 05LR.', $html);

        // 5. Assert standard product code does NOT end with dot
        $this->assertStringContainsString('OF 1010 04</td>', $html);
    }
}
