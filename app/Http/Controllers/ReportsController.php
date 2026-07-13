<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportsController extends Controller
{
    public function paymentBreakdownJson(Request $request)
    {
        $from = $request->input('from') ? Carbon::parse($request->input('from'))->startOfDay() : null;
        $to   = $request->input('to')   ? Carbon::parse($request->input('to'))->endOfDay()     : null;

        $query = Order::where('status', 'completed')->whereNotNull('payment_method')
                      ->select('payment_method',
                               DB::raw('COUNT(*) as order_count'),
                               DB::raw('SUM(total) as total_revenue'))
                      ->groupBy('payment_method');

        if ($from && $to) {
            $query->whereBetween('created_at', [$from, $to]);
        }

        $breakdown  = $query->get();
        $totalCount = $breakdown->sum('order_count');

        return response()->json([
            'breakdown'   => $breakdown->map(fn($pm) => [
                'method'        => $pm->payment_method,
                'label'         => ucfirst(str_replace('_', ' ', $pm->payment_method)),
                'order_count'   => $pm->order_count,
                'total_revenue' => $pm->total_revenue,
                'pct'           => $totalCount > 0 ? round(($pm->order_count / $totalCount) * 100) : 0,
            ]),
            'total_count'   => $totalCount,
            'total_revenue' => $breakdown->sum('total_revenue'),
        ]);
    }

    public function index()
    {
        $modules = $this->currentUser()->role->modules()->get();
        $data    = $this->buildSalesTableData(null, null);

        return view('modules.reports', array_merge($this->baseReportData(), compact('modules'), $data));
    }

    public function salesReport(Request $request)
    {
        $modules = $this->currentUser()->role->modules()->get();
        $from    = $request->input('from');
        $to      = $request->input('to');
        $data    = $this->buildSalesTableData($from, $to);

        return view('modules.reports', array_merge($this->baseReportData(), compact('modules'), $data));
    }

    private function buildSalesTableData(?string $fromStr, ?string $toStr): array
    {
        $from = $fromStr ? Carbon::parse($fromStr)->startOfDay() : null;
        $to   = $toStr   ? Carbon::parse($toStr)->endOfDay()     : null;

        $query = Order::where('status', 'completed')->with('table')->latest();
        if ($from && $to) {
            $query->whereBetween('created_at', [$from, $to]);
        }

        $allForRange  = (clone $query)->get();
        $rangeRevenue = $allForRange->sum('total');
        $rangeCount   = $allForRange->count();
        $rangeAvg     = $rangeCount > 0 ? round($rangeRevenue / $rangeCount, 2) : 0;

        $pmQuery = Order::where('status', 'completed')->whereNotNull('payment_method')
                        ->select('payment_method',
                                 DB::raw('COUNT(*) as order_count'),
                                 DB::raw('SUM(total) as total_revenue'))
                        ->groupBy('payment_method');
        if ($from && $to) {
            $pmQuery->whereBetween('created_at', [$from, $to]);
        }
        $rangePayments = $pmQuery->get();

        $sales = (clone $query)->paginate(15)->withQueryString();

        return compact('sales', 'rangeRevenue', 'rangeCount', 'rangeAvg', 'rangePayments', 'from', 'to');
    }

    public function exportSalesRangePdf(Request $request)
    {
        $from = $request->input('from') ? Carbon::parse($request->input('from'))->startOfDay() : Carbon::today()->startOfDay();
        $to   = $request->input('to')   ? Carbon::parse($request->input('to'))->endOfDay()     : Carbon::today()->endOfDay();

        $sales = Order::where('status', 'completed')
                      ->whereBetween('created_at', [$from, $to])
                      ->with('table')
                      ->latest()
                      ->get();

        $rangeRevenue = $sales->sum('total');
        $rangeCount   = $sales->count();
        $rangeAvg     = $rangeCount > 0 ? round($rangeRevenue / $rangeCount, 2) : 0;

        $rangePayments = Order::where('status', 'completed')
                              ->whereBetween('created_at', [$from, $to])
                              ->whereNotNull('payment_method')
                              ->select('payment_method',
                                       DB::raw('COUNT(*) as order_count'),
                                       DB::raw('SUM(total) as total_revenue'))
                              ->groupBy('payment_method')
                              ->get();

        $pdf = Pdf::loadView('reports.sales-range-pdf', [
            'sales'         => $sales,
            'rangeRevenue'  => $rangeRevenue,
            'rangeCount'    => $rangeCount,
            'rangeAvg'      => $rangeAvg,
            'rangePayments' => $rangePayments,
            'from'          => $from,
            'to'            => $to,
            'generatedAt'   => now()->format('d M Y, H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('sales-report-' . $from->format('Y-m-d') . '-to-' . $to->format('Y-m-d') . '.pdf');
    }

    private function baseReportData(): array
    {
        $totalRevenue  = Order::where('status', 'completed')->sum('total');
        $todaySales    = Order::where('status', 'completed')->whereDate('created_at', Carbon::today())->sum('total');
        $monthRevenue  = Order::where('status', 'completed')
                              ->whereYear('created_at', Carbon::now()->year)
                              ->whereMonth('created_at', Carbon::now()->month)->sum('total');
        $totalOrders   = Order::where('status', 'completed')->count();
        $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;

        $topProductRow = OrderItem::select('product_name', DB::raw('SUM(quantity) as total_qty'))
                                  ->groupBy('product_name')->orderByDesc('total_qty')->first();
        $topProduct = $topProductRow?->product_name ?? 'N/A';

        $last7Days = collect(range(6, 0))->map(function ($daysAgo) {
            $date = Carbon::today()->subDays($daysAgo);
            return [
                'label'   => $date->format('D d'),
                'revenue' => (float) Order::where('status', 'completed')->whereDate('created_at', $date)->sum('total'),
            ];
        });
        $chartLabels = $last7Days->pluck('label')->toJson();
        $chartData   = $last7Days->pluck('revenue')->toJson();

        $pendingSales = Order::whereIn('status', ['pending', 'hold', 'confirmed'])
                             ->whereHas('items')
                             ->with(['table', 'items'])
                             ->latest()
                             ->get();
        $pendingCount = $pendingSales->count();
        $pendingTotal = $pendingSales->sum('total');

        $recentSales = Order::where('status', 'completed')->with('table')->latest()->limit(20)->get();

        $topProducts = OrderItem::select(
                           'product_name',
                           DB::raw('SUM(quantity) as total_qty'),
                           DB::raw('SUM(subtotal) as total_revenue'),
                           DB::raw('MAX(product_id) as product_id')
                       )->groupBy('product_name')->orderByDesc('total_qty')->limit(10)->get()
                       ->map(function ($row) {
                           $product = Product::with('category')->find($row->product_id);
                           $row->category_name = $product?->category?->name ?? '—';
                           return $row;
                       });

        $paymentBreakdown = Order::where('status', 'completed')->whereNotNull('payment_method')
                                 ->select('payment_method',
                                          DB::raw('COUNT(*) as order_count'),
                                          DB::raw('SUM(total) as total_revenue'))
                                 ->groupBy('payment_method')->get();

        return compact(
            'totalRevenue', 'todaySales', 'monthRevenue', 'totalOrders', 'avgOrderValue', 'topProduct',
            'chartLabels', 'chartData', 'recentSales', 'topProducts', 'paymentBreakdown',
            'pendingSales', 'pendingCount', 'pendingTotal'
        );
    }

    public function exportSalesPdf()
    {
        $totalRevenue  = Order::where('status', 'completed')->sum('total');
        $todaySales    = Order::where('status', 'completed')
                              ->whereDate('created_at', Carbon::today())->sum('total');
        $monthRevenue  = Order::where('status', 'completed')
                              ->whereYear('created_at',  Carbon::now()->year)
                              ->whereMonth('created_at', Carbon::now()->month)->sum('total');
        $totalOrders   = Order::where('status', 'completed')->count();
        $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;

        $recentSales = Order::where('status', 'completed')
                            ->with('table')
                            ->latest()
                            ->limit(100)
                            ->get();

        $paymentBreakdown = Order::where('status', 'completed')
                                 ->whereNotNull('payment_method')
                                 ->select('payment_method',
                                          DB::raw('COUNT(*) as order_count'),
                                          DB::raw('SUM(total) as total_revenue'))
                                 ->groupBy('payment_method')
                                 ->get();

        $pdf = Pdf::loadView('reports.sales-pdf', [
            'totalRevenue' => $totalRevenue,
            'todaySales' => $todaySales,
            'monthRevenue' => $monthRevenue,
            'totalOrders' => $totalOrders,
            'avgOrderValue' => $avgOrderValue,
            'recentSales' => $recentSales,
            'paymentBreakdown' => $paymentBreakdown,
            'generatedAt' => now()->format('d M Y, H:i')
        ])->setPaper('a4', 'landscape');

        return $pdf->download('sales-report-' . now()->format('Y-m-d-His') . '.pdf');
    }

    public function exportProductsPdf()
    {
        $topProducts = OrderItem::select(
                           'product_name',
                           DB::raw('SUM(quantity) as total_qty'),
                           DB::raw('SUM(subtotal) as total_revenue'),
                           DB::raw('MAX(product_id) as product_id')
                       )
                       ->groupBy('product_name')
                       ->orderByDesc('total_qty')
                       ->get()
                       ->map(function ($row) {
                           $product = Product::with('category')->find($row->product_id);
                           $row->category_name = $product?->category?->name ?? '—';
                           return $row;
                       });

        $totalRevenue = Order::where('status', 'completed')->sum('total');

        $pdf = Pdf::loadView('reports.products-pdf', [
            'topProducts' => $topProducts,
            'totalRevenue' => $totalRevenue,
            'generatedAt' => now()->format('d M Y, H:i')
        ])->setPaper('a4', 'landscape');

        return $pdf->download('products-report-' . now()->format('Y-m-d-His') . '.pdf');
    }

    public function exportCombinedPdf()
    {
        $totalRevenue  = Order::where('status', 'completed')->sum('total');
        $todaySales    = Order::where('status', 'completed')
                              ->whereDate('created_at', Carbon::today())->sum('total');
        $monthRevenue  = Order::where('status', 'completed')
                              ->whereYear('created_at',  Carbon::now()->year)
                              ->whereMonth('created_at', Carbon::now()->month)->sum('total');
        $totalOrders   = Order::where('status', 'completed')->count();
        $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;

        $recentSales = Order::where('status', 'completed')
                            ->with('table')
                            ->latest()
                            ->limit(50)
                            ->get();

        $topProducts = OrderItem::select(
                           'product_name',
                           DB::raw('SUM(quantity) as total_qty'),
                           DB::raw('SUM(subtotal) as total_revenue'),
                           DB::raw('MAX(product_id) as product_id')
                       )
                       ->groupBy('product_name')
                       ->orderByDesc('total_qty')
                       ->limit(15)
                       ->get()
                       ->map(function ($row) {
                           $product = Product::with('category')->find($row->product_id);
                           $row->category_name = $product?->category?->name ?? '—';
                           return $row;
                       });

        $paymentBreakdown = Order::where('status', 'completed')
                                 ->whereNotNull('payment_method')
                                 ->select('payment_method',
                                          DB::raw('COUNT(*) as order_count'),
                                          DB::raw('SUM(total) as total_revenue'))
                                 ->groupBy('payment_method')
                                 ->get();

        $pdf = Pdf::loadView('reports.combined-pdf', [
            'totalRevenue' => $totalRevenue,
            'todaySales' => $todaySales,
            'monthRevenue' => $monthRevenue,
            'totalOrders' => $totalOrders,
            'avgOrderValue' => $avgOrderValue,
            'recentSales' => $recentSales,
            'topProducts' => $topProducts,
            'paymentBreakdown' => $paymentBreakdown,
            'generatedAt' => now()->format('d M Y, H:i')
        ])->setPaper('a4', 'landscape');

        return $pdf->download('complete-report-' . now()->format('Y-m-d-His') . '.pdf');
    }
}
