<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftPdfTest extends TestCase
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

    public function test_close_shift_returns_pdf_url_and_can_download_pdf()
    {
        $start = $this->actingAs($this->cashier)->postJson(route('shifts.start'), [
            'opening_balance' => 20000,
        ]);
        $start->assertStatus(200);
        $shiftId = $start->json('shift_id');

        $product = Product::create([
            'name' => 'Chicken Kottu',
            'price' => 1500,
            'selling_price' => 1500,
            'quantity' => 20,
            'is_unlimited_stock' => false,
            'is_finished_good' => true,
            'status' => 'active',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-20260825-1001',
            'user_id' => $this->cashier->id,
            'token_number' => 12,
            'status' => 'completed',
            'payment_method' => 'cash',
            'subtotal' => 1500,
            'total' => 1500,
            'amount_paid' => 1500,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 1500,
            'subtotal' => 1500,
        ]);

        $closeResponse = $this->actingAs($this->cashier)->postJson(route('shifts.close'), [
            'shift_id' => $shiftId,
            'denominations' => [
                ['denomination' => 5000, 'quantity' => 4],
                ['denomination' => 1000, 'quantity' => 1],
                ['denomination' => 500, 'quantity' => 1],
                ['denomination' => 100, 'quantity' => 0],
                ['denomination' => 50, 'quantity' => 0],
                ['denomination' => 20, 'quantity' => 0],
            ],
            'notes' => 'Shift balanced',
        ]);

        $closeResponse->assertStatus(200);
        $closeResponse->assertJsonPath('success', true);
        $closeResponse->assertJsonPath('shift_id', $shiftId);
        $this->assertNotEmpty($closeResponse->json('pdf_url'));

        // Test downloading shift PDF
        $pdfResponse = $this->actingAs($this->cashier)->get(route('shifts.export.pdf', $shiftId));
        $pdfResponse->assertStatus(200);
        $pdfResponse->assertHeader('content-type', 'application/pdf');

        // Test get shift details returns pdf_url
        $detailsResponse = $this->actingAs($this->cashier)->getJson(route('shifts.details', $shiftId));
        $detailsResponse->assertStatus(200);
        $detailsResponse->assertJsonPath('shift.pdf_url', route('shifts.export.pdf', $shiftId));
    }
}
