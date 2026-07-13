<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Http\Controllers\PosController;
use Illuminate\Http\Request;

$controller = app(PosController::class);
$request = new Request();
$response = $controller->getKotHistory($request);
echo $response->getContent();
