<?php

namespace Tests\Feature\Domain\Stock;

use App\Domain\Product\Models\Product;
use App\Domain\Stock\Models\OutboundTransaction;
use App\Filament\Pages\ScanOutbound;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScanOutboundPickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_ambiguous_scan_opens_picker_modal_with_candidates(): void
    {
        $this->actingAs(User::factory()->create());

        Product::factory()->create(['code' => 'OF 1010 04', 'name' => 'Produk A']);
        Product::factory()->create(['code' => 'OF 1010 04', 'name' => 'Produk B']);

        $tx = OutboundTransaction::create([
            'doc_no' => 'SJ-TEST-001',
            'doc_date' => '2026-07-26',
            'status' => OutboundTransaction::STATUS_DRAFT,
            'total_qty' => 0,
        ]);

        Livewire::test(ScanOutbound::class, ['transaction' => $tx])
            ->set('scanInput', 'OF 1010 04')
            ->call('submitScan')
            ->assertDispatched('open-modal', id: 'pick-product')
            ->assertCount('pickerCandidates', 2);
    }
}
