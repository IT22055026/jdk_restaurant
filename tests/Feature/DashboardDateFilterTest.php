<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Wastage;
use App\Support\BusinessDay;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardDateFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'Admin'],
            ['slug' => 'admin']
        );

        $this->admin = User::factory()->create([
            'role_id' => $role->id,
        ]);
    }

    public function test_dashboard_renders_all_time_data_by_default()
    {
        $response = $this->actingAs($this->admin)->get(route('reports.index'));
        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('Date Filter:');
        $response->assertSee('Stock &amp; Wastage Activity', false);
    }

    public function test_dashboard_filters_sales_products_and_stocks_for_a_specific_day()
    {
        Carbon::setTestNow('2026-08-25 14:00:00');
        $today = BusinessDay::today();

        $category = Category::create(['name' => 'Main Dishes']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Beef Shawarma',
            'price' => 1200,
            'selling_price' => 1200,
            'quantity' => 15,
            'alert_quantity' => 5,
            'is_unlimited_stock' => false,
            'is_finished_good' => true,
            'status' => 'active',
        ]);

        // Order today
        $orderToday = Order::create([
            'order_number' => 'ORD-20260825-001',
            'user_id' => $this->admin->id,
            'status' => 'completed',
            'payment_method' => 'cash',
            'subtotal' => 2400,
            'total' => 2400,
            'created_at' => '2026-08-25 12:00:00',
        ]);

        OrderItem::create([
            'order_id' => $orderToday->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 2,
            'unit_price' => 1200,
            'subtotal' => 2400,
        ]);

        // Stock movement today
        StockMovement::create([
            'product_id' => $product->id,
            'change_type' => 'decrease',
            'quantity' => -2,
            'reason' => 'Inventory count',
            'created_at' => '2026-08-25 13:00:00',
        ]);

        // Wastage today
        Wastage::create([
            'product_id' => $product->id,
            'quantity' => 1,
            'reason' => 'Spilled during prep',
            'date' => '2026-08-25',
            'created_at' => '2026-08-25 13:30:00',
        ]);

        // Test filtering by today
        $response = $this->actingAs($this->admin)->get(route('reports.index', ['date' => '2026-08-25']));
        $response->assertStatus(200);
        $response->assertSee('Beef Shawarma');
        $response->assertSee('2,400.00');
        $response->assertSee('Spilled during prep');
        $response->assertSee('Inventory count');

        // Test exporting filtered PDF
        $salesPdf = $this->actingAs($this->admin)->get(route('reports.export.sales', ['date' => '2026-08-25']));
        $salesPdf->assertStatus(200);
        $salesPdf->assertHeader('content-type', 'application/pdf');

        $productsPdf = $this->actingAs($this->admin)->get(route('reports.export.products', ['date' => '2026-08-25']));
        $productsPdf->assertStatus(200);
        $productsPdf->assertHeader('content-type', 'application/pdf');

        $combinedPdf = $this->actingAs($this->admin)->get(route('reports.export.combined', ['date' => '2026-08-25']));
        $combinedPdf->assertStatus(200);
        $combinedPdf->assertHeader('content-type', 'application/pdf');
    }
}
