<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offer_ingredients', function (Blueprint $table) {
            $table->decimal('quantity', 12, 3)->default(1)->after('ingredient_id');
        });
    }

    public function down(): void
    {
        Schema::table('offer_ingredients', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};
