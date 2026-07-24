<?php

namespace Database\Factories;

use App\Domain\Product\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductCategoryFactory extends Factory
{
    protected $model = ProductCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(6, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'is_locking' => fake()->boolean(),
        ];
    }

    public function locking(): static
    {
        return $this->state(['is_locking' => true]);
    }

    public function nonLocking(): static
    {
        return $this->state(['is_locking' => false]);
    }
}
