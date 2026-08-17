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
        Schema::table('offers', function (Blueprint $table) {
            // How many flavour picks the cashier must make when adding this offer at
            // POS (e.g. 2 for a combo that comes with two drinks). Only meaningful
            // when the offer has rows in offer_flavours — see Offer::flavours().
            // Each pick can be any eligible flavour product, and picks don't have
            // to be the same product (e.g. one Mojito + one Lime Soda).
            $table->unsignedInteger('flavour_qty')->default(1)->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('flavour_qty');
        });
    }
};
