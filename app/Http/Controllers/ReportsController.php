<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Expense;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\IngredientStockMovement;
use App\Models\Wastage;
use App\Support\BusinessDay;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportsController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    //  Shared helper — resolves filter bounds from ANY combination
    //  of ?preset=, ?from=, ?to=, or ?date= query params.
    //  Returns an array:
    //    [ 'isFiltered', 'filterFrom', 'filterTo', 'filterLabel',
    //      'boundsStart', 'boundsEnd', 'preset' ]
    // ─────────────────────────────────────────────────────────────
    private function resolveFilter(Request $request): array
    {
        $today     = BusinessDay::today();
        $preset    = $request->input('preset');
        $fromParam = $request->input('from');
        $toParam   = $request->input('to');
        $dateParam = $request->input('date'); // single-day legacy param

        $isFiltered  = false;
        $filterFrom  = null;
        $filterTo    = null;
        $filterLabel = 'All Time';
        $boundsStart = null;
        $boundsEnd   = null;

        if ($preset === 'today') {
            $isFiltered  = true;
            $filterFrom  = $today->format('Y-m-d');
            $filterTo    = $today->format('Y-m-d');
            $filterLabel = 'Today (' . $today->format('d M Y') . ')';

        } elseif ($preset === 'yesterday') {
            $yesterday   = $today->copy()->subDay();
            $isFiltered  = true;
            $filterFrom  = $yesterday->format('Y-m-d');
            $filterTo    = $yesterday->format('Y-m-d');
            $filterLabel = 'Yesterday (' . $yesterday->format('d M Y') . ')';

        } elseif ($preset === 'week') {
            $weekStart   = $today->copy()->subDays(6);
            $isFiltered  = true;
            $filterFrom  = $weekStart->format('Y-m-d');
            $filterTo    = $today->format('Y-m-d');
            $filterLabel = 'Last 7 Days (' . $weekStart->format('d M') . ' — ' . $today->format('d M Y') . ')';

        } elseif ($preset === 'month') {
            [$mStart, $mEnd] = BusinessDay::monthBoundsFor($today);
            $isFiltered  = true;
            $filterFrom  = $mStart->format('Y-m-d');
            $filterTo    = $mEnd->format('Y-m-d');
            $filterLabel = 'This Month (' . $today->format('F Y') . ')';

        } elseif ($fromParam && $toParam) {
            // Explicit from/to (custom date range picker)
            $isFiltered  = true;
            $filterFrom  = $fromParam;
            $filterTo    = $toParam;
            $filterLabel = $filterFrom === $filterTo
                ? Carbon::parse($filterFrom)->format('d M Y')
                : Carbon::parse($filterFrom)->format('d M Y') . ' — ' . Carbon::parse($filterTo)->format('d M Y');

        } elseif ($fromParam) {
            // Single date via ?from= (Flatpickr single-day selection)
            $isFiltered  = true;
            $filterFrom  = $fromParam;
            $filterTo    = $fromParam;
            $filterLabel = Carbon::parse($filterFrom)->format('d M Y');

        } elseif ($dateParam) {
            // Legacy single ?date= param
            // Handle Flatpickr range strings like "2026-08-20 to 2026-08-24"
            if (str_contains($dateParam, ' to ')) {
                [$filterFrom, $filterTo] = array_map('trim', explode(' to ', $dateParam, 2));
            } else {
                $filterFrom = $filterTo = $dateParam;
            }
            $isFiltered  = true;
            $filterLabel = $filterFrom === $filterTo
                ? Carbon::parse($filterFrom)->format('d M Y')
                : Carbon::parse($filterFrom)->format('d M Y') . ' — ' . Carbon::parse($filterTo)->format('d M Y');
        }

        if ($isFiltered && $filterFrom && $filterTo) {
            [$boundsStart, $boundsEnd] = BusinessDay::boundsBetween($filterFrom, $filterTo);
        }

        return compact('isFiltered', 'filterFrom', 'filterTo', 'filterLabel',
                       'boundsStart', 'boundsEnd', 'preset', 'today');
    }

    // ─────────────────────────────────────────────────────────────
    //  Payment breakdown JSON (used by Flatpickr AJAX filter)
    // ─────────────────────────────────────────────────────────────
    public function paymentBreakdownJson(Request $request)
    {
        $filter = $this->resolveFilter($request);

        $query = Order::where('status', 'completed')
                      ->whereNotNull('payment_method')
                      ->select('payment_method',
                               DB::raw('COUNT(*) as order_count'),
                               DB::raw('SUM(total) as total_revenue'))
                      ->groupBy('payment_method');

        if ($filter['isFiltered']) {
            $query->whereBetween('created_at', [$filter['boundsStart'], $filter['boundsEnd']]);
        }

        $breakdown  = $query->get();
        $totalCount = $breakdown->sum('order_count');
        $labels     = ['pickme' => 'PickMe', 'uber' => 'Uber'];

        return response()->json([
            'breakdown'     => $breakdown->map(fn($pm) => [
                'method'        => $pm->payment_method,
                'label'         => $labels[$pm->payment_method] ?? ucfirst(str_replace('_', ' ', $pm->payment_method)),
                'order_count'   => $pm->order_count,
                'total_revenue' => $pm->total_revenue,
                'pct'           => $totalCount > 0 ? round(($pm->order_count / $totalCount) * 100) : 0,
            ]),
            'total_count'   => $totalCount,
            'total_revenue' => $breakdown->sum('total_revenue'),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  Dashboard index
    // ─────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $modules = $this->currentUser()->role->modules()->get();
        return view('modules.reports', array_merge($this->baseReportData($request), compact('modules')));
    }

    // ─────────────────────────────────────────────────────────────
    //  Core dashboard data builder
    // ─────────────────────────────────────────────────────────────
    private function baseReportData(Request $request): array
    {
        $filter = $this->resolveFilter($request);

        [
            'isFiltered'  => $isFiltered,
            'filterFrom'  => $filterFrom,
            'filterTo'    => $filterTo,
            'filterLabel' => $filterLabel,
            'boundsStart' => $boundsStart,
            'boundsEnd'   => $boundsEnd,
            'preset'      => $preset,
            'today'       => $today,
        ] = $filter;

        // Always-available global metrics (lifetime totals, chart data, etc.)
        $lifetimeRevenue = (float) Order::where('status', 'completed')->sum('total');
        $lifetimeOrders  = Order::where('status', 'completed')->count();
        [$monthStart, $monthEnd] = BusinessDay::monthBoundsFor($today);
        $todaySales   = (float) Order::where('status', 'completed')->businessDay($today)->sum('total');
        $monthRevenue = (float) Order::where('status', 'completed')
                                     ->whereBetween('created_at', [$monthStart, $monthEnd])->sum('total');

        // ── Filtered or all-time metrics ──────────────────────────
        if ($isFiltered) {
            $totalRevenue  = (float) Order::where('status', 'completed')
                                          ->whereBetween('created_at', [$boundsStart, $boundsEnd])->sum('total');
            $totalOrders   = Order::where('status', 'completed')
                                   ->whereBetween('created_at', [$boundsStart, $boundsEnd])->count();
            $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;

            $recentSales = Order::where('status', 'completed')
                                ->whereBetween('created_at', [$boundsStart, $boundsEnd])
                                ->latest()->limit(50)->get();

            $pendingSales = Order::whereIn('status', ['pending', 'hold', 'confirmed'])
                                 ->whereBetween('created_at', [$boundsStart, $boundsEnd])
                                 ->whereHas('items')->with('items')->latest()->get();

            $topProducts = OrderItem::whereHas('order', function ($q) use ($boundsStart, $boundsEnd) {
                                    $q->where('status', 'completed')
                                      ->whereBetween('created_at', [$boundsStart, $boundsEnd]);
                                })
                                ->select('product_name',
                                         DB::raw('SUM(quantity) as total_qty'),
                                         DB::raw('SUM(subtotal) as total_revenue'),
                                         DB::raw('MAX(product_id) as product_id'))
                                ->groupBy('product_name')->orderByDesc('total_qty')->limit(15)->get()
                                ->map(function ($row) {
                                    $p = Product::with('category')->find($row->product_id);
                                    $row->category_name = $p?->category?->name ?? '—';
                                    return $row;
                                });

            $paymentBreakdown = Order::where('status', 'completed')
                                     ->whereBetween('created_at', [$boundsStart, $boundsEnd])
                                     ->whereNotNull('payment_method')
                                     ->select('payment_method',
                                              DB::raw('COUNT(*) as order_count'),
                                              DB::raw('SUM(total) as total_revenue'))
                                     ->groupBy('payment_method')->get();

            $totalExpenses  = (float) Expense::whereBetween('expense_date',
                                         [Carbon::parse($filterFrom)->format('Y-m-d'),
                                          Carbon::parse($filterTo)->format('Y-m-d')])->sum('amount');
            $totalPurchases = (float) Purchase::whereBetween('purchase_date',
                                         [Carbon::parse($filterFrom)->format('Y-m-d'),
                                          Carbon::parse($filterTo)->format('Y-m-d')])->sum('amount');

            $stockMovements      = StockMovement::with('product')
                                       ->whereBetween('created_at', [$boundsStart, $boundsEnd])
                                       ->latest()->limit(30)->get();
            $ingredientMovements = IngredientStockMovement::with('ingredient')
                                       ->whereBetween('created_at', [$boundsStart, $boundsEnd])
                                       ->latest()->limit(30)->get();
            $wastages            = Wastage::with('product')
                                       ->whereBetween('created_at', [$boundsStart, $boundsEnd])
                                       ->latest()->get();
        } else {
            // All-time
            $totalRevenue  = $lifetimeRevenue;
            $totalOrders   = $lifetimeOrders;
            $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;

            $recentSales = Order::where('status', 'completed')->latest()->limit(20)->get();

            $pendingSales = Order::whereIn('status', ['pending', 'hold', 'confirmed'])
                                 ->whereHas('items')->with('items')->latest()->get();

            $topProducts = OrderItem::whereHas('order', fn($q) => $q->where('status', 'completed'))
                                ->select('product_name',
                                         DB::raw('SUM(quantity) as total_qty'),
                                         DB::raw('SUM(subtotal) as total_revenue'),
                                         DB::raw('MAX(product_id) as product_id'))
                                ->groupBy('product_name')->orderByDesc('total_qty')->limit(10)->get()
                                ->map(function ($row) {
                                    $p = Product::with('category')->find($row->product_id);
                                    $row->category_name = $p?->category?->name ?? '—';
                                    return $row;
                                });

            $paymentBreakdown = Order::where('status', 'completed')
                                     ->whereNotNull('payment_method')
                                     ->select('payment_method',
                                              DB::raw('COUNT(*) as order_count'),
                                              DB::raw('SUM(total) as total_revenue'))
                                     ->groupBy('payment_method')->get();

            $totalExpenses  = (float) Expense::sum('amount');
            $totalPurchases = (float) Purchase::sum('amount');

            $stockMovements      = StockMovement::with('product')->latest()->limit(20)->get();
            $ingredientMovements = IngredientStockMovement::with('ingredient')->latest()->limit(20)->get();
            $wastages            = Wastage::with('product')->latest()->limit(20)->get();
        }

        $pendingCount    = $pendingSales->count();
        $pendingTotal    = (float) $pendingSales->sum('total');
        $wastageQuantity = $wastages->sum('quantity');
        $totalUnitsSold  = $topProducts->sum('total_qty');
        $topProduct      = $topProducts->first()?->product_name ?? 'N/A';
        $totalOutgoings  = $totalExpenses + $totalPurchases;
        $netProfit       = $totalRevenue - $totalOutgoings;

        // Month/today expenses (for footer cards — always global)
        $monthExpenses  = (float) Expense::whereBetween('expense_date',
                              [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])->sum('amount');
        $todayExpenses  = (float) Expense::whereDate('expense_date', $today->format('Y-m-d'))->sum('amount');
        $monthPurchases = (float) Purchase::whereBetween('purchase_date',
                              [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])->sum('amount');
        $todayPurchases = (float) Purchase::whereDate('purchase_date', $today->format('Y-m-d'))->sum('amount');
        $monthOutgoings = $monthExpenses + $monthPurchases;
        $monthNetProfit = $monthRevenue - $monthOutgoings;

        $activeOrders   = Order::whereIn('status', ['pending', 'confirmed', 'hold'])->count();
        $inventoryItems = Product::where('status', 'active')->count();
        $activeUsers    = User::where('status', 'active')->count() ?: User::count();

        // 7-day revenue chart (always last 7 business days, regardless of filter)
        $last7Days = collect(range(6, 0))->map(function ($daysAgo) use ($today) {
            $date = $today->copy()->subDays($daysAgo);
            return [
                'label'   => $date->format('D d'),
                'revenue' => (float) Order::where('status', 'completed')->businessDay($date)->sum('total'),
            ];
        });
        $chartLabels = $last7Days->pluck('label')->toJson();
        $chartData   = $last7Days->pluck('revenue')->toJson();

        // Low stock alerts (always current stock, regardless of filter)
        $allProducts         = Product::with('category')->get();
        $lowStockProducts    = $allProducts->filter(fn($p) => $p->isLowStock());
        $allIngredients      = Ingredient::all();
        $lowStockIngredients = $allIngredients->filter(fn($i) => $i->isLowStock());

        return compact(
            'totalRevenue', 'todaySales', 'monthRevenue', 'totalOrders', 'avgOrderValue', 'topProduct',
            'activeOrders', 'inventoryItems', 'activeUsers',
            'chartLabels', 'chartData', 'recentSales', 'topProducts', 'paymentBreakdown',
            'pendingSales', 'pendingCount', 'pendingTotal',
            'totalExpenses', 'monthExpenses', 'todayExpenses',
            'totalPurchases', 'monthPurchases', 'todayPurchases',
            'totalOutgoings', 'monthOutgoings', 'netProfit', 'monthNetProfit',
            'isFiltered', 'filterFrom', 'filterTo', 'filterLabel', 'preset', 'today',
            'stockMovements', 'ingredientMovements', 'wastages', 'wastageQuantity',
            'totalUnitsSold', 'lowStockProducts', 'lowStockIngredients'
        );
    }

    // ─────────────────────────────────────────────────────────────
    //  Date-range sales PDF (existing route, not used by dashboard)
    // ─────────────────────────────────────────────────────────────
    public function exportSalesRangePdf(Request $request)
    {
        $filter = $this->resolveFilter($request);
        [$from, $to] = $filter['isFiltered']
            ? [$filter['boundsStart'], $filter['boundsEnd']]
            : [BusinessDay::boundsFor(BusinessDay::today())[0], BusinessDay::boundsFor(BusinessDay::today())[1]];

        $sales = Order::where('status', 'completed')
                      ->whereBetween('created_at', [$from, $to])->latest()->get();

        $rangeRevenue  = $sales->sum('total');
        $rangeCount    = $sales->count();
        $rangeAvg      = $rangeCount > 0 ? round($rangeRevenue / $rangeCount, 2) : 0;

        $rangePayments = Order::where('status', 'completed')
                              ->whereBetween('created_at', [$from, $to])
                              ->whereNotNull('payment_method')
                              ->select('payment_method',
                                       DB::raw('COUNT(*) as order_count'),
                                       DB::raw('SUM(total) as total_revenue'))
                              ->groupBy('payment_method')->get();

        $pdf = Pdf::loadView('reports.sales-range-pdf', [
            'sales'        => $sales,
            'rangeRevenue' => $rangeRevenue,
            'rangeCount'   => $rangeCount,
            'rangeAvg'     => $rangeAvg,
            'rangePayments'=> $rangePayments,
            'from'         => $from,
            'to'           => $to,
            'generatedAt'  => now()->format('d M Y, H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('sales-report-' . $from->format('Y-m-d') . '-to-' . $to->format('Y-m-d') . '.pdf');
    }

    // ─────────────────────────────────────────────────────────────
    //  Sales PDF — respects ALL filter params including preset
    // ─────────────────────────────────────────────────────────────
    public function exportSalesPdf(Request $request)
    {
        $filter      = $this->resolveFilter($request);
        $isFiltered  = $filter['isFiltered'];
        $filterFrom  = $filter['filterFrom'];
        $filterTo    = $filter['filterTo'];
        $filterLabel = $filter['filterLabel'];
        $boundsStart = $filter['boundsStart'];
        $boundsEnd   = $filter['boundsEnd'];

        if ($isFiltered) {
            $sales       = Order::where('status', 'completed')
                                 ->whereBetween('created_at', [$boundsStart, $boundsEnd])
                                 ->latest()->get();
            $titlePeriod = $filterLabel;
        } else {
            $sales       = Order::where('status', 'completed')->latest()->limit(200)->get();
            $titlePeriod = 'All Time';
        }

        $totalRevenue  = (float) $sales->sum('total');
        $totalOrders   = $sales->count();
        $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;

        $paymentQuery = Order::where('status', 'completed')->whereNotNull('payment_method');
        if ($isFiltered) {
            $paymentQuery->whereBetween('created_at', [$boundsStart, $boundsEnd]);
        }
        $paymentBreakdown = $paymentQuery->select('payment_method',
                                             DB::raw('COUNT(*) as order_count'),
                                             DB::raw('SUM(total) as total_revenue'))
                                         ->groupBy('payment_method')->get();

        $pdf = Pdf::loadView('reports.sales-pdf', [
            'totalRevenue'     => $totalRevenue,
            'todaySales'       => $totalRevenue,   // for PDF layout compatibility
            'monthRevenue'     => $totalRevenue,
            'totalOrders'      => $totalOrders,
            'avgOrderValue'    => $avgOrderValue,
            'recentSales'      => $sales,
            'paymentBreakdown' => $paymentBreakdown,
            'periodLabel'      => $titlePeriod,
            'filterFrom'       => $filterFrom,
            'filterTo'         => $filterTo,
            'generatedAt'      => now()->format('d M Y, H:i'),
        ])->setPaper('a4', 'landscape');

        $filename = 'sales-report-' . ($isFiltered ? $filterFrom . '-to-' . $filterTo . '-' : 'all-time-') . now()->format('Ymd') . '.pdf';
        return $pdf->download($filename);
    }

    // ─────────────────────────────────────────────────────────────
    //  Products PDF — respects ALL filter params including preset
    // ─────────────────────────────────────────────────────────────
    public function exportProductsPdf(Request $request)
    {
        $filter      = $this->resolveFilter($request);
        $isFiltered  = $filter['isFiltered'];
        $filterLabel = $filter['filterLabel'];
        $boundsStart = $filter['boundsStart'];
        $boundsEnd   = $filter['boundsEnd'];

        $query = OrderItem::whereHas('order', function ($q) use ($isFiltered, $boundsStart, $boundsEnd) {
            $q->where('status', 'completed');
            if ($isFiltered) {
                $q->whereBetween('created_at', [$boundsStart, $boundsEnd]);
            }
        });

        $topProducts = $query->select('product_name',
                                  DB::raw('SUM(quantity) as total_qty'),
                                  DB::raw('SUM(subtotal) as total_revenue'),
                                  DB::raw('MAX(product_id) as product_id'))
                             ->groupBy('product_name')->orderByDesc('total_qty')->get()
                             ->map(function ($row) {
                                 $p = Product::with('category')->find($row->product_id);
                                 $row->category_name = $p?->category?->name ?? '—';
                                 return $row;
                             });

        $totalRevenue = (float) $topProducts->sum('total_revenue');

        $pdf = Pdf::loadView('reports.products-pdf', [
            'topProducts'  => $topProducts,
            'totalRevenue' => $totalRevenue,
            'periodLabel'  => $isFiltered ? $filterLabel : 'All Time',
            'generatedAt'  => now()->format('d M Y, H:i'),
        ])->setPaper('a4', 'landscape');

        $filename = 'products-report-' . ($isFiltered ? ($filter['filterFrom'] . '-to-' . $filter['filterTo'] . '-') : 'all-time-') . now()->format('Ymd') . '.pdf';
        return $pdf->download($filename);
    }

    // ─────────────────────────────────────────────────────────────
    //  Stock PDF (snapshot of current stock, no date filter needed)
    // ─────────────────────────────────────────────────────────────
    public function exportStockPdf()
    {
        $products    = Product::with('category', 'ingredients')->orderBy('name')->get();
        $ingredients = Ingredient::orderBy('name')->get();

        $lowStockProducts     = $products->filter(fn($p) => $p->isLowStock())->count();
        $outOfStockProducts   = $products->filter(fn($p) => !$p->is_unlimited_stock && $p->availableStock() === 0)->count();
        $lowStockIngredients  = $ingredients->filter(fn($i) => $i->isLowStock())->count();
        $outOfStockIngredients= $ingredients->filter(fn($i) => (float) $i->quantity <= 0)->count();

        $pdf = Pdf::loadView('reports.stock-pdf', [
            'products'              => $products,
            'ingredients'           => $ingredients,
            'totalProducts'         => $products->count(),
            'lowStockProducts'      => $lowStockProducts,
            'outOfStockProducts'    => $outOfStockProducts,
            'totalIngredients'      => $ingredients->count(),
            'lowStockIngredients'   => $lowStockIngredients,
            'outOfStockIngredients' => $outOfStockIngredients,
            'generatedAt'           => now()->format('d M Y, H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('stock-report-' . now()->format('Y-m-d') . '.pdf');
    }

    // ─────────────────────────────────────────────────────────────
    //  Combined PDF — respects ALL filter params including preset
    // ─────────────────────────────────────────────────────────────
    public function exportCombinedPdf(Request $request)
    {
        $filter      = $this->resolveFilter($request);
        $isFiltered  = $filter['isFiltered'];
        $filterFrom  = $filter['filterFrom'];
        $filterTo    = $filter['filterTo'];
        $filterLabel = $filter['filterLabel'];
        $boundsStart = $filter['boundsStart'];
        $boundsEnd   = $filter['boundsEnd'];

        $orderQuery = Order::where('status', 'completed');
        $itemQuery  = OrderItem::whereHas('order', function ($q) use ($isFiltered, $boundsStart, $boundsEnd) {
            $q->where('status', 'completed');
            if ($isFiltered) {
                $q->whereBetween('created_at', [$boundsStart, $boundsEnd]);
            }
        });

        if ($isFiltered) {
            $orderQuery->whereBetween('created_at', [$boundsStart, $boundsEnd]);
        }

        $sales         = $orderQuery->latest()->limit(100)->get();
        $totalRevenue  = (float) $sales->sum('total');
        $totalOrders   = $sales->count();
        $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;

        $topProducts = $itemQuery->select('product_name',
                                     DB::raw('SUM(quantity) as total_qty'),
                                     DB::raw('SUM(subtotal) as total_revenue'),
                                     DB::raw('MAX(product_id) as product_id'))
                                 ->groupBy('product_name')->orderByDesc('total_qty')->limit(15)->get()
                                 ->map(function ($row) {
                                     $p = Product::with('category')->find($row->product_id);
                                     $row->category_name = $p?->category?->name ?? '—';
                                     return $row;
                                 });

        $pmQuery = Order::where('status', 'completed')->whereNotNull('payment_method');
        if ($isFiltered) {
            $pmQuery->whereBetween('created_at', [$boundsStart, $boundsEnd]);
        }
        $paymentBreakdown = $pmQuery->select('payment_method',
                                        DB::raw('COUNT(*) as order_count'),
                                        DB::raw('SUM(total) as total_revenue'))
                                    ->groupBy('payment_method')->get();

        $pdf = Pdf::loadView('reports.combined-pdf', [
            'totalRevenue'     => $totalRevenue,
            'todaySales'       => $totalRevenue,
            'monthRevenue'     => $totalRevenue,
            'totalOrders'      => $totalOrders,
            'avgOrderValue'    => $avgOrderValue,
            'recentSales'      => $sales,
            'topProducts'      => $topProducts,
            'paymentBreakdown' => $paymentBreakdown,
            'periodLabel'      => $isFiltered ? $filterLabel : 'All Time',
            'filterFrom'       => $filterFrom,
            'filterTo'         => $filterTo,
            'generatedAt'      => now()->format('d M Y, H:i'),
        ])->setPaper('a4', 'landscape');

        $filename = 'complete-report-' . ($isFiltered ? $filterFrom . '-to-' . $filterTo . '-' : 'all-time-') . now()->format('Ymd') . '.pdf';
        return $pdf->download($filename);
    }
}
