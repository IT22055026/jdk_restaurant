<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\Order;
foreach (Order::latest()->take(3)->get() as $o) {
    echo "Order: {$o->order_number}, Items count: " . $o->items->count() . "\n";
    foreach ($o->items as $i) {
        echo "  Item: {$i->product_name}, Qty: {$i->quantity}, Printed: {$i->printed_qty}, IsBar: " . ($i->is_bar_item ? 'YES':'NO') . "\n";
    }
}
