<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('offer_id')->nullable()->after('product_id')->constrained('offers')->nullOnDelete();
            $table->foreignId('ingredient_id')->nullable()->after('offer_id')->constrained('ingredients')->nullOnDelete();
            $table->boolean('is_offer_component')->default(false)->after('ingredient_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('is_offer_component');
            $table->dropConstrainedForeignId('ingredient_id');
            $table->dropConstrainedForeignId('offer_id');
        });
    }
};
