<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // 'dine_in' = customer eating inside the restaurant
            // 'takeaway' = customer taking food away / packed
            $table->enum('order_type', ['dine_in', 'takeaway'])
                ->default('dine_in')
                ->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('order_type');
        });
    }
};
