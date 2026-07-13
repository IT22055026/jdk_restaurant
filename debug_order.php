<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\Order;
$orders = Order::whereNotNull('kot_printed_at')->get();
echo "Found " . $orders->count() . " orders with KOT printed.\n";
foreach ($orders as $o) {
    echo "  - " . $o->order_number . " (at: " . $o->kot_printed_at . ")\n";
}
