<?php

namespace Database\Seeders\hrms;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaritalStatusSeeder extends Seeder
{
    public function run(): void
    {
        //Married/Unmarried/Widowed/Divorced
        DB::table('marital_statuses')->insert([
            ['status' => 'Married', 'created_at' => '2023-12-27 09:37:50', 'updated_at' => '2023-12-27 09:37:50'],
            ['status' => 'Unmarried', 'created_at' => '2023-12-27 09:37:50', 'updated_at' => '2023-12-27 09:37:50'],
            ['status' => 'Widowed', 'created_at' => '2023-12-27 09:37:50', 'updated_at' => '2023-12-27 09:37:50'],
            ['status' => 'Divorced', 'created_at' => '2023-12-27 09:37:50', 'updated_at' => '2023-12-27 09:37:50'],
        ]);
    }
}
