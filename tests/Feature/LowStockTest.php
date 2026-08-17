<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;

class LowStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_create_triggers_low_stock_warning()
    {
        $user = User::factory()->create();

        $data = [
            'name' => 'Test Bottle',
            'selling_price' => 10,
            'quantity' => 2,
            'low_stock_threshold' => 5,
            'is_unlimited_stock' => false,
            'is_finished_good' => true,
            'status' => 'active',
        ];

        $response = $this->actingAs($user)->post(route('products.store'), $data);

        $response->assertStatus(302);
        $response->assertSessionHas('low_stock_warning');
    }

    public function test_product_update_triggers_low_stock_warning()
    {
        $user = User::factory()->create();

        $product = Product::create([
            'name' => 'Update Bottle',
            'price' => 10,
            'selling_price' => 10,
            'quantity' => 100,
            'low_stock_threshold' => 5,
            'is_unlimited_stock' => false,
            'is_finished_good' => true,
            'status' => 'active',
        ]);

        $update = [
            'name' => $product->name,
            'selling_price' => $product->selling_price,
            'quantity' => 3,
            'low_stock_threshold' => $product->low_stock_threshold,
            'is_unlimited_stock' => false,
            'is_finished_good' => true,
            'status' => 'active',
        ];

        $response = $this->actingAs($user)->put(route('products.update', $product), $update);

        $response->assertStatus(302);
        $response->assertSessionHas('low_stock_warning');
    }
}
