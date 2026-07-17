<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Snapshot of the offer's "quantity per unit sold" for an included-item
            // component line, captured when the offer was added to the bill so later
            // edits to the offer's recipe don't retroactively change past orders.
            $table->decimal('offer_component_qty', 12, 3)->nullable()->after('is_offer_component');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('offer_component_qty');
        });
    }
};
