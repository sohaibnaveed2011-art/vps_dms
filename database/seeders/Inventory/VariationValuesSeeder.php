<?php

namespace Database\Seeders\Inventory;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VariationValuesSeeder extends Seeder
{

public function run(): void
{
    $variations = [
        1 => ['XS', 'SM', 'MD', 'LG', 'XL', 'XXL'], // Sizes
        2 => [ // Colors
            'Red'       => '#FF0000',
            'Green'     => '#00FF00',
            'Blue'      => '#0000FF',
            'White'     => '#FFFFFF',
            'Black'     => '#000000',
            'Orange'    => '#FFA500',
            'Off White' => '#FAF9F6',
        ],
        3 => ['V-shaped', 'Rounded', 'Circular'], // Shapes
    ];

    $data = [];

    foreach ($variations as $id => $values) {
        foreach ($values as $key => $value) {
            $data[] = [
                'organization_id' => 1, // Assuming all values belong to organization_id 1 for seeding
                'variation_id' => $id,
                'value'        => is_numeric($key) ? $value : $key,
                'color_code'   => is_numeric($key) ? null   : $value,
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        }
    }

    DB::table('variation_values')->insert($data);
}
}
