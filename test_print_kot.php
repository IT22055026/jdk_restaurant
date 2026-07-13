<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\Order;
use App\Models\OrderItem;
use App\Http\Controllers\PosController;

try {
    // Create a test order
    $order = Order::create([
        'order_number' => 'ORD-TEST-' . time(),
        'order_type' => 'dine_in',
        'status' => 'pending',
        'user_id' => 1 // might be needed
    ]);

    // Add a kitchen item
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => 1,
        'product_name' => 'Pizza',
        'unit_price' => 10,
        'quantity' => 1,
        'subtotal' => 10,
        'is_bar_item' => false,
        'printed_qty' => 0
    ]);

    $controller = app(PosController::class);
    echo "Calling printKot...\n";
    $res = $controller->printKot($order);
    echo "Response: " . $res->getContent() . "\n";

    $order->refresh();
    echo "kot_printed_at: " . ($order->kot_printed_at ?? 'NULL') . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "TRACE: " . $e->getTraceAsString() . "\n";
}
