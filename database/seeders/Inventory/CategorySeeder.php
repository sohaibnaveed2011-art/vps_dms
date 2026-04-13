<?php

namespace Database\Seeders\Inventory;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{


    public function run(): void
    {
        $categories = [
            'Air Conditioners',
            'Refrigerators & Freezers',
            'Home Entertainment',
            'Washing Solutions',
            'Water Dispenser',
            'Heating Solutions',
            'Kitchen Appliances',
            'Personal Care',
            'Small Appliances',
            'Home Appliances',
        ];

        $data = array_map(fn($name) => [
            'organization_id' => 1,
            'name' => $name,
            'slug' => Str::slug($name),
            'created_at'   => now(),
            'updated_at'   => now(),
        ], $categories);

        DB::table('categories')->insert($data);
    }
}
