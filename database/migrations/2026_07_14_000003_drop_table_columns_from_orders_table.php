<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['table_id']);
            $table->dropColumn(['table_id', 'order_type', 'source', 'seen_at']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('table_id')->nullable()->after('token_number')->constrained('restaurant_tables')->nullOnDelete();
            $table->enum('order_type', ['dine_in', 'takeaway', 'delivery', 'vip_room'])->default('dine_in')->after('table_id');
            $table->enum('source', ['staff', 'qr'])->default('staff')->after('order_type');
            $table->timestamp('seen_at')->nullable()->after('source');
        });
    }
};
