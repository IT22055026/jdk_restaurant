<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\WastageController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\QrMenuController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\OfferController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Public QR Menu Routes (no auth required)
Route::get('/menu/scan', [QrMenuController::class, 'viewMenu'])->name('menu.view');
Route::get('/api/menu/category/{categoryId}', [QrMenuController::class, 'getProductsByCategory']);
Route::get('/qr-code/generate', [QrMenuController::class, 'generateQr'])->name('qr.generate');
Route::get('/qr-code/download', [QrMenuController::class, 'downloadQr'])->name('qr.download');
Route::get('/qr-code/pdf', [QrMenuController::class, 'downloadQrPdf'])->name('qr.pdf');

// Auth Routes
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'store']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'module.access'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // QR Menu Management (Admin)
    Route::get('/qr-menu/admin', function () {
        $modules = Auth::user()->role->modules()->get();
        return view('qr-menu.qr-admin', ['modules' => $modules]);
    })->name('qr.admin');

    // Customer CRUD routes
    Route::get('/customers/search', [CustomerController::class, 'search'])->name('customers.search');
    Route::resource('customers', CustomerController::class);

    // Category CRUD routes
    Route::resource('categories', CategoryController::class)->except(['show']);

    // Product (Inventory) CRUD routes
    Route::resource('products', ProductController::class);
    Route::get('/inventory', [ProductController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/dashboard', [ProductController::class, 'dashboard'])->name('inventory.dashboard');

    // Recipe (Bill of Materials) management per product
    Route::get('/products/{product}/recipe', [RecipeController::class, 'edit'])->name('products.recipe.edit');
    Route::put('/products/{product}/recipe', [RecipeController::class, 'update'])->name('products.recipe.update');

    // Ingredient (raw stock) CRUD routes
    Route::resource('ingredients', IngredientController::class)->except(['show']);

    // Employee CRUD routes
    Route::resource('employees', EmployeeController::class);

    // User password update
    Route::post('/users/{user}/update-password', [UserController::class, 'updatePassword']);

    // Wastage CRUD routes
    Route::resource('wastages', WastageController::class);
    Route::get('/wastage', function () {
        return app(WastageController::class)->index();
    })->name('wastage.index');

    // Placeholder routes for other modules
    Route::resource('suppliers', SupplierController::class)->except(['show']);

    // Shift & Till Management routes
    Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
    Route::post('/shifts/start', [ShiftController::class, 'startShift'])->name('shifts.start');
    Route::post('/shifts/close', [ShiftController::class, 'closeShift'])->name('shifts.close');
    Route::get('/shifts/active', [ShiftController::class, 'getActiveShift'])->name('shifts.active');
    Route::get('/shifts/{shift}', [ShiftController::class, 'getShiftDetails'])->name('shifts.details');
    Route::post('/shifts/{shift}/transaction', [ShiftController::class, 'recordTransaction'])->name('shifts.transaction');

    // POS routes
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos/order', [PosController::class, 'createOrder'])->name('pos.order.create');
    Route::get('/pos/order/{order}', [PosController::class, 'getOrder'])->name('pos.order.show');
    Route::post('/pos/order/{order}/item', [PosController::class, 'addItem'])->name('pos.item.add');
    Route::delete('/pos/order/{order}/item/{item}', [PosController::class, 'removeItem'])->name('pos.item.remove');
    Route::put('/pos/order/{order}/item/{item}', [PosController::class, 'updateItem'])->name('pos.item.update');
    Route::post('/pos/order/{order}/complete', [PosController::class, 'completeOrder'])->name('pos.order.complete');
    Route::post('/pos/order/{order}/customer', [PosController::class, 'updateCustomer'])->name('pos.order.customer');
    Route::post('/pos/order/{order}/token-number', [PosController::class, 'updateToken'])->name('pos.order.token_number');
    Route::get('/pos/offers', [PosController::class, 'getActiveOffers'])->name('pos.offers');
    Route::post('/pos/order/{order}/offer', [PosController::class, 'addOffer'])->name('pos.offer.add');
    Route::post('/pos/order/{order}/waiter-bill', [PosController::class, 'printWaiterBill'])->name('pos.order.waiter_bill');
    Route::post('/pos/order/{order}/live-bill', [PosController::class, 'toggleLiveBill'])->name('pos.order.live_bill');
    Route::post('/pos/order/{order}/cancel', [PosController::class, 'cancelOrder'])->name('pos.order.cancel');
    Route::get('/pos/products', [PosController::class, 'getProducts'])->name('pos.products');
    Route::post('/pos/order/{order}/pay', [PosController::class, 'payOrder'])->name('pos.order.pay');

    // Order & Token History routes
    Route::get('/order-history', [PosController::class, 'orderHistory'])->name('order.history');
    Route::get('/api/order-history', [PosController::class, 'getOrderHistory'])->name('api.order.history');
    Route::get('/token-history', [PosController::class, 'tokenHistory'])->name('token.history');
    Route::get('/api/token-history', [PosController::class, 'getTokenHistory'])->name('api.token.history');
    Route::get('/pos/order/{order}/receipt/reprint', [PosController::class, 'reprintReceipt'])->name('pos.receipt.reprint');
    Route::get('/pos/order/{order}/token/reprint', [PosController::class, 'reprintToken'])->name('pos.token.reprint');

    // Sales report (every bill and what happened to it, with revoke)
    Route::get('/sales-report', [PosController::class, 'salesReport'])->name('sales-report.index');
    Route::get('/api/sales-report', [PosController::class, 'getSalesReport'])->name('api.sales-report');
    Route::post('/pos/order/{order}/revoke', [PosController::class, 'revokeOrder'])->name('pos.order.revoke');

    // Discounts & Offers CRUD
    Route::resource('offers', OfferController::class)->except(['show']);

    // Stock adjustments
    Route::get('/inventory/adjustments', [StockAdjustmentController::class, 'index'])->name('stock.adjustments.index');
    Route::get('/inventory/adjustments/create', [StockAdjustmentController::class, 'create'])->name('stock.adjustments.create');
    Route::post('/inventory/adjustments', [StockAdjustmentController::class, 'store'])->name('stock.adjustments.store');

    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/sales-pdf', [ReportsController::class, 'exportSalesPdf'])->name('reports.export.sales');
    Route::get('/reports/export/sales-range-pdf', [ReportsController::class, 'exportSalesRangePdf'])->name('reports.export.sales.range');
    Route::get('/reports/payment-breakdown', [ReportsController::class, 'paymentBreakdownJson'])->name('reports.payment.breakdown');
    Route::get('/reports/export/products-pdf', [ReportsController::class, 'exportProductsPdf'])->name('reports.export.products');
    Route::get('/reports/export/stock-pdf', [ReportsController::class, 'exportStockPdf'])->name('reports.export.stock');
    Route::get('/reports/export/combined-pdf', [ReportsController::class, 'exportCombinedPdf'])->name('reports.export.combined');

    Route::get('/settings', function () {
        $modules = auth()->user()->role->modules()->get();
        return view('modules.settings', ['modules' => $modules]);
    })->name('settings.index');
});
