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
        // Redirect to the first module this user's role has access to.
        // Hardcoding reports.index here would block Cashier users (who don't
        // have the Reports module) with a 403 whenever they hit / or /dashboard.
        $user = auth()->user();
        $firstModule = $user?->role?->modules()->orderBy('sort_order')->first();

        if ($firstModule && \Illuminate\Support\Facades\Route::has($firstModule->route)) {
            return redirect()->route($firstModule->route);
        }

        // Absolute fallback — should never be reached for a properly seeded DB.
        return redirect()->route('reports.index');
    }
}
