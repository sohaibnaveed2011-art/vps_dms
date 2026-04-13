<?php

namespace Database\Seeders\Inventory;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class variationseeder extends Seeder
{

public function run(): void
{
    $variations = [
        ['name' => 'Size', 'has_multiple' => true],
        ['name' => 'Color', 'has_multiple' => true],
        ['name' => 'Shape', 'has_multiple' => false],
    ];

    $data = [];

    foreach ($variations as $variation) {
        $data[] = [
            'organization_id' => 1, // Assuming all variations belong to organization with ID 1
            'name'         => $variation['name'],
            'short_name'   => Str::lower($variation['name']), // e.g., 'size'
            'has_multiple' => $variation['has_multiple'],    // true or false
            'created_at'   => now(),
            'updated_at'   => now(),
        ];
    }

    DB::table('variations')->insert($data);
}
}
