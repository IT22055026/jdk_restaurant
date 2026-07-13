<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$orderId = 55;
$order = \App\Models\Order::find($orderId);

echo "\n=== SIMULATE USER FLOW ===\n";
echo "Order 55 ID: " . $order->id . "\n\n";

// Step 1: Check current items
echo "Step 1: Current items\n";
foreach($order->items as $i) {
    $p = $i->kot_printed ? 'YES' : 'NO';
    echo "  ID:{$i->id} | {$i->product_name} | Qty:{$i->quantity} | KOT:{$p}\n";
}

// Step 2: User clicks button to add Fried Rice
echo "\nStep 2: User adds Fried Rice through frontend\n";
echo "Frontend calls POST /pos/order/55/item with {product_id: 1, quantity: 1}\n";

$product = \App\Models\Product::find(1);
$price = $product->selling_price ?? $product->price;

// Backend logic from addItem()
$existingItem = \App\Models\OrderItem::where('order_id', $order->id)
    ->where('product_id', $product->id)
    ->where('kot_printed', false)
    ->first();

echo "Backend: Search for unprinted Fried Rice... " . ($existingItem ? 'FOUND' : 'NOT FOUND') . "\n";

if (!$existingItem) {
    $newItem = \App\Models\OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'unit_price' => $price,
        'quantity' => 1,
        'subtotal' => $price,
        'kitchen_notes' => null,
        'is_bar_item' => false,
        'kot_printed' => false,
    ]);
    echo "Backend: Created new item ID " . $newItem->id . " with kot_printed=" . ($newItem->kot_printed ? 'true' : 'false') . "\n";
}

// Step 3: Frontend calls syncOrder
echo "\nStep 3: Frontend calls syncOrder to fetch fresh data\n";
$order->refresh();
echo "Fetched order with items:\n";
foreach($order->items as $i) {
    $p = $i->kot_printed ? 'YES' : 'NO';
    echo "  ID:{$i->id} | {$i->product_name} | Qty:{$i->quantity} | KOT:{$p}\n";
}

// Step 4: User clicks Print KOT
echo "\nStep 4: User clicks Print KOT\n";
$order->load('items');
$kitchenItems = $order->items->where('is_bar_item', false)->values();
$unprintedItems = $kitchenItems->filter(function($item) { return !$item->kot_printed; });

echo "Backend: Found " . $unprintedItems->count() . " unprinted items\n";
foreach($unprintedItems as $i) {
    echo "  ID:{$i->id} | {$i->product_name} | Qty:{$i->quantity}\n";
}

if ($unprintedItems->count() > 0) {
    $unprintedItemIds = $unprintedItems->pluck('id')->toArray();
    \App\Models\OrderItem::whereIn('id', $unprintedItemIds)->update(['kot_printed' => true]);
    echo "Backend: Marked as printed\n";
} else {
    echo "Backend: NO ITEMS TO PRINT - returning error\n";
}

// Final state
echo "\nFinal state after all operations:\n";
$order->refresh();
foreach($order->items as $i) {
    $p = $i->kot_printed ? 'YES' : 'NO';
    echo "  ID:{$i->id} | {$i->product_name} | Qty:{$i->quantity} | KOT:{$p}\n";
}
