<?php

namespace Database\Factories;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductCategory;
use App\Domain\Registration\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'product_category_id' => ProductCategory::factory(),
            'registration_id' => Registration::factory(),
            'code' => fake()->unique()->bothify('OF #### ??'),
            'name' => fake()->words(4, true) . ' ' . fake()->numberBetween(2, 20) . ' Holes',
            'description' => null,
            'is_published' => false,
            'published_at' => null,
            'published_by' => null,
        ];
    }

    public function published(): static
    {
        return $this->state([
            'is_published' => true,
            'published_at' => now(),
        ]);
    }
}
