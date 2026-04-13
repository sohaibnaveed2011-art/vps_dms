<?php

namespace Database\Seeders\hrms;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShiftsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('shifts')->insert([
            ['name' => 'Morning', 'shift_type' => 'rotating', 'start_time' => Carbon::createFromTime(9, 0, 0)->format('H:i:s'), 'end_time' => Carbon::createFromTime(5, 0, 0)->format('H:i:s'), 'created_at' => '2023-12-27 09:37:50', 'updated_at' => '2023-12-27 09:37:50'],
            ['name' => 'Evening', 'shift_type' => 'rotating', 'start_time' => Carbon::createFromTime(5, 0, 0)->format('H:i:s'), 'end_time' => Carbon::createFromTime(1, 0, 0)->format('H:i:s'), 'created_at' => '2023-12-27 09:37:50', 'updated_at' => '2023-12-27 09:37:50'],
            ['name' => 'Night', 'shift_type' => 'rotating', 'start_time' => Carbon::createFromTime(1, 0, 0)->format('H:i:s'), 'end_time' => Carbon::createFromTime(9, 0, 0)->format('H:i:s'), 'created_at' => '2023-12-27 09:37:50', 'updated_at' => '2023-12-27 09:37:50'],
            ['name' => 'Regular', 'shift_type' => 'fixed', 'start_time' => Carbon::createFromTime(9, 0, 0)->format('H:i:s'), 'end_time' => Carbon::createFromTime(6, 0, 0)->format('H:i:s'), 'created_at' => '2023-12-27 09:37:50', 'updated_at' => '2023-12-27 09:37:50'],
        ]);
    }
}
