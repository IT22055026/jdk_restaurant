<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Module;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = $this->currentUser();
        $role = $user->role;
        $modules = $role->modules()->get();

        // Stat card data
        $totalSales    = Order::where('status', 'completed')->sum('total');
        $activeOrders  = Order::whereIn('status', ['pending', 'confirmed', 'hold'])->count();
        $inventoryItems = Product::where('status', 'active')->count();
        $activeUsers   = User::where('status', 'active')->count() ?: User::count();

        // Extra stats
        $todaySales    = Order::where('status', 'completed')
                              ->whereDate('created_at', Carbon::today())
                              ->sum('total');
        $monthRevenue  = Order::where('status', 'completed')
                              ->whereYear('created_at', Carbon::now()->year)
                              ->whereMonth('created_at', Carbon::now()->month)
                              ->sum('total');

        return view('dashboard.index', [
            'user' => $user,
            'role' => $role,
            'modules' => $modules,
            'totalSales' => $totalSales,
            'activeOrders' => $activeOrders,
            'inventoryItems' => $inventoryItems,
            'activeUsers' => $activeUsers,
            'todaySales' => $todaySales,
            'monthRevenue' => $monthRevenue,
        ]);
    }
}
