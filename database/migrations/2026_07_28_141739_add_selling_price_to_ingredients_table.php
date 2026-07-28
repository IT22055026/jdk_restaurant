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
        Schema::table('ingredients', function (Blueprint $table) {
            // What a customer is charged when this included item is sold
            // directly (e.g. "extra mayonnaise") rather than used as part of
            // a recipe/offer — distinct from cost_per_unit, which is what we
            // pay the supplier.
            $table->decimal('selling_price', 12, 2)->nullable()->after('cost_per_unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn('selling_price');
        });
    }
};
