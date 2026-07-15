<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->date('token_date')->nullable()->after('order_number');
            $table->unsignedInteger('token_number')->nullable()->after('token_date');
            $table->unique(['token_date', 'token_number']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['token_date', 'token_number']);
            $table->dropColumn(['token_date', 'token_number']);
        });
    }
};
