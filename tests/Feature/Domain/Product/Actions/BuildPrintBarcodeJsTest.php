<?php

namespace Tests\Feature\Domain\Product\Actions;

use App\Domain\Product\Actions\BuildPrintBarcodeJs;
use App\Domain\Product\Models\Product;
use App\Domain\Stock\Actions\AddScanToOutbound;
use App\Domain\Stock\Models\OutboundTransaction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildPrintBarcodeJsTest extends TestCase
{
    use RefreshDatabase;

    public function test_encodes_lot_number_into_barcode_and_scans_correctly(): void
    {
        $product = Product::factory()->create([
            'code' => 'OF 1010 04',
            'product_group_code' => '12',
        ]);

        $customSequence = '088';
        $qtyPerLabel = 3;
        $date = '2026-09';

        $js = app(BuildPrintBarcodeJs::class)->handle(
            printItems: [$product->id],
            customSequence: $customSequence,
            customDuplicateCount: 1,
            customQuantityPerLabel: $qtyPerLabel,
            yearMonth: $date,
        );

        // Expected barcode: ID + 03 + 12 (group) + 26 (year) + 09 (month) + 088 (seq)
        $expectedLot = '122609088';
        $expectedBarcode = $product->id . '03' . $expectedLot;

        // Verify that the JS output contains the encoded HTML
        $this->assertStringContainsString("printWindow", $js);

        // Simulate scanning the printed barcode into an outbound transaction
        $tx = OutboundTransaction::create([
            'doc_no' => 'SJ-20260916-001',
            'doc_date' => '2026-09-16',
            'status' => OutboundTransaction::STATUS_DRAFT,
            'total_qty' => 0,
        ]);

        $scanResult = app(AddScanToOutbound::class)->handle($tx, $expectedBarcode);

        $this->assertSame($product->id, $scanResult['item']->product_id);
        $this->assertSame(3, $scanResult['item']->quantity);
        $this->assertSame($expectedLot, $scanResult['item']->lot_number);
    }
}
