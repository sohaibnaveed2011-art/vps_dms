<?php

namespace Database\Seeders;

use App\Models\Core\Organization;
use Database\Seeders\Inventory\BrandSeeder;
use Database\Seeders\Inventory\CategorySeeder;
use Database\Seeders\Inventory\VariationSeeder;
use Database\Seeders\Inventory\VariationValuesSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\Inventory\UomSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\VoucherTypeSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::get()->first();
        
        if(!$organization){
            $this->call([
                RoleSeeder::class,
                PermissionSeeder::class,
                UserSeeder::class,
            ]);
        }

        if($organization){
            $this->call([
                UomSeeder::class,
                CategorySeeder::class,
                VariationSeeder::class,
                VariationValuesSeeder::class,
                BrandSeeder::class,
                VoucherTypeSeeder::class,
            ]);
        }
        
    }
}
