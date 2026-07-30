<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // The business date (06:00-05:59 the next day, see App\Support\BusinessDay)
            // this order's number was sequenced under — order_number itself encodes
            // this, but keeping it as its own indexed column is what makes finding
            // "today's next number" a fast, lockable query instead of parsing strings.
            $table->date('business_date')->nullable()->after('order_number');
            $table->unsignedInteger('daily_sequence')->nullable()->after('business_date');
            // Unique, not just indexed: the very first order of a new business
            // day has no prior row to lock against (lockForUpdate() alone can't
            // prevent two simultaneous "first order" requests from computing the
            // same number), so PosController::createOrder() relies on this
            // constraint plus a retry-on-conflict loop as the real guarantee.
            $table->unique(['business_date', 'daily_sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['business_date', 'daily_sequence']);
            $table->dropColumn(['business_date', 'daily_sequence']);
        });
    }
};
