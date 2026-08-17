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
        // Fixed finished goods / menu products bundled directly into an offer
        // (e.g. 1 portion of French Fries, 1 Burger).
        if (!Schema::hasTable('offer_products')) {
            Schema::create('offer_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('offer_id')->constrained('offers')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->decimal('quantity', 12, 3)->default(1);
                $table->timestamps();
                $table->unique(['offer_id', 'product_id']);
            });
        }

        // Choice groups for an offer (e.g. "Drinks" - pick 2, "Dessert" - pick 1).
        // Each group specifies how many items the customer must choose (choice_qty)
        // and optionally links to a Category (e.g. all products in Drinks category)
        // and/or specific products via offer_choice_group_products.
        if (!Schema::hasTable('offer_choice_groups')) {
            Schema::create('offer_choice_groups', function (Blueprint $table) {
                $table->id();
                $table->foreignId('offer_id')->constrained('offers')->cascadeOnDelete();
                $table->string('name'); // e.g. "Drinks", "Dessert", "Side Dish"
                $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
                $table->unsignedInteger('choice_qty')->default(1);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // Specific eligible products assigned to a choice group.
        if (!Schema::hasTable('offer_choice_group_products')) {
            Schema::create('offer_choice_group_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('offer_choice_group_id')->constrained('offer_choice_groups')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['offer_choice_group_id', 'product_id'], 'ocgp_group_product_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offer_choice_group_products');
        Schema::dropIfExists('offer_choice_groups');
        Schema::dropIfExists('offer_products');
    }
};
