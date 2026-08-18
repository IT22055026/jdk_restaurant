<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_discarding_a_bill_requires_a_reason()
    {
        $user = User::factory()->create();
        $order = Order::create(['order_number' => 'ORD-000001', 'user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson(route('pos.order.cancel', $order), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('reason');
    }

    public function test_discarding_a_completed_bill_is_rejected()
    {
        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-000002',
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)->postJson(route('pos.order.cancel', $order), [
            'reason' => 'Customer changed mind',
        ]);

        $response->assertStatus(422);
        $order->refresh();
        $this->assertEquals('completed', $order->status);
    }

    public function test_discarding_an_open_bill_records_audit_fields()
    {
        $user = User::factory()->create();
        $order = Order::create(['order_number' => 'ORD-000003', 'user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson(route('pos.order.cancel', $order), [
            'reason' => 'Ordered by mistake',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $order->refresh();
        $this->assertEquals('cancelled', $order->status);
        $this->assertEquals('Ordered by mistake', $order->discard_reason);
        $this->assertEquals($user->id, $order->discarded_by);
        $this->assertNotNull($order->discarded_at);
    }

    public function test_sales_report_lists_every_bill_regardless_of_status()
    {
        $user = User::factory()->create();
        Order::create(['order_number' => 'ORD-000004', 'user_id' => $user->id, 'status' => 'pending']);
        Order::create(['order_number' => 'ORD-000005', 'user_id' => $user->id, 'status' => 'hold']);
        Order::create(['order_number' => 'ORD-000006', 'user_id' => $user->id, 'status' => 'completed']);
        $cancelled = Order::create(['order_number' => 'ORD-000007', 'user_id' => $user->id]);
        $this->actingAs($user)->postJson(route('pos.order.cancel', $cancelled), ['reason' => 'Test reason']);

        $response = $this->actingAs($user)->getJson(route('api.sales-report'));

        $response->assertStatus(200);
        $response->assertJsonCount(4);
        $response->assertJsonFragment(['order_number' => 'ORD-000004', 'status' => 'pending']);
        $response->assertJsonFragment(['order_number' => 'ORD-000005', 'status' => 'hold']);
        $response->assertJsonFragment(['order_number' => 'ORD-000006', 'status' => 'completed']);
        $response->assertJsonFragment(['order_number' => 'ORD-000007', 'status' => 'cancelled', 'discard_reason' => 'Test reason']);
    }

    public function test_sales_report_can_be_filtered_by_status()
    {
        $user = User::factory()->create();
        Order::create(['order_number' => 'ORD-000008', 'user_id' => $user->id, 'status' => 'completed']);
        Order::create(['order_number' => 'ORD-000009', 'user_id' => $user->id, 'status' => 'hold']);

        $response = $this->actingAs($user)->getJson(route('api.sales-report', ['status' => 'completed']));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['order_number' => 'ORD-000008']);
    }

    public function test_sales_report_can_be_filtered_by_date_range()
    {
        $user = User::factory()->create();
        $inRange = Order::create(['order_number' => 'ORD-000010', 'user_id' => $user->id, 'status' => 'completed']);
        $inRange->forceFill(['created_at' => '2026-06-15 10:00:00'])->save();

        $outOfRange = Order::create(['order_number' => 'ORD-000011', 'user_id' => $user->id, 'status' => 'completed']);
        $outOfRange->forceFill(['created_at' => '2026-01-01 10:00:00'])->save();

        $response = $this->actingAs($user)->getJson(route('api.sales-report', [
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-30',
        ]));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['order_number' => 'ORD-000010']);
    }

    // Shifts run overnight, so the trading day runs 06:00-05:59 rather than
    // midnight-to-midnight — a sale at 1am still belongs to the PREVIOUS
    // business day, not the new calendar date.
    public function test_sales_report_date_filter_uses_business_day_not_calendar_day()
    {
        $user = User::factory()->create();

        // Placed at 1am on the 16th — that's still business day the 15th.
        $lateNight = Order::create(['order_number' => 'ORD-BIZDAY-01', 'user_id' => $user->id, 'status' => 'completed']);
        $lateNight->forceFill(['created_at' => '2026-06-16 01:00:00'])->save();

        // Placed at 8am on the 16th — a genuine business day 16th sale.
        $morning = Order::create(['order_number' => 'ORD-BIZDAY-02', 'user_id' => $user->id, 'status' => 'completed']);
        $morning->forceFill(['created_at' => '2026-06-16 08:00:00'])->save();

        $forThe15th = $this->actingAs($user)->getJson(route('api.sales-report', [
            'date_from' => '2026-06-15', 'date_to' => '2026-06-15',
        ]));
        $forThe15th->assertJsonCount(1);
        $forThe15th->assertJsonFragment(['order_number' => 'ORD-BIZDAY-01']);

        $forThe16th = $this->actingAs($user)->getJson(route('api.sales-report', [
            'date_from' => '2026-06-16', 'date_to' => '2026-06-16',
        ]));
        $forThe16th->assertJsonCount(1);
        $forThe16th->assertJsonFragment(['order_number' => 'ORD-BIZDAY-02']);
    }

    public function test_only_completed_bills_can_be_revoked()
    {
        $user = User::factory()->create();
        $order = Order::create(['order_number' => 'ORD-000012', 'user_id' => $user->id, 'status' => 'pending']);

        $response = $this->actingAs($user)->postJson(route('pos.order.revoke', $order), ['reason' => 'Test']);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_revoking_a_bill_requires_a_reason()
    {
        $user = User::factory()->create();
        $order = Order::create(['order_number' => 'ORD-000013', 'user_id' => $user->id, 'status' => 'completed']);

        $response = $this->actingAs($user)->postJson(route('pos.order.revoke', $order), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('reason');
    }

    public function test_revoking_a_bill_restores_stock_and_records_audit_fields()
    {
        $user = User::factory()->create();

        $product = Product::create([
            'name' => 'Pepsi',
            'price' => 200,
            'selling_price' => 200,
            'quantity' => 10,
            'is_unlimited_stock' => false,
            'is_finished_good' => true,
            'status' => 'active',
        ]);

        $order = Order::create(['order_number' => 'ORD-000014', 'user_id' => $user->id]);
        $this->actingAs($user)->postJson(route('pos.item.add', $order), ['product_id' => $product->id, 'quantity' => 3]);
        $this->actingAs($user)->postJson(route('pos.order.token_number', $order), ['token_number' => 1]);

        $payResponse = $this->actingAs($user)->postJson(route('pos.order.pay', $order), [
            'payment_method' => 'cash',
            'amount_paid' => 600,
        ]);
        $payResponse->assertStatus(200);
        $this->assertEquals(7, $product->fresh()->quantity); // 10 - 3

        $revokeResponse = $this->actingAs($user)->postJson(route('pos.order.revoke', $order), [
            'reason' => 'Customer returned the order',
        ]);

        $revokeResponse->assertStatus(200);
        $revokeResponse->assertJsonPath('success', true);

        $this->assertEquals(10, $product->fresh()->quantity); // restored back to original

        $order->refresh();
        $this->assertEquals('cancelled', $order->status);
        $this->assertEquals('Customer returned the order', $order->discard_reason);
        $this->assertEquals($user->id, $order->discarded_by);
        $this->assertNotNull($order->discarded_at);
        $this->assertEquals('cash', $order->payment_method); // kept, to distinguish "revoked" from "discarded"

        // Revoked money no longer counts toward the sales report's revenue.
        $response = $this->actingAs($user)->getJson(route('api.sales-report'));
        $response->assertJsonFragment(['order_number' => 'ORD-000014', 'status' => 'cancelled']);
    }

    public function test_pickme_and_uber_are_accepted_payment_methods()
    {
        $user = User::factory()->create();

        foreach (['pickme', 'uber'] as $method) {
            $order = Order::create(['order_number' => 'ORD-PAY-' . strtoupper($method), 'user_id' => $user->id]);
            $this->actingAs($user)->postJson(route('pos.order.token_number', $order), ['token_number' => rand(100, 999)]);

            $response = $this->actingAs($user)->postJson(route('pos.order.pay', $order), [
                'payment_method' => $method,
                'amount_paid' => 0,
            ]);

            $response->assertStatus(200);
            $response->assertJsonPath('success', true);
            $this->assertEquals($method, $order->fresh()->payment_method);
        }
    }

    public function test_holding_an_open_bill_sets_status_to_hold()
    {
        $user = User::factory()->create();
        $order = Order::create(['order_number' => 'ORD-000016', 'user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson(route('pos.order.hold', $order));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $order->refresh();
        $this->assertEquals('hold', $order->status);
    }

    public function test_holding_a_completed_bill_is_rejected()
    {
        $user = User::factory()->create();
        $order = Order::create(['order_number' => 'ORD-000017', 'user_id' => $user->id, 'status' => 'completed']);

        $response = $this->actingAs($user)->postJson(route('pos.order.hold', $order));

        $response->assertStatus(422);
        $order->refresh();
        $this->assertEquals('completed', $order->status);
    }

    public function test_holding_a_cancelled_bill_is_rejected()
    {
        $user = User::factory()->create();
        $order = Order::create(['order_number' => 'ORD-000018', 'user_id' => $user->id]);
        $this->actingAs($user)->postJson(route('pos.order.cancel', $order), ['reason' => 'Test reason']);

        $response = $this->actingAs($user)->postJson(route('pos.order.hold', $order));

        $response->assertStatus(422);
        $order->refresh();
        $this->assertEquals('cancelled', $order->status);
    }

    public function test_a_held_bill_keeps_its_items_and_can_later_be_resumed_and_paid()
    {
        $user = User::factory()->create();

        $product = Product::create([
            'name' => 'Fries',
            'price' => 500,
            'selling_price' => 500,
            'quantity' => 10,
            'is_unlimited_stock' => false,
            'is_finished_good' => true,
            'status' => 'active',
        ]);

        $order = Order::create(['order_number' => 'ORD-000019', 'user_id' => $user->id]);
        $this->actingAs($user)->postJson(route('pos.item.add', $order), ['product_id' => $product->id, 'quantity' => 2]);
        $this->actingAs($user)->postJson(route('pos.order.token_number', $order), ['token_number' => 5]);

        $holdResponse = $this->actingAs($user)->postJson(route('pos.order.hold', $order));
        $holdResponse->assertStatus(200);
        $order->refresh();
        $this->assertEquals('hold', $order->status);

        // Shows up as an open/pending bill on the Sales Report.
        $reportResponse = $this->actingAs($user)->getJson(route('api.sales-report'));
        $reportResponse->assertJsonFragment(['order_number' => 'ORD-000019', 'status' => 'hold']);

        // Resuming it (GET pos.order.show, as the POS screen's "Resume & Pay" does) still
        // works and the token/items survive the hold.
        $showResponse = $this->actingAs($user)->getJson(route('pos.order.show', $order));
        $showResponse->assertStatus(200);
        $showResponse->assertJsonPath('status', 'hold');
        $showResponse->assertJsonPath('token_number', 5);
        $showResponse->assertJsonCount(1, 'items');

        $payResponse = $this->actingAs($user)->postJson(route('pos.order.pay', $order), [
            'payment_method' => 'cash',
            'amount_paid' => 1000,
        ]);

        $payResponse->assertStatus(200);
        $order->refresh();
        $this->assertEquals('completed', $order->status);
    }

    public function test_revoking_a_completed_bill_is_rejected_a_second_time()
    {
        $user = User::factory()->create();
        $order = Order::create(['order_number' => 'ORD-000015', 'user_id' => $user->id]);
        $this->actingAs($user)->postJson(route('pos.order.token_number', $order), ['token_number' => 2]);
        $this->actingAs($user)->postJson(route('pos.order.pay', $order), [
            'payment_method' => 'cash',
            'amount_paid' => 0,
        ]);

        $this->actingAs($user)->postJson(route('pos.order.revoke', $order), ['reason' => 'First revoke']);

        $response = $this->actingAs($user)->postJson(route('pos.order.revoke', $order), ['reason' => 'Second attempt']);

        $response->assertStatus(422);
    }
}
