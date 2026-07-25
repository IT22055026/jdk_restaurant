<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;

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
}
