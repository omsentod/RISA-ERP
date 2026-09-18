<?php

namespace Tests\Feature\Domain\Stock\Actions;

use App\Domain\Product\Models\Product;
use App\Domain\Stock\Models\OutboundTransaction;
use App\Domain\Stock\Models\OutboundTransactionItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrintSuratJalanPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_surat_jalan_single_page_when_few_items(): void
    {
        $tx = OutboundTransaction::create([
            'doc_no' => 'SJ-20260918-001',
            'doc_date' => '2026-09-18',
            'status' => OutboundTransaction::STATUS_COMPLETED,
            'total_qty' => 5,
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $product = Product::factory()->create(['code' => "PROD-{$i}"]);
            OutboundTransactionItem::create([
                'outbound_transaction_id' => $tx->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'lot_number' => "LOT{$i}",
                'scanned_at' => now(),
            ]);
        }

        $tx->load(['items.product.registration', 'creator']);
        $html = view('partials.print-surat-jalan', ['transaction' => $tx])->render();

        // 1 page only
        $this->assertSame(1, substr_count($html, '<div class="page">'));

        // No page indicator for 1-page documents
        $this->assertStringNotContainsString('Hal :', $html);

        // All 5 items are displayed
        for ($i = 1; $i <= 5; $i++) {
            $this->assertStringContainsString("<td class=\"col-no\">{$i}</td>", $html);
        }

        // Empty rows fill grid
        $this->assertGreaterThan(0, substr_count($html, '<tr class="empty-row">'));

        // Signature section is present
        $this->assertSame(1, substr_count($html, 'Yang Menyerahkan'));
        $this->assertStringContainsString('Total', $html);
        $this->assertStringContainsString('5 Pcs', $html);
    }

    public function test_surat_jalan_chunks_into_multiple_pages_for_100_items(): void
    {
        $tx = OutboundTransaction::create([
            'doc_no' => 'SJ-20260918-002',
            'doc_date' => '2026-09-18',
            'status' => OutboundTransaction::STATUS_COMPLETED,
            'total_qty' => 100,
        ]);

        for ($i = 1; $i <= 100; $i++) {
            $product = Product::factory()->create(['code' => "PROD-{$i}"]);
            OutboundTransactionItem::create([
                'outbound_transaction_id' => $tx->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'lot_number' => sprintf('LOT%03d', $i),
                'scanned_at' => now(),
            ]);
        }

        $tx->load(['items.product.registration', 'creator']);
        $html = view('partials.print-surat-jalan', ['transaction' => $tx])->render();

        // Exactly 3 pages generated for 100 items (40 per page: 40 + 40 + 20)
        $this->assertSame(3, substr_count($html, '<div class="page">'));

        // Page numbering displayed
        $this->assertStringContainsString('Hal : 1 / 3', $html);
        $this->assertStringContainsString('Hal : 2 / 3', $html);
        $this->assertStringContainsString('Hal : 3 / 3', $html);

        // Sequential row numbering from 1 to 100
        $this->assertStringContainsString('<td class="col-no">1</td>', $html);
        $this->assertStringContainsString('<td class="col-no">40</td>', $html);
        $this->assertStringContainsString('<td class="col-no">41</td>', $html);
        $this->assertStringContainsString('<td class="col-no">80</td>', $html);
        $this->assertStringContainsString('<td class="col-no">81</td>', $html);
        $this->assertStringContainsString('<td class="col-no">100</td>', $html);

        // Continuation indicators on intermediate pages
        $this->assertStringContainsString('(Bersambung ke Halaman 2...)', $html);
        $this->assertStringContainsString('(Bersambung ke Halaman 3...)', $html);

        // Signature section exists exactly once (on the last page)
        $this->assertSame(1, substr_count($html, 'Yang Menyerahkan'));

        // Grand Total appears on the last page
        $this->assertStringContainsString('100 Pcs', $html);
    }
}
