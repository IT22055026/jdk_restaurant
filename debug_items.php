<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n=== Recent Order Items ===\n";
$items = \App\Models\OrderItem::orderBy('id', 'desc')->take(15)->get();
foreach($items as $item) {
    $printed = $item->kot_printed ? 'YES' : 'NO ';
    echo "ID:{$item->id} | Product:{$item->product_name} | Qty:{$item->quantity} | KOT Printed:{$printed} | Order:{$item->order_id}\n";
}

echo "\n=== Recent Orders ===\n";
$orders = \App\Models\Order::orderBy('id', 'desc')->take(5)->get();
foreach($orders as $order) {
    $itemCount = $order->items()->count();
    $unprintedCount = $order->items()->where('kot_printed', false)->count();
    echo "Order #{$order->order_number} | Items:{$itemCount} | Unprinted:{$unprintedCount}\n";
}
