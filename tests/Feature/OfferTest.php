<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Ingredient;
use App\Models\Offer;
use App\Models\Order;
use App\Models\OrderItem;

class OfferTest extends TestCase
{
    use RefreshDatabase;

    public function test_offer_can_be_created_with_included_items_and_quantities()
    {
        $user = User::factory()->create();

        $rice = Ingredient::create(['name' => 'Rice', 'unit' => 'kg', 'quantity' => 20, 'status' => 'active']);
        $chicken = Ingredient::create(['name' => 'Chicken', 'unit' => 'kg', 'quantity' => 10, 'status' => 'active']);

        $response = $this->actingAs($user)->post(route('offers.store'), [
            'name' => 'Family Combo',
            'description' => 'Rice + chicken bundle',
            'price' => 990,
            'is_active' => '1',
            'ingredient_ids' => [$rice->id, $chicken->id],
            'quantities' => [
                $rice->id => 1.5,
                $chicken->id => 0.5,
            ],
        ]);

        $response->assertRedirect(route('offers.index'));

        $offer = Offer::where('name', 'Family Combo')->firstOrFail();
        $this->assertTrue($offer->is_active);
        $this->assertEquals(2, $offer->ingredients()->count());
        $riceRow = $offer->ingredients()->where('ingredient_id', $rice->id)->first();
        $this->assertEquals(1.5, $riceRow->pivot->quantity);
    }

    public function test_adding_an_offer_in_pos_creates_billing_and_ingredient_component_lines_and_deducts_stock_at_offer_rate()
    {
        $user = User::factory()->create();

        $napkin = Ingredient::create(['name' => 'Napkin', 'unit' => 'pcs', 'quantity' => 10, 'status' => 'active']);

        $offer = Offer::create(['name' => 'Combo Deal', 'price' => 990, 'is_active' => true]);
        $offer->ingredients()->attach($napkin->id, ['quantity' => 2]); // 2 napkins per offer sold

        $order = Order::create(['order_number' => 'ORD-000001', 'user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson(route('pos.offer.add', $order), [
            'offer_id' => $offer->id,
            'quantity' => 1,
        ]);
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $items = OrderItem::where('order_id', $order->id)->get();
        $this->assertCount(2, $items);

        $billingLine = $items->firstWhere('is_offer_component', false);
        $this->assertNotNull($billingLine);
        $this->assertEquals(990, $billingLine->unit_price);
        $this->assertNull($billingLine->product_id);

        $component = $items->firstWhere('is_offer_component', true);
        $this->assertNotNull($component);
        $this->assertEquals($napkin->id, $component->ingredient_id);
        $this->assertEquals(0, $component->unit_price);
        $this->assertEquals(2, $component->offer_component_qty);

        // Bill display hides the hidden component line.
        $this->actingAs($user)->getJson(route('pos.order.show', $order))
            ->assertJsonCount(1, 'items');

        // Paying sends the order to the kitchen and deducts stock in one action,
        // at the offer's per-unit rate (2 napkins per offer sold).
        $this->actingAs($user)->postJson(route('pos.order.token_number', $order), ['token_number' => 1]);
        $payResponse = $this->actingAs($user)->postJson(route('pos.order.pay', $order), [
            'payment_method' => 'cash',
            'amount_paid' => 990,
        ]);
        $payResponse->assertStatus(200);
        $payResponse->assertJsonPath('success', true);
        $payResponse->assertJsonCount(1, 'kot_items');
        $payResponse->assertJsonPath('kot_items.0.product_name', 'Napkin');
        $payResponse->assertJsonPath('kot_items.0.quantity', 2);

        $this->assertEquals(8, $napkin->fresh()->quantity);
    }

    public function test_paying_with_insufficient_ingredient_stock_is_rejected_and_order_stays_open()
    {
        $user = User::factory()->create();

        $napkin = Ingredient::create(['name' => 'Napkin', 'unit' => 'pcs', 'quantity' => 1, 'status' => 'active']);

        $offer = Offer::create(['name' => 'Combo Deal', 'price' => 990, 'is_active' => true]);
        $offer->ingredients()->attach($napkin->id, ['quantity' => 2]); // needs 2, only 1 in stock

        $order = Order::create(['order_number' => 'ORD-000003', 'user_id' => $user->id]);
        $this->actingAs($user)->postJson(route('pos.offer.add', $order), ['offer_id' => $offer->id]);
        $this->actingAs($user)->postJson(route('pos.order.token_number', $order), ['token_number' => 2]);

        $response = $this->actingAs($user)->postJson(route('pos.order.pay', $order), [
            'payment_method' => 'cash',
            'amount_paid' => 990,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);

        $this->assertEquals(1, $napkin->fresh()->quantity);
        $this->assertEquals('pending', $order->fresh()->status);
    }

    public function test_adding_same_offer_twice_increases_quantity_of_all_its_lines()
    {
        $user = User::factory()->create();

        $ingredient = Ingredient::create(['name' => 'Sauce', 'unit' => 'ml', 'quantity' => 1000, 'status' => 'active']);

        $offer = Offer::create(['name' => 'Snack Pack', 'price' => 300, 'is_active' => true]);
        $offer->ingredients()->attach($ingredient->id, ['quantity' => 10]);

        $order = Order::create(['order_number' => 'ORD-000002', 'user_id' => $user->id]);

        $this->actingAs($user)->postJson(route('pos.offer.add', $order), ['offer_id' => $offer->id]);
        $this->actingAs($user)->postJson(route('pos.offer.add', $order), ['offer_id' => $offer->id]);

        $items = OrderItem::where('order_id', $order->id)->get();
        $this->assertCount(2, $items);
        $items->each(fn($item) => $this->assertEquals(2, $item->quantity));
    }
}
