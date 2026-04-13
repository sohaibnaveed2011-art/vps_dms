<?php

namespace Database\Seeders\Inventory;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $organizationId = 1;

        $brands = [
            'TCL', 'Sony', 'Bosch', 'Philips', 'Gree', 
            'Haier', 'Dawlance', 'Ecostar', 'Kenwood', 
            'LG', 'Samsung', 'Orient', 'Panasonic', 
            'Super Asia', 'Homage'
        ];

        $data = array_map(function ($brand) use ($organizationId, $now) {
            return [
                'organization_id' => $organizationId,
                'name'            => $brand,
                'slug'            => Str::slug($brand),
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }, $brands);

        DB::table('brands')->insert($data);
    }
}