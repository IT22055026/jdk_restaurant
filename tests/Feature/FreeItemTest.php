<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Ingredient;

class FreeItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_adding_a_free_item_creates_a_zero_subtotal_line_at_full_discount()
    {
        $user = User::factory()->create();
        $product = Product::create([
            'name' => 'Sauce Cup',
            'price' => 50,
            'selling_price' => 50,
            'quantity' => 100,
            'is_unlimited_stock' => false,
            'is_finished_good' => true,
            'status' => 'active',
        ]);
        $order = Order::create(['order_number' => 'ORD-FREE-000001', 'user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson(route('pos.item.add', $order), [
            'product_id' => $product->id,
            'quantity' => 1,
            'is_free' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $item = $order->items()->where('product_id', $product->id)->firstOrFail();
        $this->assertEquals(100, $item->discount_percent);
        $this->assertEquals(0, $item->subtotal);
        $this->assertEquals(50, $item->unit_price);
    }

    public function test_free_item_does_not_merge_with_a_normally_priced_line_of_the_same_product()
    {
        $user = User::factory()->create();
        $product = Product::create([
            'name' => 'Sauce Cup',
            'price' => 50,
            'selling_price' => 50,
            'quantity' => 100,
            'is_unlimited_stock' => false,
            'is_finished_good' => true,
            'status' => 'active',
        ]);
        $order = Order::create(['order_number' => 'ORD-FREE-000002', 'user_id' => $user->id]);

        $this->actingAs($user)->postJson(route('pos.item.add', $order), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $this->actingAs($user)->postJson(route('pos.item.add', $order), [
            'product_id' => $product->id,
            'quantity' => 1,
            'is_free' => true,
        ]);

        $items = $order->items()->where('product_id', $product->id)->get();
        $this->assertCount(2, $items);

        $paidLine = $items->firstWhere('discount_percent', 0);
        $freeLine = $items->firstWhere('discount_percent', 100);

        $this->assertEquals(2, $paidLine->quantity);
        $this->assertEquals(100, $paidLine->subtotal);
        $this->assertEquals(1, $freeLine->quantity);
        $this->assertEquals(0, $freeLine->subtotal);
    }

    // Same guarantee as above, but with the free line added FIRST — the
    // merge lookup used to only filter by discount_percent when adding a
    // free item, so a paid re-add done afterward would match (and silently
    // fold its quantity into, zeroing out) the existing free line.
    public function test_a_paid_add_does_not_merge_into_an_existing_free_line_of_the_same_product()
    {
        $user = User::factory()->create();
        $product = Product::create([
            'name' => 'Sauce Cup',
            'price' => 50,
            'selling_price' => 50,
            'quantity' => 100,
            'is_unlimited_stock' => false,
            'is_finished_good' => true,
            'status' => 'active',
        ]);
        $order = Order::create(['order_number' => 'ORD-FREE-000008', 'user_id' => $user->id]);

        $this->actingAs($user)->postJson(route('pos.item.add', $order), [
            'product_id' => $product->id,
            'quantity' => 1,
            'is_free' => true,
        ]);
        $this->actingAs($user)->postJson(route('pos.item.add', $order), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $items = $order->items()->where('product_id', $product->id)->get();
        $this->assertCount(2, $items);

        $paidLine = $items->firstWhere('discount_percent', 0);
        $freeLine = $items->firstWhere('discount_percent', 100);

        $this->assertEquals(2, $paidLine->quantity);
        $this->assertEquals(100, $paidLine->subtotal);
        $this->assertEquals(1, $freeLine->quantity);
        $this->assertEquals(0, $freeLine->subtotal);
    }

    public function test_re_adding_a_free_item_merges_into_the_free_line_and_keeps_subtotal_zero()
    {
        $user = User::factory()->create();
        $product = Product::create([
            'name' => 'Sauce Cup',
            'price' => 50,
            'selling_price' => 50,
            'quantity' => 100,
            'is_unlimited_stock' => false,
            'is_finished_good' => true,
            'status' => 'active',
        ]);
        $order = Order::create(['order_number' => 'ORD-FREE-000003', 'user_id' => $user->id]);

        $this->actingAs($user)->postJson(route('pos.item.add', $order), [
            'product_id' => $product->id,
            'quantity' => 1,
            'is_free' => true,
        ]);
        $this->actingAs($user)->postJson(route('pos.item.add', $order), [
            'product_id' => $product->id,
            'quantity' => 2,
            'is_free' => true,
        ]);

        $items = $order->items()->where('product_id', $product->id)->get();
        $this->assertCount(1, $items);
        $this->assertEquals(3, $items->first()->quantity);
        $this->assertEquals(0, $items->first()->subtotal);
    }

    public function test_free_item_still_deducts_stock_and_does_not_add_to_order_total_when_paid()
    {
        $user = User::factory()->create();
        $product = Product::create([
            'name' => 'Sauce Cup',
            'price' => 50,
            'selling_price' => 50,
            'quantity' => 100,
            'is_unlimited_stock' => false,
            'is_finished_good' => true,
            'status' => 'active',
        ]);
        $paidProduct = Product::create([
            'name' => 'Burger',
            'price' => 500,
            'selling_price' => 500,
            'quantity' => 50,
            'is_unlimited_stock' => false,
            'is_finished_good' => true,
            'status' => 'active',
        ]);
        $order = Order::create(['order_number' => 'ORD-FREE-000004', 'user_id' => $user->id]);

        $this->actingAs($user)->postJson(route('pos.item.add', $order), [
            'product_id' => $paidProduct->id,
            'quantity' => 1,
        ]);
        $this->actingAs($user)->postJson(route('pos.item.add', $order), [
            'product_id' => $product->id,
            'quantity' => 2,
            'is_free' => true,
        ]);
        $this->actingAs($user)->postJson(route('pos.order.token_number', $order), ['token_number' => 9]);

        $response = $this->actingAs($user)->postJson(route('pos.order.pay', $order), [
            'payment_method' => 'cash',
            'amount_paid' => 500,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('total', 500);

        $this->assertEquals(98, $product->fresh()->quantity); // 100 - 2, deducted even though free
        $this->assertEquals(49, $paidProduct->fresh()->quantity); // 50 - 1
    }

    // The Free Item picker isn't limited to sellable menu Products — a
    // cashier can also give away a raw/included item directly (e.g. an
    // extra sauce pot that isn't itself on the menu).
    public function test_adding_a_free_ingredient_creates_a_zero_subtotal_line_at_full_discount()
    {
        $user = User::factory()->create();
        $ingredient = Ingredient::create([
            'name' => 'Chili Sauce', 'unit' => 'ml', 'quantity' => 5000,
            'cost_per_unit' => 0.50, 'status' => 'active',
        ]);
        $order = Order::create(['order_number' => 'ORD-FREE-000005', 'user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson(route('pos.item.add', $order), [
            'ingredient_id' => $ingredient->id,
            'quantity' => 3,
            'is_free' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $item = $order->items()->where('ingredient_id', $ingredient->id)->firstOrFail();
        $this->assertNull($item->product_id);
        $this->assertEquals('Chili Sauce', $item->product_name);
        $this->assertEquals(100, $item->discount_percent);
        $this->assertEquals(0, $item->subtotal);
        $this->assertEquals(3, $item->quantity);
    }

    public function test_free_ingredient_deducts_ingredient_stock_when_paid()
    {
        $user = User::factory()->create();
        $ingredient = Ingredient::create([
            'name' => 'Chili Sauce', 'unit' => 'ml', 'quantity' => 5000,
            'cost_per_unit' => 0.50, 'status' => 'active',
        ]);
        $order = Order::create(['order_number' => 'ORD-FREE-000006', 'user_id' => $user->id]);

        $this->actingAs($user)->postJson(route('pos.item.add', $order), [
            'ingredient_id' => $ingredient->id,
            'quantity' => 200,
            'is_free' => true,
        ]);
        $this->actingAs($user)->postJson(route('pos.order.token_number', $order), ['token_number' => 15]);

        $response = $this->actingAs($user)->postJson(route('pos.order.pay', $order), [
            'payment_method' => 'cash',
            'amount_paid' => 0,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('total', 0);
        $this->assertEquals(4800, (float) $ingredient->fresh()->quantity); // 5000 - 200
    }

    public function test_free_item_requires_either_a_product_or_an_ingredient()
    {
        $user = User::factory()->create();
        $order = Order::create(['order_number' => 'ORD-FREE-000007', 'user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson(route('pos.item.add', $order), [
            'quantity' => 1,
            'is_free' => true,
        ]);

        $response->assertStatus(422);
    }

    public function test_get_ingredients_endpoint_lists_only_active_ingredients()
    {
        $user = User::factory()->create();
        Ingredient::create(['name' => 'Chili Sauce', 'unit' => 'ml', 'quantity' => 100, 'status' => 'active']);
        Ingredient::create(['name' => 'Old Stock Item', 'unit' => 'g', 'quantity' => 50, 'status' => 'inactive']);

        $response = $this->actingAs($user)->getJson(route('pos.ingredients'));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['name' => 'Chili Sauce', 'unit' => 'ml']);
    }

    // "Include Items" section: an included item can be sold directly (e.g.
    // "extra mayonnaise") at its own selling_price, not just given away free.
    public function test_a_customer_can_buy_an_included_item_directly_at_its_selling_price()
    {
        $user = User::factory()->create();
        $ingredient = Ingredient::create([
            'name' => 'Mayonnaise', 'unit' => 'g', 'quantity' => 2000,
            'cost_per_unit' => 0.20, 'selling_price' => 0.50, 'status' => 'active',
        ]);
        $order = Order::create(['order_number' => 'ORD-FREE-000009', 'user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson(route('pos.item.add', $order), [
            'ingredient_id' => $ingredient->id,
            'quantity' => 100, // 100g
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $item = $order->items()->where('ingredient_id', $ingredient->id)->firstOrFail();
        $this->assertEquals(0, $item->discount_percent);
        $this->assertEquals(0.50, (float) $item->unit_price);
        $this->assertEquals(50, (float) $item->subtotal); // 100g * Rs 0.50

        $this->actingAs($user)->postJson(route('pos.order.token_number', $order), ['token_number' => 16]);
        $payResponse = $this->actingAs($user)->postJson(route('pos.order.pay', $order), [
            'payment_method' => 'cash',
            'amount_paid' => 50,
        ]);

        $payResponse->assertStatus(200);
        $payResponse->assertJsonPath('total', 50);
        $this->assertEquals(1900, (float) $ingredient->fresh()->quantity); // 2000 - 100
    }

    public function test_buying_an_included_item_without_a_selling_price_set_is_rejected()
    {
        $user = User::factory()->create();
        $ingredient = Ingredient::create([
            'name' => 'Extra Napkins', 'unit' => 'pcs', 'quantity' => 500, 'status' => 'active',
        ]);
        $order = Order::create(['order_number' => 'ORD-FREE-000010', 'user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson(route('pos.item.add', $order), [
            'ingredient_id' => $ingredient->id,
            'quantity' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $this->assertDatabaseCount('order_items', 0);
    }

    public function test_a_paid_included_item_add_does_not_merge_into_an_existing_free_line()
    {
        $user = User::factory()->create();
        $ingredient = Ingredient::create([
            'name' => 'Mayonnaise', 'unit' => 'g', 'quantity' => 2000,
            'cost_per_unit' => 0.20, 'selling_price' => 0.50, 'status' => 'active',
        ]);
        $order = Order::create(['order_number' => 'ORD-FREE-000011', 'user_id' => $user->id]);

        $this->actingAs($user)->postJson(route('pos.item.add', $order), [
            'ingredient_id' => $ingredient->id,
            'quantity' => 20,
            'is_free' => true,
        ]);
        $this->actingAs($user)->postJson(route('pos.item.add', $order), [
            'ingredient_id' => $ingredient->id,
            'quantity' => 100,
        ]);

        $items = $order->items()->where('ingredient_id', $ingredient->id)->get();
        $this->assertCount(2, $items);

        $paidLine = $items->firstWhere('discount_percent', 0);
        $freeLine = $items->firstWhere('discount_percent', 100);
        $this->assertEquals(100, $paidLine->quantity);
        $this->assertEquals(50, (float) $paidLine->subtotal);
        $this->assertEquals(20, $freeLine->quantity);
        $this->assertEquals(0, (float) $freeLine->subtotal);
    }
}
