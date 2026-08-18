<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Ingredient;
use App\Models\Offer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

class OfferTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(string $name, float $price = 100): Product
    {
        return Product::create([
            'name' => $name,
            'price' => $price,
            'quantity' => 100,
            'is_finished_good' => true,
            'status' => 'active',
        ]);
    }

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

    public function test_offer_can_be_created_with_a_flavour_choice_and_a_pick_quantity()
    {
        $user = User::factory()->create();

        $mojito = $this->makeProduct('Passion Mojito');
        $lime = $this->makeProduct('Lime Soda');

        $response = $this->actingAs($user)->post(route('offers.store'), [
            'name' => 'Family Combo',
            'price' => 1990,
            'is_active' => '1',
            'flavour_ids' => [$mojito->id, $lime->id],
            'flavour_qty' => 2,
        ]);

        $response->assertRedirect(route('offers.index'));

        $offer = Offer::where('name', 'Family Combo')->firstOrFail();
        $this->assertEquals(2, $offer->flavour_qty);
        $this->assertEquals(2, $offer->flavours()->count());
    }

    public function test_adding_a_flavour_offer_without_enough_picks_is_rejected()
    {
        $user = User::factory()->create();

        $mojito = $this->makeProduct('Passion Mojito');
        $lime = $this->makeProduct('Lime Soda');

        $offer = Offer::create(['name' => 'Family Combo', 'price' => 1990, 'is_active' => true, 'flavour_qty' => 2]);
        $offer->flavours()->sync([$mojito->id, $lime->id]);

        $order = Order::create(['order_number' => 'ORD-000010', 'user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson(route('pos.offer.add', $order), [
            'offer_id' => $offer->id,
            'flavour_product_ids' => [$mojito->id], // only 1, needs 2
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $this->assertCount(0, OrderItem::where('order_id', $order->id)->get());
    }

    public function test_adding_a_flavour_offer_with_a_product_outside_the_eligible_list_is_rejected()
    {
        $user = User::factory()->create();

        $mojito = $this->makeProduct('Passion Mojito');
        $burger = $this->makeProduct('Beef Burger');

        $offer = Offer::create(['name' => 'Drink Deal', 'price' => 500, 'is_active' => true, 'flavour_qty' => 1]);
        $offer->flavours()->sync([$mojito->id]);

        $order = Order::create(['order_number' => 'ORD-000011', 'user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson(route('pos.offer.add', $order), [
            'offer_id' => $offer->id,
            'flavour_product_ids' => [$burger->id],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_adding_a_flavour_offer_with_two_different_picks_creates_two_component_lines_and_a_combined_label()
    {
        $user = User::factory()->create();

        $mojito = $this->makeProduct('Passion Mojito', 350);
        $lime = $this->makeProduct('Lime Soda', 250);

        $offer = Offer::create(['name' => 'Family Combo', 'price' => 1990, 'is_active' => true, 'flavour_qty' => 2]);
        $offer->flavours()->sync([$mojito->id, $lime->id]);

        $order = Order::create(['order_number' => 'ORD-000012', 'user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson(route('pos.offer.add', $order), [
            'offer_id' => $offer->id,
            'flavour_product_ids' => [$mojito->id, $lime->id],
        ]);
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $items = OrderItem::where('order_id', $order->id)->get();
        $billingLine = $items->firstWhere('is_offer_component', false);
        $this->assertNotNull($billingLine);
        $this->assertStringContainsString('Passion Mojito', $billingLine->product_name);
        $this->assertStringContainsString('Lime Soda', $billingLine->product_name);

        $components = $items->where('is_offer_component', true)->whereNotNull('product_id');
        $this->assertCount(2, $components);
        $components->each(fn($item) => $this->assertEquals(1, $item->quantity));

        // The bill only ever shows the one billing line to the cashier.
        $this->actingAs($user)->getJson(route('pos.order.show', $order))
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.offer_flavour_locked', true);
    }

    public function test_adding_the_same_flavour_offer_again_with_a_repeated_pick_accumulates_that_products_component_quantity()
    {
        $user = User::factory()->create();

        $mojito = $this->makeProduct('Passion Mojito');
        $lime = $this->makeProduct('Lime Soda');

        $offer = Offer::create(['name' => 'Family Combo', 'price' => 1990, 'is_active' => true, 'flavour_qty' => 2]);
        $offer->flavours()->sync([$mojito->id, $lime->id]);

        $order = Order::create(['order_number' => 'ORD-000013', 'user_id' => $user->id]);

        $this->actingAs($user)->postJson(route('pos.offer.add', $order), [
            'offer_id' => $offer->id,
            'flavour_product_ids' => [$mojito->id, $lime->id],
        ]);
        $this->actingAs($user)->postJson(route('pos.offer.add', $order), [
            'offer_id' => $offer->id,
            'flavour_product_ids' => [$mojito->id, $mojito->id],
        ]);

        $items = OrderItem::where('order_id', $order->id)->get();
        $billingLine = $items->firstWhere('is_offer_component', false);
        $this->assertEquals(2, $billingLine->quantity);

        $mojitoComponent = $items->where('is_offer_component', true)->firstWhere('product_id', $mojito->id);
        $limeComponent = $items->where('is_offer_component', true)->firstWhere('product_id', $lime->id);
        $this->assertEquals(3, $mojitoComponent->quantity); // 1 from first add + 2 from second
        $this->assertEquals(1, $limeComponent->quantity);
    }

    public function test_paying_is_blocked_if_a_flavour_offer_line_is_missing_required_picks()
    {
        $user = User::factory()->create();

        $mojito = $this->makeProduct('Passion Mojito');
        $lime = $this->makeProduct('Lime Soda');

        $offer = Offer::create(['name' => 'Family Combo', 'price' => 1990, 'is_active' => true, 'flavour_qty' => 2]);
        $offer->flavours()->sync([$mojito->id, $lime->id]);

        $order = Order::create(['order_number' => 'ORD-000014', 'user_id' => $user->id]);

        $this->actingAs($user)->postJson(route('pos.offer.add', $order), [
            'offer_id' => $offer->id,
            'flavour_product_ids' => [$mojito->id, $lime->id],
        ]);

        // Simulate an incomplete state directly (e.g. a stray manual deletion of
        // one flavour component row) to exercise the pay-time safety net.
        OrderItem::where('order_id', $order->id)
            ->where('is_offer_component', true)
            ->where('product_id', $lime->id)
            ->delete();

        $this->actingAs($user)->postJson(route('pos.order.token_number', $order), ['token_number' => 5]);
        $response = $this->actingAs($user)->postJson(route('pos.order.pay', $order), [
            'payment_method' => 'cash',
            'amount_paid' => 1990,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $this->assertEquals('pending', $order->fresh()->status);
    }

    public function test_offer_can_be_created_with_fixed_products_and_choice_groups()
    {
        $user = User::factory()->create();

        $chicken = Ingredient::create(['name' => 'Chicken', 'unit' => 'kg', 'quantity' => 10, 'status' => 'active']);
        $fries = $this->makeProduct('French Fries', 400);

        $coke = $this->makeProduct('Coca Cola', 200);
        $pepsi = $this->makeProduct('Pepsi', 200);
        $cake = $this->makeProduct('Chocolate Cake', 350);
        $iceCream = $this->makeProduct('Vanilla Ice Cream', 300);

        $response = $this->actingAs($user)->post(route('offers.store'), [
            'name' => 'Mega Combo 01',
            'price' => 2500,
            'is_active' => '1',
            'ingredient_ids' => [$chicken->id],
            'ingredient_quantities' => [$chicken->id => 1.0],
            'product_ids' => [$fries->id],
            'product_quantities' => [$fries->id => 2],
            'choice_groups' => [
                [
                    'name' => 'Drinks',
                    'choice_qty' => 2,
                    'product_ids' => [$coke->id, $pepsi->id],
                ],
                [
                    'name' => 'Dessert',
                    'choice_qty' => 1,
                    'product_ids' => [$cake->id, $iceCream->id],
                ],
            ],
        ]);

        $response->assertRedirect(route('offers.index'));

        $offer = Offer::where('name', 'Mega Combo 01')->firstOrFail();
        $this->assertEquals(1, $offer->ingredients()->count());
        $this->assertEquals(1, $offer->products()->count());
        $this->assertEquals(2, $offer->choiceGroups()->count());

        $drinksGroup = $offer->choiceGroups()->where('name', 'Drinks')->firstOrFail();
        $this->assertEquals(2, $drinksGroup->choice_qty);
        $this->assertEquals(2, $drinksGroup->products()->count());

        $dessertGroup = $offer->choiceGroups()->where('name', 'Dessert')->firstOrFail();
        $this->assertEquals(1, $dessertGroup->choice_qty);
        $this->assertEquals(2, $dessertGroup->products()->count());
    }

    public function test_adding_offer_with_multi_group_choices_at_pos()
    {
        $user = User::factory()->create();

        $chicken = Ingredient::create(['name' => 'Chicken', 'unit' => 'kg', 'quantity' => 10, 'status' => 'active']);
        $mayo = Ingredient::create(['name' => 'Mayonnaise', 'unit' => 'g', 'quantity' => 500, 'status' => 'active']);
        $fries = $this->makeProduct('French Fries', 400);

        $coke = $this->makeProduct('Coca Cola', 200);
        $pepsi = $this->makeProduct('Pepsi', 200);
        $cake = $this->makeProduct('Chocolate Cake', 350);

        $offer = Offer::create(['name' => 'Mega Combo 01', 'price' => 2500, 'is_active' => true]);
        $offer->ingredients()->attach($chicken->id, ['quantity' => 0.5]);
        $offer->ingredients()->attach($mayo->id, ['quantity' => 50]);
        $offer->products()->attach($fries->id, ['quantity' => 1]);

        $drinksGroup = $offer->choiceGroups()->create(['name' => 'Drinks', 'choice_qty' => 2, 'sort_order' => 0]);
        $drinksGroup->products()->sync([$coke->id, $pepsi->id]);

        $dessertGroup = $offer->choiceGroups()->create(['name' => 'Dessert', 'choice_qty' => 1, 'sort_order' => 1]);
        $dessertGroup->products()->sync([$cake->id]);

        $order = Order::create(['order_number' => 'ORD-000050', 'user_id' => $user->id]);

        // Attempt adding with missing dessert choice -> Should be rejected
        $resIncomplete = $this->actingAs($user)->postJson(route('pos.offer.add', $order), [
            'offer_id' => $offer->id,
            'choice_picks' => [
                ['group_id' => $drinksGroup->id, 'product_ids' => [$coke->id, $pepsi->id]],
                ['group_id' => $dessertGroup->id, 'product_ids' => []], // missing dessert!
            ],
        ]);
        $resIncomplete->assertStatus(422);

        // Add with valid choices: 2 drinks (1 Coke + 1 Pepsi) + 1 dessert (Chocolate Cake)
        $resSuccess = $this->actingAs($user)->postJson(route('pos.offer.add', $order), [
            'offer_id' => $offer->id,
            'choice_picks' => [
                ['group_id' => $drinksGroup->id, 'product_ids' => [$coke->id, $pepsi->id]],
                ['group_id' => $dessertGroup->id, 'product_ids' => [$cake->id]],
            ],
        ]);
        $resSuccess->assertStatus(200);

        // Verify billing line name contains the picked customer choices
        $billingLine = OrderItem::where('order_id', $order->id)->where('is_offer_component', false)->firstOrFail();
        $this->assertStringContainsString('Mega Combo 01', $billingLine->product_name);
        $this->assertStringContainsString('Coca Cola', $billingLine->product_name);
        $this->assertStringContainsString('Pepsi', $billingLine->product_name);
        $this->assertStringContainsString('Chocolate Cake', $billingLine->product_name);

        // Verify component lines: 2 fixed ingredients, 1 fixed product, 3 picked choices = 6 component lines
        $components = OrderItem::where('order_id', $order->id)->where('is_offer_component', true)->get();
        $this->assertCount(6, $components);

        // Verify fixed ingredient component
        $chickenLine = $components->firstWhere('ingredient_id', $chicken->id);
        $this->assertNotNull($chickenLine);
        $this->assertEquals(0.5, $chickenLine->offer_component_qty);

        // Verify fixed product component
        $friesLine = $components->where('product_id', $fries->id)->whereNotNull('offer_component_qty')->first();
        $this->assertNotNull($friesLine);
        $this->assertEquals(1, $friesLine->quantity);

        // Verify picked products
        $cokeLine = $components->where('product_id', $coke->id)->whereNull('offer_component_qty')->first();
        $this->assertNotNull($cokeLine);
        $this->assertEquals(1, $cokeLine->quantity);

        // Pay order and check stock deductions
        $this->actingAs($user)->postJson(route('pos.order.token_number', $order), ['token_number' => 10]);
        $payRes = $this->actingAs($user)->postJson(route('pos.order.pay', $order), [
            'payment_method' => 'cash',
            'amount_paid' => 2500,
        ]);
        $payRes->assertStatus(200);

        $this->assertEquals(9.5, $chicken->fresh()->quantity);
        $this->assertEquals(450, $mayo->fresh()->quantity);
        $this->assertEquals(99, $fries->fresh()->quantity);
        $this->assertEquals(99, $coke->fresh()->quantity);
        $this->assertEquals(99, $pepsi->fresh()->quantity);
        $this->assertEquals(99, $cake->fresh()->quantity);
    }
}
