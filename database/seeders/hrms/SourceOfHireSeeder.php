<?php

namespace Database\Seeders\hrms;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SourceOfHireSeeder extends Seeder
{
    public function run(): void
    {
        // Direct/Referrel/Web/Newspaper/Social-Media/Other
        DB::table('source_of_hires')->insert([
            ['source_type' => 'Direct', 'created_at' => '2023-12-27 09:37:50', 'updated_at' => '2023-12-27 09:37:50'],
            ['source_type' => 'Referrel', 'created_at' => '2023-12-27 09:37:50', 'updated_at' => '2023-12-27 09:37:50'],
            ['source_type' => 'Web', 'created_at' => '2023-12-27 09:37:50', 'updated_at' => '2023-12-27 09:37:50'],
            ['source_type' => 'Newspaper', 'created_at' => '2023-12-27 09:37:50', 'updated_at' => '2023-12-27 09:37:50'],
            ['source_type' => 'Social Media', 'created_at' => '2023-12-27 09:37:50', 'updated_at' => '2023-12-27 09:37:50'],
            ['source_type' => 'Other', 'created_at' => '2023-12-27 09:37:50', 'updated_at' => '2023-12-27 09:37:50'],
        ]);
    }
}
