<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\Schema;

echo "ORDER_ITEMS COLUMNS:\n";
foreach (Schema::getColumnListing('order_items') as $c) echo " - $c\n";

echo "\nORDERS COLUMNS:\n";
foreach (Schema::getColumnListing('orders') as $c) echo " - $c\n";
