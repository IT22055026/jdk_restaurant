<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Order;

class TokenNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_can_be_created_without_a_token()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('pos.order.create'), []);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('token_number', null);
    }

    public function test_duplicate_token_number_same_day_is_rejected()
    {
        $user = User::factory()->create();

        $orderA = Order::create(['order_number' => 'ORD-000001', 'user_id' => $user->id]);
        $orderB = Order::create(['order_number' => 'ORD-000002', 'user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson(route('pos.order.token_number', $orderA), ['token_number' => 5])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('token_number', 5);

        $this->actingAs($user)
            ->postJson(route('pos.order.token_number', $orderB), ['token_number' => 5])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_completing_bill_without_token_is_rejected()
    {
        $user = User::factory()->create();
        $order = Order::create(['order_number' => 'ORD-000003', 'user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson(route('pos.order.pay', $order), [
            'payment_method' => 'cash',
            'amount_paid' => 100,
        ]);

        $response->assertStatus(422);
    }

    public function test_completing_bill_with_token_succeeds()
    {
        $user = User::factory()->create();
        $order = Order::create(['order_number' => 'ORD-000004', 'user_id' => $user->id]);

        $this->actingAs($user)->postJson(route('pos.order.token_number', $order), ['token_number' => 7]);

        $response = $this->actingAs($user)->postJson(route('pos.order.pay', $order), [
            'payment_method' => 'cash',
            'amount_paid' => 0,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }

    public function test_pickme_and_uber_orders_do_not_require_a_token()
    {
        $user = User::factory()->create();

        foreach (['pickme', 'uber'] as $method) {
            $order = Order::create(['order_number' => 'ORD-NOTOKEN-' . strtoupper($method), 'user_id' => $user->id]);

            $response = $this->actingAs($user)->postJson(route('pos.order.pay', $order), [
                'payment_method' => $method,
                'amount_paid' => 0,
            ]);

            $response->assertStatus(200);
            $response->assertJsonPath('success', true);
            $this->assertNull($order->fresh()->token_number);
        }
    }

    public function test_cash_order_still_requires_a_token_even_if_pickme_uber_are_exempt()
    {
        $user = User::factory()->create();
        $order = Order::create(['order_number' => 'ORD-000005', 'user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson(route('pos.order.pay', $order), [
            'payment_method' => 'cash',
            'amount_paid' => 0,
        ]);

        $response->assertStatus(422);
    }
}
