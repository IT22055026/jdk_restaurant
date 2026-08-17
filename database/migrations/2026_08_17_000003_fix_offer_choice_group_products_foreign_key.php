<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('offer_choice_group_products', 'offer_choice_group_id')) {
            Schema::dropIfExists('offer_choice_group_products');
            Schema::create('offer_choice_group_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('offer_choice_group_id')->constrained('offer_choice_groups')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['offer_choice_group_id', 'product_id'], 'ocgp_group_product_unique');
            });
        }
    }

    public function down(): void
    {
    }
};
