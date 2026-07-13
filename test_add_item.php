<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get order 55 and add an item manually
$order = \App\Models\Order::find(55);
echo "\nOrder 55 before: " . $order->items()->count() . " items\n";

// Simulate addItem logic
$product = \App\Models\Product::find(1); // Fried Rice
$price = $product->selling_price ?? $product->price;

// Search for unprinted item
$existingItem = \App\Models\OrderItem::where('order_id', $order->id)
    ->where('product_id', $product->id)
    ->where('kot_printed', false)
    ->first();

echo "Existing unprinted item found: " . ($existingItem ? 'YES' : 'NO') . "\n";

// Create new item
$item = \App\Models\OrderItem::create([
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

echo "New item created with ID " . $item->id . " and kot_printed = " . ($item->kot_printed ? 'true' : 'false') . "\n";

// Verify it in database
$check = \App\Models\OrderItem::find($item->id);
echo "Verified in DB: kot_printed = " . ($check->kot_printed ? 'true' : 'false') . "\n";

// Check all items
echo "\n=== All items in order 55 now ===\n";
$order->refresh();
foreach($order->items as $i) {
    echo "ID:{$i->id} | {$i->product_name} | KOT:" . ($i->kot_printed ? 'YES' : 'NO') . "\n";
}
