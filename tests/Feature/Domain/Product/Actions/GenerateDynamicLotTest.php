<?php

namespace Tests\Feature\Domain\Product\Actions;

use App\Domain\Product\Actions\GenerateDynamicLot;
use App\Domain\Product\Models\Product;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateDynamicLotTest extends TestCase
{
    use RefreshDatabase;

    public function test_uses_provided_date_for_year_and_month_segments(): void
    {
        $product = Product::factory()->create(['product_group_code' => '12']);

        $lot = app(GenerateDynamicLot::class)->handle($product, '132', Carbon::create(2025, 7, 1));

        $this->assertSame('122507132', $lot);
    }

    public function test_defaults_to_now_when_no_date_given(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 1));
        $product = Product::factory()->create(['product_group_code' => '03']);

        $lot = app(GenerateDynamicLot::class)->handle($product, '007');

        $this->assertSame('032603007', $lot);
        Carbon::setTestNow();
    }
}
