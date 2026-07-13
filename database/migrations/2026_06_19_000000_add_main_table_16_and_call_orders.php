<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Target for the main section: 20 cards total
        //   - 16 "Table" cards      (table_number 1 – 16)
        //   - 4  "Call Order" cards (table_number 17 – 20)

        // Remove any stray main cards in 17–35 (left over from an earlier draft),
        // but never touch a card that already has an order attached.
        $strays = DB::table('restaurant_tables')
            ->where('section', 'main')
            ->whereBetween('table_number', [17, 35])
            ->pluck('id');

        foreach ($strays as $id) {
            $hasOrders = DB::table('orders')->where('table_id', $id)->exists();
            if (!$hasOrders) {
                DB::table('restaurant_tables')->where('id', $id)->delete();
            }
        }

        // Ensure Table 16 exists (Tables 1–15 already exist from earlier seeds).
        $this->ensureCard(16, 'Table 16');

        // Ensure the 4 "Call Order" cards exist at 17–20.
        foreach (range(1, 4) as $n) {
            $this->ensureCard(16 + $n, 'Call Order ' . $n);
        }
    }

    public function down(): void
    {
        DB::table('restaurant_tables')
            ->where('section', 'main')
            ->whereBetween('table_number', [16, 20])
            ->delete();
    }

    private function ensureCard(int $number, string $name): void
    {
        $card = DB::table('restaurant_tables')->where('table_number', $number)->first();

        if ($card) {
            // Correct the label if a stray with this number survived (had an order).
            DB::table('restaurant_tables')
                ->where('id', $card->id)
                ->update(['name' => $name, 'updated_at' => now()]);
        } else {
            DB::table('restaurant_tables')->insert([
                'table_number' => $number,
                'name' => $name,
                'capacity' => 6,
                'status' => 'available',
                'section' => 'main',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
