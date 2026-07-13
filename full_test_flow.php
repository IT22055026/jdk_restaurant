<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n=== COMPLETE TESTING FLOW ===\n\n";

// Create a new order
$order = \App\Models\Order::create([
    'order_number' => 'TEST-' . time(),
    'table_id' => null,
    'user_id' => 1,
    'order_type' => 'dine_in',
]);

echo "Created Order: " . $order->order_number . "\n\n";

// Step 1: Add Fried Rice
echo "STEP 1: Add Fried Rice\n";
$product = \App\Models\Product::find(1);
$existingItem = \App\Models\OrderItem::where('order_id', $order->id)
    ->where('product_id', $product->id)
    ->where('kot_printed', false)
    ->first();

echo "  Unprinted Fried Rice found: " . ($existingItem ? 'YES' : 'NO') . "\n";

if (!$existingItem) {
    $item1 = \App\Models\OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'unit_price' => $product->selling_price ?? $product->price,
        'quantity' => 1,
        'subtotal' => $product->selling_price ?? $product->price,
        'kot_printed' => false,
    ]);
    echo "  Created item ID " . $item1->id . " with kot_printed=" . ($item1->kot_printed ? 'true' : 'false') . "\n";
}

// Step 2: Add Cocacola
echo "\nSTEP 2: Add Cocacola\n";
$product = \App\Models\Product::find(2);
$existingItem = \App\Models\OrderItem::where('order_id', $order->id)
    ->where('product_id', $product->id)
    ->where('kot_printed', false)
    ->first();

echo "  Unprinted Cocacola found: " . ($existingItem ? 'YES' : 'NO') . "\n";

if (!$existingItem) {
    $item2 = \App\Models\OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'unit_price' => $product->selling_price ?? $product->price,
        'quantity' => 1,
        'subtotal' => $product->selling_price ?? $product->price,
        'kot_printed' => false,
    ]);
    echo "  Created item ID " . $item2->id . " with kot_printed=" . ($item2->kot_printed ? 'true' : 'false') . "\n";
}

// Step 3: Print KOT
echo "\nSTEP 3: Print KOT\n";
$order->load('items');
$kitchenItems = $order->items
    ->where('is_bar_item', false)
    ->where('kot_printed', false)
    ->values();

echo "  Unprinted items found: " . $kitchenItems->count() . "\n";
if ($kitchenItems->count() > 0) {
    foreach($kitchenItems as $i) {
        echo "    - {$i->product_name} qty={$i->quantity}\n";
    }
    $ids = $kitchenItems->pluck('id')->toArray();
    \DB::table('order_items')
        ->whereIn('id', $ids)
        ->update(['kot_printed' => true, 'updated_at' => \Carbon\Carbon::now()]);
    echo "  Marked as printed\n";
}

// Step 4: Add Fried Rice again
echo "\nSTEP 4: Add Fried Rice again (after KOT)\n";
$product = \App\Models\Product::find(1);
$existingItem = \App\Models\OrderItem::where('order_id', $order->id)
    ->where('product_id', $product->id)
    ->where('kot_printed', false)
    ->first();

echo "  Unprinted Fried Rice found: " . ($existingItem ? 'YES' : 'NO') . "\n";

if ($existingItem) {
    $existingItem->quantity += 1;
    $existingItem->subtotal = $existingItem->unit_price * $existingItem->quantity;
    $existingItem->save();
    echo "  Incremented existing item to qty=" . $existingItem->quantity . "\n";
} else {
    $item3 = \App\Models\OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'unit_price' => $product->selling_price ?? $product->price,
        'quantity' => 1,
        'subtotal' => $product->selling_price ?? $product->price,
        'kot_printed' => false,
    ]);
    echo "  Created NEW item ID " . $item3->id . " with kot_printed=" . ($item3->kot_printed ? 'true' : 'false') . "\n";
}

// Step 5: Print KOT again
echo "\nSTEP 5: Print KOT again\n";
$order->load('items');
$kitchenItems = $order->items
    ->where('is_bar_item', false)
    ->where('kot_printed', false)
    ->values();

echo "  Unprinted items found: " . $kitchenItems->count() . "\n";
if ($kitchenItems->count() > 0) {
    foreach($kitchenItems as $i) {
        echo "    - {$i->product_name} qty={$i->quantity}\n";
    }
} else {
    echo "  NO ITEMS TO PRINT\n";
}

// Final inventory
echo "\n\n=== FINAL STATE ===\n";
$order->refresh();
foreach($order->items as $i) {
    $p = $i->kot_printed ? 'YES' : 'NO';
    echo "ID:{$i->id} | {$i->product_name} | Qty:{$i->quantity} | KOT:{$p}\n";
}
