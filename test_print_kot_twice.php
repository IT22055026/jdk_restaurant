<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$orderId = 54;  // Order with ALL items printed
$order = \App\Models\Order::find($orderId);

echo "\n=== Test: Call printKot twice ===\n";
echo "Order 54 items:\n";
foreach($order->items as $i) {
    $p = $i->kot_printed ? 'YES' : 'NO';
    echo "  ID:{$i->id} | {$i->product_name} | KOT:{$p}\n";
}

// First KOT call - simulate
$order->load('items');
$kitchenItems = $order->items->where('is_bar_item', false)->values();
$unprintedItems = $kitchenItems->filter(fn($item) => !$item->kot_printed);

echo "\nFirst KOT call:\n";
echo "  Unprinted items found: " . $unprintedItems->count() . "\n";
if ($unprintedItems->count() > 0) {
    echo "  Would print these items\n";
    $unprintedItemIds = $unprintedItems->pluck('id')->toArray();
    \App\Models\OrderItem::whereIn('id', $unprintedItemIds)->update(['kot_printed' => true]);
} else {
    echo "  No items to print - returning error\n";
}

// Second KOT call
$order->refresh();
$order->load('items');
$kitchenItems = $order->items->where('is_bar_item', false)->values();
$unprintedItems = $kitchenItems->filter(fn($item) => !$item->kot_printed);

echo "\nSecond KOT call:\n";
echo "  Unprinted items found: " . $unprintedItems->count() . "\n";
if ($unprintedItems->count() > 0) {
    echo "  Would print these items\n";
} else {
    echo "  No items to print - returning error with '422' status\n";
}
