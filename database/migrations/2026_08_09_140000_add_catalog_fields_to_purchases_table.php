<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets a purchase line item link straight to a catalog Finished Good (product)
     * or Included Item (ingredient) instead of only a free-typed item_name. A row
     * still links to at most one of the two — enforced in the controller, not the DB.
     *
     * purchase_category_id becomes nullable because catalog-linked rows no longer
     * require a manual category pick; it stays required for the "Other" fallback.
     *
     * reference_no groups the line items created together in one "add multiple
     * items, then save" submission from the new Purchase Module UI.
     */
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('supplier_id')->constrained('products')->nullOnDelete();
            $table->foreignId('ingredient_id')->nullable()->after('product_id')->constrained('ingredients')->nullOnDelete();
            $table->string('reference_no', 40)->nullable()->after('user_id')->index();
            $table->string('supplier_invoice_no', 100)->nullable()->after('reference_no');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('purchase_category_id')->nullable()->change();
            // Ingredients carry a free-typed unit (e.g. "packets"), not just the fixed
            // kg/ltr/pcs list, so the column needs more room than the original 10 chars.
            $table->string('unit', 30)->default('kg')->change();
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('purchase_category_id')->nullable(false)->change();
            $table->string('unit', 10)->default('kg')->change();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
            $table->dropConstrainedForeignId('ingredient_id');
            $table->dropColumn(['reference_no', 'supplier_invoice_no']);
        });
    }
};
