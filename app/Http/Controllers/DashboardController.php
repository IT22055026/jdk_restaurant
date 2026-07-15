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
        // The dashboard's stats/analytics now live on the Report page — this route
        // stays around only because it's still linked from the navbar/breadcrumbs.
        return redirect()->route('reports.index');
    }
}
