<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Existing orders were created with a random 'ORD-xxxxxxxx' string; new orders now get a
     * sequential 'ORD-000001' style number derived from their id. Backfill old rows to match.
     */
    public function up(): void
    {
        DB::table('orders')->orderBy('id')->select('id')->chunkById(200, function ($orders) {
            foreach ($orders as $order) {
                DB::table('orders')
                    ->where('id', $order->id)
                    ->update(['order_number' => 'ORD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT)]);
            }
        });
    }

    public function down(): void
    {
        // Original random order numbers are not recoverable; irreversible data fix.
    }
};
