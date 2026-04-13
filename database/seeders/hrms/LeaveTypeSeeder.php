<?php

namespace Database\Seeders\hrms;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        //Sick Leave/Casual Leave/Annual Leave/Maternity Leave/Parental Leave
        DB::table('leave_types')->insert([
            ['type' => 'Sick Leave', 'created_at' => '2023-12-27 09:37:50', 'updated_at' => '2023-12-27 09:37:50'],
            ['type' => 'Casual Leave', 'created_at' => '2023-12-27 09:37:50', 'updated_at' => '2023-12-27 09:37:50'],
            ['type' => 'Annual Leave', 'created_at' => '2023-12-27 09:37:50', 'updated_at' => '2023-12-27 09:37:50'],
            ['type' => 'Maternity Leave', 'created_at' => '2023-12-27 09:37:50', 'updated_at' => '2023-12-27 09:37:50'],
            ['type' => 'Parental Leave', 'created_at' => '2023-12-27 09:37:50', 'updated_at' => '2023-12-27 09:37:50'],
        ]);
    }
}
