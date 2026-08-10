<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\IngredientStockMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseCategory;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PurchaseModuleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::where('name', 'Admin')->firstOrFail();
        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_create_page_loads_with_supplier_and_catalog_dropdowns_and_no_category_ui()
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('purchases.create'));

        $response->assertOk();
        $response->assertSee('Supplier');
        $response->assertSee('Finished Goods');
        $response->assertSee('Included Items');
        $response->assertSee('Payment Method');
        $response->assertDontSee('Main Category');
        $response->assertDontSee('Sub-category');
        $response->assertDontSee('Manage Categories');
        $response->assertDontSee('Other Item');
    }

    public function test_purchase_category_management_routes_no_longer_exist()
    {
        $this->assertFalse(Route::has('purchase-categories.index'));
        $this->assertFalse(Route::has('purchase-categories.store'));
        $this->assertFalse(Route::has('purchase-categories.update'));
        $this->assertFalse(Route::has('purchase-categories.destroy'));
    }

    public function test_index_page_has_no_manage_categories_link_or_category_filters()
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('purchases.index'));

        $response->assertOk();
        $response->assertDontSee('Manage Categories');
        $response->assertDontSee('Main Category');
        $response->assertSee('Finished Good');
        $response->assertSee('Included Item');
    }

    public function test_storing_a_multi_item_purchase_creates_one_row_per_item_and_increases_stock()
    {
        $admin = $this->admin();
        $supplier = Supplier::create(['name' => 'Fresh Farms', 'status' => 'active']);

        $product = Product::create([
            'name' => 'Bottled Water',
            'supplier_id' => $supplier->id,
            'price' => 100,
            'quantity' => 10,
            'is_finished_good' => true,
            'is_unlimited_stock' => false,
            'status' => 'active',
        ]);

        $ingredient = Ingredient::create([
            'name' => 'Chicken Breast',
            'unit' => 'kg',
            'quantity' => 5,
            'supplier_id' => $supplier->id,
            'status' => 'active',
        ]);

        $payload = [
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->format('Y-m-d'),
            'payment_method' => 'bank',
            'supplier_invoice_no' => 'INV-001',
            'notes' => 'Weekly restock',
            'items' => [
                [
                    'item_type' => 'finished_good',
                    'product_id' => $product->id,
                    'quantity' => 12,
                    'unit_price' => 80,
                    'amount' => 960,
                ],
                [
                    'item_type' => 'included_item',
                    'ingredient_id' => $ingredient->id,
                    'quantity' => 8.5,
                    'unit_price' => 1200,
                    'amount' => 10200,
                ],
            ],
        ];

        $response = $this->actingAs($admin)->post(route('purchases.store'), $payload);

        $response->assertRedirect(route('purchases.index'));
        $response->assertSessionHasNoErrors();

        $this->assertEquals(2, Purchase::count());

        $productPurchase = Purchase::where('product_id', $product->id)->firstOrFail();
        $ingredientPurchase = Purchase::where('ingredient_id', $ingredient->id)->firstOrFail();

        // Both rows share one reference_no (same "add multiple items, then save" batch)
        // and the payment method chosen for the whole purchase.
        $this->assertNotNull($productPurchase->reference_no);
        $this->assertEquals($productPurchase->reference_no, $ingredientPurchase->reference_no);
        $this->assertEquals('bank', $productPurchase->payment_method);
        $this->assertEquals('bank', $ingredientPurchase->payment_method);
        $this->assertEquals('INV-001', $productPurchase->supplier_invoice_no);
        $this->assertEquals($supplier->id, $productPurchase->supplier_id);
        $this->assertEquals('Bottled Water', $productPurchase->item_name);
        $this->assertEquals('pcs', $productPurchase->unit);
        $this->assertEquals('kg', $ingredientPurchase->unit);
        $this->assertNull($productPurchase->purchase_category_id);

        // Stock increased by the purchased quantity.
        $this->assertEquals(22, $product->fresh()->quantity);
        $this->assertEquals(13.5, (float) $ingredient->fresh()->quantity);

        $this->assertEquals(1, StockMovement::where('product_id', $product->id)->where('source', 'purchase')->count());
        $this->assertEquals(1, IngredientStockMovement::where('ingredient_id', $ingredient->id)->where('source', 'purchase')->count());
    }

    public function test_supplier_and_payment_method_are_required_to_store_a_purchase()
    {
        $admin = $this->admin();
        $ingredient = Ingredient::create(['name' => 'Rice', 'unit' => 'kg', 'quantity' => 5, 'status' => 'active']);

        $response = $this->actingAs($admin)->post(route('purchases.store'), [
            'purchase_date' => now()->format('Y-m-d'),
            'items' => [[
                'item_type' => 'included_item',
                'ingredient_id' => $ingredient->id,
                'quantity' => 5,
                'amount' => 500,
            ]],
        ]);

        $response->assertSessionHasErrors(['supplier_id', 'payment_method']);
        $this->assertEquals(0, Purchase::count());
    }

    public function test_finished_good_purchase_rejects_fractional_quantity()
    {
        $admin = $this->admin();
        $supplier = Supplier::create(['name' => 'Fresh Farms', 'status' => 'active']);
        $product = Product::create([
            'name' => 'Bottled Water', 'supplier_id' => $supplier->id, 'price' => 100,
            'quantity' => 10, 'is_finished_good' => true, 'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->format('Y-m-d'),
            'payment_method' => 'cash',
            'items' => [[
                'item_type' => 'finished_good',
                'product_id' => $product->id,
                'quantity' => 2.5,
                'amount' => 100,
            ]],
        ]);

        $response->assertSessionHasErrors(['items.0.quantity']);
        $this->assertEquals(0, Purchase::count());
        $this->assertEquals(10, $product->fresh()->quantity);
    }

    public function test_purchase_rejects_item_not_supplied_by_the_selected_supplier()
    {
        $admin = $this->admin();
        $supplierA = Supplier::create(['name' => 'Supplier A', 'status' => 'active']);
        $supplierB = Supplier::create(['name' => 'Supplier B', 'status' => 'active']);
        $ingredient = Ingredient::create([
            'name' => 'Rice', 'unit' => 'kg', 'quantity' => 5, 'supplier_id' => $supplierA->id, 'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post(route('purchases.store'), [
            'supplier_id' => $supplierB->id,
            'purchase_date' => now()->format('Y-m-d'),
            'payment_method' => 'card',
            'items' => [[
                'item_type' => 'included_item',
                'ingredient_id' => $ingredient->id,
                'quantity' => 5,
                'amount' => 500,
            ]],
        ]);

        $response->assertSessionHasErrors(['items.0.ingredient_id']);
        $this->assertEquals(0, Purchase::count());
    }

    public function test_category_based_other_item_can_no_longer_be_created()
    {
        $admin = $this->admin();
        $supplier = Supplier::create(['name' => 'Fresh Farms', 'status' => 'active']);
        $main = PurchaseCategory::create(['name' => 'Vegetables', 'is_active' => true, 'sort_order' => 1]);
        $category = PurchaseCategory::create(['name' => 'Onion', 'parent_id' => $main->id, 'is_active' => true, 'sort_order' => 1]);

        $response = $this->actingAs($admin)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->format('Y-m-d'),
            'payment_method' => 'split',
            'items' => [[
                'item_type' => 'other',
                'purchase_category_id' => $category->id,
                'item_name' => 'Misc cleaning supplies',
                'quantity' => 3,
                'amount' => 450,
            ]],
        ]);

        $response->assertSessionHasErrors(['items.0.item_type']);
        $this->assertEquals(0, Purchase::count());
    }

    public function test_updating_quantity_and_payment_method_on_a_catalog_linked_purchase_adjusts_stock_by_the_delta()
    {
        $admin = $this->admin();
        $supplier = Supplier::create(['name' => 'Fresh Farms', 'status' => 'active']);
        $ingredient = Ingredient::create([
            'name' => 'Sugar', 'unit' => 'kg', 'quantity' => 5, 'supplier_id' => $supplier->id, 'status' => 'active',
        ]);

        $this->actingAs($admin)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->format('Y-m-d'),
            'payment_method' => 'cash',
            'items' => [[
                'item_type' => 'included_item',
                'ingredient_id' => $ingredient->id,
                'quantity' => 10,
                'amount' => 1000,
            ]],
        ])->assertSessionHasNoErrors();

        $this->assertEquals(15, (float) $ingredient->fresh()->quantity);

        $purchase = Purchase::where('ingredient_id', $ingredient->id)->firstOrFail();

        // Correct the quantity down from 10 to 6, and switch the payment method to Card.
        $this->actingAs($admin)->put(route('purchases.update', $purchase), [
            'supplier_id' => $supplier->id,
            'quantity' => 6,
            'amount' => 600,
            'purchase_date' => now()->format('Y-m-d'),
            'payment_method' => 'card',
        ])->assertSessionHasNoErrors();

        $this->assertEquals(11, (float) $ingredient->fresh()->quantity);
        $this->assertEquals('card', $purchase->fresh()->payment_method);
    }

    public function test_deleting_a_catalog_linked_purchase_reverses_the_stock_it_added()
    {
        $admin = $this->admin();
        $supplier = Supplier::create(['name' => 'Fresh Farms', 'status' => 'active']);
        $product = Product::create([
            'name' => 'Bottled Water', 'supplier_id' => $supplier->id, 'price' => 100,
            'quantity' => 0, 'is_finished_good' => true, 'status' => 'active',
        ]);

        $this->actingAs($admin)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->format('Y-m-d'),
            'payment_method' => 'cash',
            'items' => [[
                'item_type' => 'finished_good',
                'product_id' => $product->id,
                'quantity' => 20,
                'amount' => 2000,
            ]],
        ])->assertSessionHasNoErrors();

        $this->assertEquals(20, $product->fresh()->quantity);

        $purchase = Purchase::where('product_id', $product->id)->firstOrFail();
        $this->actingAs($admin)->delete(route('purchases.destroy', $purchase))->assertSessionHasNoErrors();

        $this->assertEquals(0, $product->fresh()->quantity);
        $this->assertEquals(0, Purchase::count());
    }

    public function test_legacy_category_based_purchase_can_still_be_edited_read_only_identity()
    {
        $admin = $this->admin();
        $main = PurchaseCategory::create(['name' => 'Vegetables', 'is_active' => true, 'sort_order' => 1]);
        $category = PurchaseCategory::create(['name' => 'Onion', 'parent_id' => $main->id, 'is_active' => true, 'sort_order' => 1]);

        // Simulate a row created under the old category-driven flow, before this change.
        $purchase = Purchase::create([
            'purchase_category_id' => $category->id,
            'item_name' => 'Big Onion - Grade A',
            'quantity' => 10,
            'unit' => 'kg',
            'unit_price' => 100,
            'amount' => 1000,
            'purchase_date' => now()->format('Y-m-d'),
            'payment_method' => 'cash',
        ]);

        $response = $this->actingAs($admin)->get(route('purchases.edit', $purchase));
        $response->assertOk();
        $response->assertSee('Big Onion - Grade A');
        $response->assertDontSee('Main Category');

        $update = $this->actingAs($admin)->put(route('purchases.update', $purchase), [
            'quantity' => 12,
            'amount' => 1200,
            'purchase_date' => now()->format('Y-m-d'),
            'payment_method' => 'bank',
        ]);
        $update->assertSessionHasNoErrors();

        $purchase->refresh();
        $this->assertEquals(12, (float) $purchase->quantity);
        $this->assertEquals('bank', $purchase->payment_method);
        $this->assertEquals($category->id, $purchase->purchase_category_id);
    }
}
