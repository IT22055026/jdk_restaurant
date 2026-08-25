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
            $table->string('split_method1', 30)->nullable()->after('payment_method');
            $table->decimal('split_amount1', 12, 2)->nullable()->after('split_method1');
            $table->string('split_method2', 30)->nullable()->after('split_amount1');
            $table->decimal('split_amount2', 12, 2)->nullable()->after('split_method2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['split_method1', 'split_amount1', 'split_method2', 'split_amount2']);
        });
    }
};
