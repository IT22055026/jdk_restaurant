<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SplitPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'Cashier'],
            ['slug' => 'cashier']
        );

        $this->cashier = User::factory()->create([
            'role_id' => $role->id,
        ]);
    }

    public function test_can_pay_order_with_split_payment_and_retrieve_details()
    {
        $product = Product::create([
            'name' => 'Full BBQ Chicken',
            'price' => 3900,
            'selling_price' => 3900,
            'quantity' => 10,
            'is_unlimited_stock' => false,
            'is_finished_good' => true,
            'status' => 'active',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-20260825-0001',
            'user_id' => $this->cashier->id,
            'token_number' => 8,
            'status' => 'pending',
            'subtotal' => 3900,
            'total' => 3900,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 3900,
            'subtotal' => 3900,
        ]);

        $response = $this->actingAs($this->cashier)->postJson(route('pos.order.pay', $order->id), [
            'payment_method' => 'mixed',
            'amount_paid' => 3900,
            'split_method1' => 'card',
            'split_amount1' => 3000,
            'split_method2' => 'cash',
            'split_amount2' => 900,
            'order_type' => 'dine_in',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('payment_method', 'mixed');
        $response->assertJsonPath('split_method1', 'card');
        $response->assertJsonPath('split_amount1', 3000);
        $response->assertJsonPath('split_method2', 'cash');
        $response->assertJsonPath('split_amount2', 900);

        $order->refresh();
        $this->assertEquals('completed', $order->status);
        $this->assertEquals('mixed', $order->payment_method);
        $this->assertEquals('card', $order->split_method1);
        $this->assertEquals(3000, (float) $order->split_amount1);
        $this->assertEquals('cash', $order->split_method2);
        $this->assertEquals(900, (float) $order->split_amount2);

        // Test reprint receipt endpoint
        $reprintResponse = $this->actingAs($this->cashier)->getJson(route('pos.receipt.reprint', $order->id));
        $reprintResponse->assertStatus(200);
        $reprintResponse->assertJsonPath('success', true);
        $reprintResponse->assertJsonPath('payment_method', 'mixed');
        $reprintResponse->assertJsonPath('split_method1', 'card');
        $reprintResponse->assertJsonPath('split_amount1', 3000);
        $reprintResponse->assertJsonPath('split_method2', 'cash');
        $reprintResponse->assertJsonPath('split_amount2', 900);
    }

    public function test_uber_and_pickme_orders_reprint_token()
    {
        $product = Product::create([
            'name' => 'Burger',
            'price' => 1200,
            'selling_price' => 1200,
            'quantity' => 10,
            'is_unlimited_stock' => false,
            'is_finished_good' => true,
            'status' => 'active',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-20260825-0002',
            'user_id' => $this->cashier->id,
            'status' => 'pending',
            'subtotal' => 1200,
            'total' => 1200,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 1200,
            'subtotal' => 1200,
        ]);

        $response = $this->actingAs($this->cashier)->postJson(route('pos.order.pay', $order->id), [
            'payment_method' => 'uber',
            'amount_paid' => 1200,
            'order_type' => 'dine_in',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('payment_method', 'uber');

        // Test reprint token endpoint
        $reprintToken = $this->actingAs($this->cashier)->getJson(route('pos.token.reprint', $order->id));
        $reprintToken->assertStatus(200);
        $reprintToken->assertJsonPath('payment_method', 'uber');
    }
}
