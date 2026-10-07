<?php

namespace Tests\Feature\Domain\Product;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductCategory;
use App\Domain\Registration\Models\Registration;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_exact_phrase_search_matches_only_the_exact_product(): void
    {
        $cat = ProductCategory::create(['name' => 'NON LOCKING', 'slug' => 'non-locking']);
        $reg = Registration::create(['nie_number' => '21302420151', 'issuer' => 'BPOM']);

        // Hole 3
        $p3 = Product::create([
            'product_category_id' => $cat->id,
            'registration_id' => $reg->id,
            'code' => 'OF 1030 03 LP',
            'name' => 'Onethird Tubular - Low Profile Plate Hole 3',
            'product_group_code' => '04',
            'default_quantity' => 1,
        ]);

        // Hole 4
        $p4 = Product::create([
            'product_category_id' => $cat->id,
            'registration_id' => $reg->id,
            'code' => 'OF 1030 04 LP',
            'name' => 'Onethird Tubular - Low Profile Plate Hole 4',
            'product_group_code' => '04',
            'default_quantity' => 1,
        ]);

        // Hole 5
        $p5 = Product::create([
            'product_category_id' => $cat->id,
            'registration_id' => $reg->id,
            'code' => 'OF 1030 05 LP',
            'name' => 'Onethird Tubular - Low Profile Plate Hole 5',
            'product_group_code' => '04',
            'default_quantity' => 1,
        ]);

        // Hole 14
        $p14 = Product::create([
            'product_category_id' => $cat->id,
            'registration_id' => $reg->id,
            'code' => 'OF 1030 14 LP',
            'name' => 'Onethird Tubular - Low Profile Plate Hole 14',
            'product_group_code' => '04',
            'default_quantity' => 1,
        ]);

        // Search: "Onethird Tubular - Low Profile Plate Hole 4"
        $results = ProductResource::applyProductSearch(
            Product::query(),
            'Onethird Tubular - Low Profile Plate Hole 4'
        )->get();

        $this->assertCount(1, $results);
        $this->assertEquals($p4->id, $results->first()->id);
        $this->assertFalse($results->contains('id', $p3->id));
        $this->assertFalse($results->contains('id', $p5->id));
        $this->assertFalse($results->contains('id', $p14->id));
    }

    public function test_multi_word_search_does_not_confuse_hole_4_with_hole_14(): void
    {
        $cat = ProductCategory::create(['name' => 'NON LOCKING', 'slug' => 'non-locking']);

        $p4 = Product::create([
            'product_category_id' => $cat->id,
            'code' => 'OF 1030 04',
            'name' => 'Onethird Tubular Plate Hole 4',
            'product_group_code' => '04',
            'default_quantity' => 1,
        ]);

        $p14 = Product::create([
            'product_category_id' => $cat->id,
            'code' => 'OF 1030 14',
            'name' => 'Onethird Tubular Plate Hole 14',
            'product_group_code' => '04',
            'default_quantity' => 1,
        ]);

        $results = ProductResource::applyProductSearch(
            Product::query(),
            'Onethird Hole 4'
        )->get();

        $this->assertCount(1, $results);
        $this->assertEquals($p4->id, $results->first()->id);
        $this->assertFalse($results->contains('id', $p14->id));
    }

    public function test_sku_search_without_spaces_still_matches(): void
    {
        $cat = ProductCategory::create(['name' => 'NON LOCKING', 'slug' => 'non-locking']);

        $p = Product::create([
            'product_category_id' => $cat->id,
            'code' => 'OF 1030 04 LP',
            'name' => 'Onethird Tubular - Low Profile Plate Hole 4',
            'default_quantity' => 1,
        ]);

        $results = ProductResource::applyProductSearch(
            Product::query(),
            'OF 1030 04LP'
        )->get();

        $this->assertCount(1, $results);
        $this->assertEquals($p->id, $results->first()->id);
    }
}
