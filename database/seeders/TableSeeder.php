<?php

namespace Database\Seeders;

use App\Models\RestaurantTable;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    public function run(): void
    {
        // Main section - 15 tables
        for ($i = 1; $i <= 15; $i++) {
            RestaurantTable::create([
                'table_number' => $i,
                'name' => 'Table ' . $i,
                'capacity' => $i <= 5 ? 4 : 6,
                'status' => 'available',
                'section' => 'main',
            ]);
        }

        // VIP section - 2 tables
        for ($i = 1; $i <= 2; $i++) {
            RestaurantTable::create([
                'table_number' => 100 + $i,
                'name' => 'VIP Room ' . $i,
                'capacity' => 8,
                'status' => 'available',
                'section' => 'vip',
            ]);
        }
    }
}
