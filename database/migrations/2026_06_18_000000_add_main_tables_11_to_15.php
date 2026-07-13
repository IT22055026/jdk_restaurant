<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (range(11, 15) as $i) {
            // Skip if a table with this number already exists
            $exists = DB::table('restaurant_tables')
                ->where('table_number', $i)
                ->exists();

            if (!$exists) {
                DB::table('restaurant_tables')->insert([
                    'table_number' => $i,
                    'name' => 'Table ' . $i,
                    'capacity' => 6,
                    'status' => 'available',
                    'section' => 'main',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('restaurant_tables')
            ->where('section', 'main')
            ->whereIn('table_number', range(11, 15))
            ->delete();
    }
};
