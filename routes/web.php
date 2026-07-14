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

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Public QR Menu Routes (no auth required)
Route::get('/menu/scan', [QrMenuController::class, 'viewMenu'])->name('menu.view');
Route::get('/api/menu/category/{categoryId}', [QrMenuController::class, 'getProductsByCategory']);
Route::get('/qr-code/generate', [QrMenuController::class, 'generateQr'])->name('qr.generate');
Route::get('/qr-code/download', [QrMenuController::class, 'downloadQr'])->name('qr.download');
Route::get('/qr-code/pdf', [QrMenuController::class, 'downloadQrPdf'])->name('qr.pdf');

// Public Table QR Ordering (customers scan table QR with phones)
Route::get('/table/{tableId}/order', [PosController::class, 'tableQrMenu'])->name('table.order.menu');
Route::post('/table/{tableId}/order/submit', [PosController::class, 'submitTableOrder'])->name('table.order.submit');

// Auth Routes
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'store']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
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
    Route::post('/pos/order/{order}/hold', [PosController::class, 'holdOrder'])->name('pos.order.hold');
    Route::post('/pos/order/{order}/complete', [PosController::class, 'completeOrder'])->name('pos.order.complete');
    Route::post('/pos/order/{order}/kot', [PosController::class, 'printKot'])->name('pos.order.kot');
    Route::post('/pos/order/{order}/customer', [PosController::class, 'updateCustomer'])->name('pos.order.customer');
    Route::post('/pos/order/{order}/waiter-bill', [PosController::class, 'printWaiterBill'])->name('pos.order.waiter_bill');
    Route::post('/pos/order/{order}/live-bill', [PosController::class, 'toggleLiveBill'])->name('pos.order.live_bill');
    Route::post('/pos/order/{order}/close-table', [PosController::class, 'closeTable'])->name('pos.order.close_table');
    Route::get('/pos/table/{table}/orders', [PosController::class, 'getTableOrders'])->name('pos.table.orders');
    Route::get('/pos/tables', [PosController::class, 'getTables'])->name('pos.tables');
    Route::get('/pos/products', [PosController::class, 'getProducts'])->name('pos.products');
    Route::get('/pos/held-orders', [PosController::class, 'getHeldOrders'])->name('pos.held');
    Route::post('/pos/order/{order}/pay', [PosController::class, 'payOrder'])->name('pos.order.pay');

    // QR order notifications
    Route::get('/pos/qr-orders/notifications', [PosController::class, 'getQrOrderNotifications'])->name('pos.qr_orders.notifications');
    Route::post('/pos/qr-orders/{order}/seen', [PosController::class, 'markQrOrderSeen'])->name('pos.qr_orders.seen');
    Route::post('/pos/qr-orders/seen-all', [PosController::class, 'markAllQrOrdersSeen'])->name('pos.qr_orders.seen_all');

    // QR Code routes
    Route::get('/pos/table/{table}/qr-code', [PosController::class, 'generateTableQrCode'])->name('pos.table.qr');
    Route::post('/pos/scan-qr', [PosController::class, 'scanTableQr'])->name('pos.scan.qr');
    Route::get('/pos/qr-codes/all', [PosController::class, 'getAllTableQrCodes'])->name('pos.qr.all');
    Route::get('/pos/qr-codes/print', [PosController::class, 'tableQrCodesPrint'])->name('pos.qr.print');
    Route::get('/settings/table-qr-codes', [PosController::class, 'tableQrCodesSettings'])->name('settings.table.qr');
    Route::get('/settings/table/{tableId}/qr-download', [PosController::class, 'downloadTableQrCode'])->name('settings.table.qr.download');

    // Order & KOT History routes
    Route::get('/order-history', [PosController::class, 'orderHistory'])->name('order.history');
    Route::get('/api/order-history', [PosController::class, 'getOrderHistory'])->name('api.order.history');
    Route::get('/kot-history', [PosController::class, 'kotHistory'])->name('kot.history');
    Route::get('/api/kot-history', [PosController::class, 'getKotHistory'])->name('api.kot.history');
    Route::get('/pos/order/{order}/receipt/reprint', [PosController::class, 'reprintReceipt'])->name('pos.receipt.reprint');
    Route::get('/pos/order/{order}/kot/reprint', [PosController::class, 'reprintKot'])->name('pos.kot.reprint');

    // Stock adjustments
    Route::get('/inventory/adjustments', [StockAdjustmentController::class, 'index'])->name('stock.adjustments.index');
    Route::get('/inventory/adjustments/create', [StockAdjustmentController::class, 'create'])->name('stock.adjustments.create');
    Route::post('/inventory/adjustments', [StockAdjustmentController::class, 'store'])->name('stock.adjustments.store');

    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/sales', [ReportsController::class, 'salesReport'])->name('reports.sales');
    Route::get('/reports/export/sales-pdf', [ReportsController::class, 'exportSalesPdf'])->name('reports.export.sales');
    Route::get('/reports/export/sales-range-pdf', [ReportsController::class, 'exportSalesRangePdf'])->name('reports.export.sales.range');
    Route::get('/reports/payment-breakdown', [ReportsController::class, 'paymentBreakdownJson'])->name('reports.payment.breakdown');
    Route::get('/reports/export/products-pdf', [ReportsController::class, 'exportProductsPdf'])->name('reports.export.products');
    Route::get('/reports/export/combined-pdf', [ReportsController::class, 'exportCombinedPdf'])->name('reports.export.combined');

    Route::get('/settings', function () {
        $modules = auth()->user()->role->modules()->get();
        return view('modules.settings', ['modules' => $modules]);
    })->name('settings.index');
});
