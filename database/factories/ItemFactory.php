<?php

namespace Database\Factories;

use App\Models\Inventory\Brand;
use App\Models\Inventory\BrandModel;
use App\Models\Inventory\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inventory\Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(3, true);

        return [
            'organization_id' => 1,
            'category_id' => Category::factory(),
            'brand_id' => Brand::factory(),
            'brand_model_id' => BrandModel::factory(),
            'base_unit_id' => 1,
            'tax_id' => 1,
            'name' => ucfirst($name),
            'sku' => $this->faker->unique()->bothify('SKU-####-????'),
            'description' => $this->faker->paragraph(),
            'valuation_method' => 'FIFO',
            'cost_price' => $this->faker->randomFloat(2, 100, 500),
            'sale_price' => $this->faker->randomFloat(2, 600, 1500),
            'is_bookable' => true,
            'is_active' => true,
            // External API Image: 600x400 random image based on item name
            'image_url' => 'https://picsum.photos/seed/'.str()->slug($name).'/600/400',
        ];
    }
}
