<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Order;
use Carbon\Carbon;

class TokenNumberTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

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

    // Physical tokens are handed to a customer and returned once they leave,
    // so the same number must be reassignable to a new customer the same day
    // once the order that held it is no longer open.
    public function test_token_number_can_be_reused_once_original_order_is_completed()
    {
        $user = User::factory()->create();

        $orderA = Order::create(['order_number' => 'ORD-000006', 'user_id' => $user->id]);
        $orderB = Order::create(['order_number' => 'ORD-000007', 'user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson(route('pos.order.token_number', $orderA), ['token_number' => 9])
            ->assertStatus(200);

        $this->actingAs($user)->postJson(route('pos.order.pay', $orderA), [
            'payment_method' => 'cash',
            'amount_paid' => 0,
        ])->assertStatus(200);

        $this->actingAs($user)
            ->postJson(route('pos.order.token_number', $orderB), ['token_number' => 9])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('token_number', 9);
    }

    public function test_token_number_can_be_reused_once_original_order_is_cancelled()
    {
        $user = User::factory()->create();

        $orderA = Order::create(['order_number' => 'ORD-000008', 'user_id' => $user->id]);
        $orderB = Order::create(['order_number' => 'ORD-000009', 'user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson(route('pos.order.token_number', $orderA), ['token_number' => 3])
            ->assertStatus(200);

        $this->actingAs($user)->postJson(route('pos.order.cancel', $orderA), [
            'reason' => 'Customer left without ordering',
        ])->assertStatus(200);

        $this->actingAs($user)
            ->postJson(route('pos.order.token_number', $orderB), ['token_number' => 3])
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_token_number_still_blocked_while_original_order_is_open()
    {
        $user = User::factory()->create();

        $orderA = Order::create(['order_number' => 'ORD-000010', 'user_id' => $user->id]);
        $orderB = Order::create(['order_number' => 'ORD-000011', 'user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson(route('pos.order.token_number', $orderA), ['token_number' => 4])
            ->assertStatus(200);

        // orderA is still pending — token 4 must stay reserved until it's
        // completed or cancelled.
        $this->actingAs($user)
            ->postJson(route('pos.order.token_number', $orderB), ['token_number' => 4])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    // Shifts run overnight (e.g. 8pm to 3pm the next day), so the trading
    // day runs 06:00-05:59 rather than resetting at midnight — a token
    // issued at 1am is still part of the PREVIOUS business day.
    public function test_a_token_issued_before_6am_is_dated_the_previous_business_day()
    {
        Carbon::setTestNow('2026-07-30 01:30:00');

        $user = User::factory()->create();
        $order = Order::create(['order_number' => 'ORD-000012', 'user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson(route('pos.order.token_number', $order), ['token_number' => 11]);

        $response->assertStatus(200);
        $response->assertJsonPath('token_date', '2026-07-29');
        $this->assertEquals('2026-07-29', $order->fresh()->token_date->toDateString());
    }

    public function test_a_token_issued_after_6am_is_dated_that_calendar_day()
    {
        Carbon::setTestNow('2026-07-30 09:00:00');

        $user = User::factory()->create();
        $order = Order::create(['order_number' => 'ORD-000013', 'user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson(route('pos.order.token_number', $order), ['token_number' => 12]);

        $response->assertStatus(200);
        $response->assertJsonPath('token_date', '2026-07-30');
    }

    public function test_tokens_issued_just_before_and_just_after_the_6am_cutoff_are_treated_as_different_days()
    {
        Carbon::setTestNow('2026-07-30 05:59:59');
        $user = User::factory()->create();
        $lateNightOrder = Order::create(['order_number' => 'ORD-000014', 'user_id' => $user->id]);
        $this->actingAs($user)
            ->postJson(route('pos.order.token_number', $lateNightOrder), ['token_number' => 20])
            ->assertStatus(200);

        // Same physical token number, now on the NEW business day — should
        // be free to reuse even though the previous order is still open,
        // because it belongs to yesterday's trading window.
        Carbon::setTestNow('2026-07-30 06:00:01');
        $morningOrder = Order::create(['order_number' => 'ORD-000015', 'user_id' => $user->id]);
        $this->actingAs($user)
            ->postJson(route('pos.order.token_number', $morningOrder), ['token_number' => 20])
            ->assertStatus(200)
            ->assertJsonPath('token_date', '2026-07-30');

        $this->assertEquals('2026-07-29', $lateNightOrder->fresh()->token_date->toDateString());
    }
}
