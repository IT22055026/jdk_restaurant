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
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete()->after('name');
            $table->string('product_code')->nullable()->unique()->after('name');
            $table->decimal('cost_price', 12, 2)->nullable()->after('description');
            $table->decimal('selling_price', 12, 2)->nullable()->after('cost_price');
            $table->boolean('is_unlimited_stock')->default(false)->after('quantity');
            $table->string('barcode')->nullable()->unique()->after('product_code');
            $table->text('image')->nullable()->after('barcode');
            $table->string('supplier')->nullable()->after('image');
            $table->decimal('discount', 5, 2)->nullable()->after('supplier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['category_id', 'product_code', 'cost_price', 'selling_price', 'is_unlimited_stock', 'barcode', 'image', 'supplier', 'discount']);
        });
    }
};
