<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Order;
use Carbon\Carbon;

class OrderNumberTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_the_first_order_of_a_business_day_gets_sequence_0001()
    {
        Carbon::setTestNow('2026-07-30 10:00:00');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('pos.order.create'), []);

        $response->assertStatus(200);
        $response->assertJsonPath('order_number', 'ORD-20260730-0001');

        $order = Order::findOrFail($response->json('order_id'));
        $this->assertEquals('2026-07-30', $order->business_date->toDateString());
        $this->assertEquals(1, $order->daily_sequence);
    }

    public function test_subsequent_orders_the_same_business_day_increment_the_sequence()
    {
        Carbon::setTestNow('2026-07-30 10:00:00');
        $user = User::factory()->create();

        $first  = $this->actingAs($user)->postJson(route('pos.order.create'), []);
        $second = $this->actingAs($user)->postJson(route('pos.order.create'), []);
        $third  = $this->actingAs($user)->postJson(route('pos.order.create'), []);

        $first->assertJsonPath('order_number', 'ORD-20260730-0001');
        $second->assertJsonPath('order_number', 'ORD-20260730-0002');
        $third->assertJsonPath('order_number', 'ORD-20260730-0003');
    }

    public function test_an_order_placed_before_6am_numbers_under_the_previous_business_day()
    {
        Carbon::setTestNow('2026-07-30 01:30:00');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('pos.order.create'), []);

        // 1:30am on the 30th is still business day the 29th (see BusinessDay).
        $response->assertJsonPath('order_number', 'ORD-20260729-0001');
    }

    public function test_sequence_resets_to_0001_on_a_new_business_day()
    {
        Carbon::setTestNow('2026-07-29 10:00:00');
        $user = User::factory()->create();
        $this->actingAs($user)->postJson(route('pos.order.create'), []);
        $this->actingAs($user)->postJson(route('pos.order.create'), [])
            ->assertJsonPath('order_number', 'ORD-20260729-0002');

        Carbon::setTestNow('2026-07-30 10:00:00');
        $response = $this->actingAs($user)->postJson(route('pos.order.create'), []);

        $response->assertJsonPath('order_number', 'ORD-20260730-0001');
    }

    public function test_order_number_stays_unique_even_if_a_sequence_slot_is_already_taken()
    {
        Carbon::setTestNow('2026-07-30 10:00:00');
        $user = User::factory()->create();

        // Simulate another request having already claimed sequence 1 for
        // today via some other path, without going through the normal flow.
        Order::create([
            'order_number' => 'ORD-20260730-0001',
            'business_date' => '2026-07-30',
            'daily_sequence' => 1,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson(route('pos.order.create'), []);

        $response->assertStatus(200);
        $response->assertJsonPath('order_number', 'ORD-20260730-0002');
        $this->assertDatabaseCount('orders', 2);
    }
}
