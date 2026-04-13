<?php

namespace Database\Seeders\hrms;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RemunerationTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Daily/Hourly/Weekly/Bi-monthly/Monthly/Contract
        DB::table('remuneration_types')->insert([
            ['type' => 'Hourly', 'created_at' => '2023-12-27 09:37:50', 'updated_at' => '2023-12-27 09:37:50'],
            ['type' => 'Weekly', 'created_at' => '2023-12-27 09:37:50', 'updated_at' => '2023-12-27 09:37:50'],
            ['type' => 'Bi-monthly', 'created_at' => '2023-12-27 09:37:50', 'updated_at' => '2023-12-27 09:37:50'],
            ['type' => 'Monthly', 'created_at' => '2023-12-27 09:37:50', 'updated_at' => '2023-12-27 09:37:50'],
            ['type' => 'Contract', 'created_at' => '2023-12-27 09:37:50', 'updated_at' => '2023-12-27 09:37:50'],
        ]);
    }
}
