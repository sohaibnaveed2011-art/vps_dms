<?php

namespace Database\Factories; // Ensure this matches your folder structure

use App\Models\Inventory\Brand;
use App\Models\Inventory\BrandModel; // Import the specific model
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inventory\BrandModel>
 */
class BrandModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = BrandModel::class; // Explicitly link the model

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'organization_id' => 1,
            'brand_id' => Brand::factory(),
            'name' => ucfirst($name),
            'series' => 'Series '.strtoupper($this->faker->randomLetter()),
            'slug' => str()->slug($name),
            'is_active' => true,
        ];
    }
}
