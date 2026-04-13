<?php

namespace Database\Seeders\Inventory;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UomSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $organizationId = 1;

        $units = [
            ['name' => 'Metric Ton',      'short_name' => 'MT',       'allow_decimal' => 1],
            ['name' => 'Bill of Lading',  'short_name' => 'Bol',      'allow_decimal' => 0],
            ['name' => 'Set',             'short_name' => 'set',      'allow_decimal' => 0],
            ['name' => 'Square Meter',    'short_name' => 'Sq.m',     'allow_decimal' => 0],
            ['name' => 'Number',          'short_name' => 'No',       'allow_decimal' => 0],
            ['name' => 'Kilo Watt Hour',  'short_name' => 'KWH',      'allow_decimal' => 0],
            ['name' => 'Ton',             'short_name' => 'T (40KG)', 'allow_decimal' => 0],
            ['name' => 'Square Yard',     'short_name' => 'Sq. yd',   'allow_decimal' => 0],
            ['name' => 'Mega Watt',       'short_name' => 'MW',       'allow_decimal' => 0],
            ['name' => 'Kilogram',        'short_name' => 'KG',       'allow_decimal' => 0],
            ['name' => 'Meter',           'short_name' => 'mtr',      'allow_decimal' => 0], // Changed 'm' to 'mtr' to avoid conflict
            ['name' => 'Foot',            'short_name' => 'ft',       'allow_decimal' => 0],
            ['name' => 'Barrels',         'short_name' => 'bbl',      'allow_decimal' => 0],
            ['name' => 'Pieces',          'short_name' => 'PCS',      'allow_decimal' => 0],
            ['name' => 'Units',           'short_name' => 'U',        'allow_decimal' => 0],
            ['name' => 'Caret',           'short_name' => 'Caret',    'allow_decimal' => 0],
            ['name' => 'Dozen',           'short_name' => 'Doz',      'allow_decimal' => 0],
            ['name' => 'Gram',            'short_name' => 'GM',       'allow_decimal' => 0],
            ['name' => 'Gallon',          'short_name' => 'gal',      'allow_decimal' => 0],
            ['name' => 'Ounce',           'short_name' => 'oz',       'allow_decimal' => 0],
            ['name' => 'Pound',           'short_name' => 'lb',       'allow_decimal' => 0],
            ['name' => 'Timber Logs',     'short_name' => 'TL',       'allow_decimal' => 0],
            ['name' => 'Packs',           'short_name' => 'pk',       'allow_decimal' => 0],
            ['name' => 'Pair',            'short_name' => 'pr',       'allow_decimal' => 0],
            ['name' => 'Square Feet',     'short_name' => 'Sq. Ft',   'allow_decimal' => 0],
            ['name' => 'Murabbah',        'short_name' => 'M.',       'allow_decimal' => 0], // Added a dot to differentiate
            ['name' => 'Ghamaon',         'short_name' => 'Gham',     'allow_decimal' => 0], // Made more unique than 'Gm'
            ['name' => 'Begah',           'short_name' => 'B',        'allow_decimal' => 0],
            ['name' => 'Kanal',           'short_name' => 'K',        'allow_decimal' => 0],
            ['name' => 'Marla',           'short_name' => 'Ml',       'allow_decimal' => 0],
            ['name' => 'Other',           'short_name' => 'Other',    'allow_decimal' => 0],
        ];

        foreach ($units as $unit) {
            DB::table('units')->updateOrInsert(
                [
                    'organization_id' => $organizationId,
                    'short_name' => $unit['short_name']
                ],
                [
                    'name' => $unit['name'],
                    'allow_decimal' => $unit['allow_decimal'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}