@extends('layouts.app')

@section('title', 'Dashboard & Reports')

@section('content')
<div class="space-y-6">
    <!-- Page header -->
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            @section('breadcrumb')
                <nav class="text-sm text-gray-600" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-2">
                        <li class="text-gray-900 font-semibold">Dashboard</li>
                    </ol>
                </nav>
                <h1 class="text-4xl font-bold text-gray-900">Dashboard</h1>
            @endsection
            <p class="text-gray-600 mt-1 text-sm">Business performance, sales analytics, product movements, and inventory</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <span class="text-xs text-gray-500 font-medium bg-gray-100 px-3 py-1.5 rounded-lg">
                <i class="fas fa-clock mr-1 text-gray-400"></i>As of {{ now()->format('d M Y, H:i') }}
            </span>
            @php
                // Build export query params — always use resolved from/to so PDFs match the view.
                // Presets get translated to explicit from/to dates so the export controller
                // doesn't need to know about the original preset.
                $exportParams = [];
                if ($isFiltered && $filterFrom && $filterTo) {
                    $exportParams = ['from' => $filterFrom, 'to' => $filterTo];
                }
            @endphp
            <div class="flex gap-2 flex-wrap">
                <a href="{{ route('reports.export.sales', $exportParams) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition text-xs font-semibold shadow-xs">
                    <i class="fas fa-file-pdf"></i> Sales PDF
                </a>
                <a href="{{ route('reports.export.products', $exportParams) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-amber-500 text-white rounded-xl hover:bg-amber-600 transition text-xs font-semibold shadow-xs">
                    <i class="fas fa-file-pdf"></i> Products PDF
                </a>
                <a href="{{ route('reports.export.stock') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition text-xs font-semibold shadow-xs">
                    <i class="fas fa-boxes-stacked"></i> Stock Report
                </a>
                <a href="{{ route('reports.export.combined', $exportParams) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-800 text-white rounded-xl hover:bg-slate-900 transition text-xs font-semibold shadow-xs">
                    <i class="fas fa-file-lines"></i> Complete PDF
                </a>
            </div>
        </div>
    </div>

    <!-- ── DATE FILTER BAR ── -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm">
        <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
            
            <!-- Quick Presets -->
            <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider mr-1 flex items-center gap-1">
                    <i class="fas fa-filter text-blue-500"></i>Date Filter:
                </span>
                
                <a href="{{ route('reports.index', ['preset' => 'today']) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ ($preset === 'today' || ($filterFrom === $today->format('Y-m-d') && $filterTo === $today->format('Y-m-d') && !$preset && $isFiltered)) ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                   Today
                </a>
                
                <a href="{{ route('reports.index', ['preset' => 'yesterday']) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $preset === 'yesterday' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                   Yesterday
                </a>
                
                <a href="{{ route('reports.index', ['preset' => 'week']) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $preset === 'week' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                   Last 7 Days
                </a>
                
                <a href="{{ route('reports.index', ['preset' => 'month']) }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $preset === 'month' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                   This Month
                </a>
                
                <a href="{{ route('reports.index') }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ (!$isFiltered && !$preset) ? 'bg-slate-800 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                   All Time
                </a>
            </div>

            <!-- Custom Date Selector Form -->
            {{-- Flatpickr populates #fpFrom and #fpTo hidden inputs on selection --}}
            <form id="dashDateForm" action="{{ route('reports.index') }}" method="GET" class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                <input type="hidden" id="fpFrom" name="from" value="{{ $filterFrom ?? '' }}">
                <input type="hidden" id="fpTo"   name="to"   value="{{ $filterTo ?? '' }}">
                <div class="relative flex items-center bg-slate-50 border border-slate-300 rounded-xl px-3 py-1.5 focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500 transition">
                    <i class="fas fa-calendar-day text-slate-400 text-xs mr-2"></i>
                    <input type="text" id="dashboardDatePicker" placeholder="Select date or range"
                           value="{{ ($filterFrom && !$preset) ? ($filterFrom === $filterTo ? $filterFrom : $filterFrom . ' to ' . $filterTo) : '' }}"
                           class="bg-transparent text-xs font-semibold text-slate-800 outline-none w-48 cursor-pointer" readonly>
                </div>

                <button type="submit" id="dashApplyBtn" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-4 py-2 rounded-xl transition shadow-xs flex items-center gap-1.5">
                    <i class="fas fa-magnifying-glass"></i>
                    <span>Apply</span>
                </button>

                @if($isFiltered)
                    <a href="{{ route('reports.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold px-3 py-2 rounded-xl transition" title="Clear Date Filter">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </form>

        </div>

        <!-- Active Filter Indicator Banner -->
        @if($isFiltered)
            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-blue-50 text-blue-800 border border-blue-200">
                        <i class="fas fa-calendar-check text-blue-600"></i>
                        Showing Data For: {{ $filterLabel }}
                    </span>
                    <span class="text-xs text-slate-500">Sales revenue, orders, products sold, and stock activity for this period</span>
                </div>
                <a href="{{ route('reports.index') }}" class="text-xs text-blue-600 hover:text-blue-800 font-semibold underline">
                    Reset to All Time
                </a>
            </div>
        @endif
    </div>

    <!-- ── 1. FINANCIAL SUMMARY & PROFIT ── -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Gross Sales -->
        <div class="bg-gradient-to-br from-blue-600 to-indigo-700 text-white rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-xs font-semibold uppercase tracking-wider">{{ $isFiltered ? 'Period Revenue' : 'Total Sales' }}</p>
                    <p class="text-2xl font-black mt-1">LKR {{ number_format($totalRevenue, 2) }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center">
                    <i class="fas fa-sack-dollar text-white"></i>
                </div>
            </div>
            <p class="text-[11px] text-blue-100/80 mt-2">{{ $totalOrders }} completed orders</p>
        </div>

        <!-- Purchases -->
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 text-white rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-amber-100 text-xs font-semibold uppercase tracking-wider">{{ $isFiltered ? 'Period Purchases' : 'Total Purchases' }}</p>
                    <p class="text-2xl font-black mt-1">LKR {{ number_format($totalPurchases, 2) }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center">
                    <i class="fas fa-basket-shopping text-white"></i>
                </div>
            </div>
            <p class="text-[11px] text-amber-100/80 mt-2">Raw materials & ingredients</p>
        </div>

        <!-- Expenses -->
        <div class="bg-gradient-to-br from-rose-500 to-rose-600 text-white rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-rose-100 text-xs font-semibold uppercase tracking-wider">{{ $isFiltered ? 'Period Expenses' : 'Total Expenses' }}</p>
                    <p class="text-2xl font-black mt-1">LKR {{ number_format($totalExpenses, 2) }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center">
                    <i class="fas fa-receipt text-white"></i>
                </div>
            </div>
            <p class="text-[11px] text-rose-100/80 mt-2">Operational outgoings</p>
        </div>

        <!-- Net Profit -->
        <div class="bg-gradient-to-br {{ $netProfit >= 0 ? 'from-emerald-600 to-emerald-700' : 'from-red-600 to-red-700' }} text-white rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-emerald-100 text-xs font-semibold uppercase tracking-wider">Net Profit</p>
                    <p class="text-2xl font-black mt-1">LKR {{ number_format($netProfit, 2) }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center">
                    <i class="fas fa-coins text-white"></i>
                </div>
            </div>
            <p class="text-[11px] text-emerald-100/80 mt-2">Sales minus (Purchases + Expenses)</p>
        </div>

        <!-- Summary KPI -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div>
                <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider">Avg Order Value</p>
                <p class="text-2xl font-black text-slate-900 mt-1">LKR {{ number_format($avgOrderValue, 2) }}</p>
            </div>
            <div class="flex items-center justify-between text-xs text-slate-500 pt-2 border-t border-slate-100">
                <span>Top Product:</span>
                <span class="font-bold text-slate-800 truncate max-w-[120px]" title="{{ $topProduct }}">{{ $topProduct }}</span>
            </div>
        </div>
    </div>

    <!-- ── 2. SALES, COMPLETED ORDERS & PAYMENT BREAKDOWN ── -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <!-- Sales / Completed Orders Table (2/3) -->
        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div>
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i class="fas fa-receipt text-blue-500"></i>
                        <span>{{ $isFiltered ? 'Sales for Selected Period' : 'Recent Completed Orders' }}</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $recentSales->count() }} completed orders listed</p>
                </div>
                <span class="text-xs font-bold text-blue-600 bg-blue-50 border border-blue-200 px-3 py-1 rounded-full">
                    Total: LKR {{ number_format($totalRevenue, 2) }}
                </span>
            </div>
            <div class="overflow-x-auto flex-1 max-h-[380px] overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider sticky top-0 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left">Order #</th>
                            <th class="px-4 py-3 text-left">Token</th>
                            <th class="px-4 py-3 text-left">Customer</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-center">Payment</th>
                            <th class="px-4 py-3 text-right">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentSales as $sale)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-2.5 font-mono text-xs font-bold text-slate-800">{{ $sale->order_number }}</td>
                            <td class="px-4 py-2.5 text-slate-600 font-semibold">
                                {{ $sale->token_number ? '#' . str_pad($sale->token_number, 2, '0', STR_PAD_LEFT) : '—' }}
                            </td>
                            <td class="px-4 py-2.5 text-slate-600 text-xs">{{ $sale->customer_name ?? 'Walk-in' }}</td>
                            <td class="px-4 py-2.5 text-right font-bold text-slate-900 text-xs">
                                LKR {{ number_format($sale->total, 2) }}
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                @php
                                    $pmColors = [
                                        'cash'          => 'bg-green-100 text-green-700',
                                        'card'          => 'bg-blue-100 text-blue-700',
                                        'bank_transfer' => 'bg-purple-100 text-purple-700',
                                        'mixed'         => 'bg-orange-100 text-orange-700',
                                        'pickme'        => 'bg-red-100 text-red-700',
                                        'uber'          => 'bg-slate-900 text-white',
                                    ];
                                    $pmColor = $pmColors[$sale->payment_method] ?? 'bg-slate-100 text-slate-600';
                                    $pmLabels = ['pickme' => 'PickMe', 'uber' => 'Uber', 'mixed' => 'Split'];
                                    $pmLabel = $pmLabels[$sale->payment_method] ?? ucfirst(str_replace('_', ' ', $sale->payment_method ?? '—'));
                                @endphp
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $pmColor }}">
                                    {{ $pmLabel }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-right text-slate-400 text-xs whitespace-nowrap">
                                {{ $sale->created_at->format('d M, h:i A') }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-slate-400 text-xs">No completed sales recorded for this date.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payment Methods Breakdown (1/3) -->
        <div class="xl:col-span-1 bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fas fa-credit-card text-blue-500"></i>
                    <span>Payment Methods</span>
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">{{ $totalOrders }} total transactions</p>
            </div>
            <div class="p-5 space-y-3.5 flex-1 overflow-y-auto max-h-[380px]">
                @forelse($paymentBreakdown as $pm)
                @php
                    $pmIcons = [
                        'cash'          => 'fas fa-money-bill-wave text-green-500',
                        'card'          => 'fas fa-credit-card text-blue-500',
                        'bank_transfer' => 'fas fa-university text-purple-500',
                        'mixed'         => 'fas fa-shuffle text-orange-500',
                        'pickme'        => 'fas fa-taxi text-red-500',
                        'uber'          => 'fab fa-uber text-slate-900',
                    ];
                    $icon = $pmIcons[$pm->payment_method] ?? 'fas fa-circle-question text-slate-400';
                    $pmLabels = ['pickme' => 'PickMe', 'uber' => 'Uber', 'mixed' => 'Split Payment'];
                    $pmLabel = $pmLabels[$pm->payment_method] ?? ucfirst(str_replace('_', ' ', $pm->payment_method));
                    $pct  = $totalOrders > 0 ? round(($pm->order_count / $totalOrders) * 100) : 0;
                @endphp
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200/60">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="flex items-center gap-2 text-xs font-bold text-slate-700">
                            <i class="{{ $icon }}"></i>
                            {{ $pmLabel }}
                        </span>
                        <span class="text-xs font-extrabold text-slate-900">{{ $pm->order_count }} orders</span>
                    </div>
                    <div class="flex items-center gap-2 mb-1">
                        <div class="flex-1 bg-slate-200 rounded-full h-2 overflow-hidden">
                            <div class="bg-blue-600 h-2 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                        </div>
                        <span class="text-[11px] font-bold text-slate-500 w-9 text-right">{{ $pct }}%</span>
                    </div>
                    <p class="text-[11px] font-semibold text-slate-600">LKR {{ number_format($pm->total_revenue, 2) }}</p>
                </div>
                @empty
                <p class="text-center text-slate-400 py-10 text-xs">No payment data for this period.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- ── 3. PRODUCTS & INVENTORY / STOCKS ACTIVITY ── -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

        <!-- Top Selling Products Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div>
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i class="fas fa-trophy text-amber-500"></i>
                        <span>{{ $isFiltered ? 'Products Sold in Selected Period' : 'Top Selling Products' }}</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Ranked by units sold</p>
                </div>
                <span class="text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 px-3 py-1 rounded-full">
                    {{ $totalUnitsSold }} Units Sold
                </span>
            </div>
            <div class="overflow-x-auto flex-1 max-h-[350px] overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider sticky top-0 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left">#</th>
                            <th class="px-4 py-3 text-left">Product</th>
                            <th class="px-4 py-3 text-left">Category</th>
                            <th class="px-4 py-3 text-right">Qty Sold</th>
                            <th class="px-4 py-3 text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($topProducts as $i => $prod)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-2.5 text-slate-400 font-bold text-xs">
                                @if($i === 0) <i class="fas fa-medal text-amber-400 text-sm"></i>
                                @elseif($i === 1) <i class="fas fa-medal text-slate-400 text-sm"></i>
                                @elseif($i === 2) <i class="fas fa-medal text-amber-600 text-sm"></i>
                                @else {{ $i + 1 }}
                                @endif
                            </td>
                            <td class="px-4 py-2.5 font-bold text-slate-900 text-xs">{{ $prod->product_name }}</td>
                            <td class="px-4 py-2.5 text-slate-500 text-xs">{{ $prod->category_name }}</td>
                            <td class="px-4 py-2.5 text-right font-black text-slate-900 text-xs">
                                <span class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded-lg border border-blue-100">
                                    {{ number_format($prod->total_qty) }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-right font-bold text-slate-800 text-xs">
                                LKR {{ number_format($prod->total_revenue, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-12 text-center text-slate-400 text-xs">No product sales data for this date.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Stock Movements & Wastages Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div>
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i class="fas fa-boxes-stacked text-emerald-600"></i>
                        <span>Stock &amp; Wastage Activity</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Inventory movements recorded for this period</p>
                </div>
                <div class="flex gap-2">
                    <span class="text-xs font-bold text-red-700 bg-red-50 border border-red-200 px-2.5 py-1 rounded-full">
                        {{ $wastages->count() }} Wastage Logs
                    </span>
                    <span class="text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full">
                        {{ $stockMovements->count() }} Adjustments
                    </span>
                </div>
            </div>
            <div class="overflow-x-auto flex-1 max-h-[350px] overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider sticky top-0 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left">Item / Product</th>
                            <th class="px-4 py-3 text-center">Type</th>
                            <th class="px-4 py-3 text-right">Quantity</th>
                            <th class="px-4 py-3 text-left">Reason / Note</th>
                            <th class="px-4 py-3 text-right">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <!-- Wastages First -->
                        @foreach($wastages as $w)
                        <tr class="hover:bg-red-50/50 transition-colors bg-red-50/20">
                            <td class="px-4 py-2.5 font-bold text-slate-800 text-xs">
                                {{ $w->product->name ?? 'Unknown Item' }}
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800 uppercase tracking-wider">
                                    Wastage
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-right font-black text-red-600 text-xs">
                                -{{ $w->quantity }}
                            </td>
                            <td class="px-4 py-2.5 text-slate-600 text-xs max-w-[140px] truncate" title="{{ $w->reason }}">
                                {{ $w->reason ?: ($w->notes ?: '—') }}
                            </td>
                            <td class="px-4 py-2.5 text-right text-slate-400 text-xs whitespace-nowrap">
                                {{ $w->created_at->format('d M, h:i A') }}
                            </td>
                        </tr>
                        @endforeach

                        <!-- Stock Adjustments -->
                        @foreach($stockMovements as $sm)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-2.5 font-bold text-slate-800 text-xs">
                                {{ $sm->product->name ?? ($sm->name ?? 'Item') }}
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 uppercase tracking-wider">
                                    {{ ucfirst($sm->change_type ?? 'Adjustment') }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-right font-black {{ ($sm->quantity >= 0) ? 'text-emerald-600' : 'text-rose-600' }} text-xs">
                                {{ $sm->quantity > 0 ? '+' : '' }}{{ $sm->quantity }}
                            </td>
                            <td class="px-4 py-2.5 text-slate-600 text-xs max-w-[140px] truncate" title="{{ $sm->reason }}">
                                {{ $sm->reason ?: ($sm->notes ?: '—') }}
                            </td>
                            <td class="px-4 py-2.5 text-right text-slate-400 text-xs whitespace-nowrap">
                                {{ $sm->created_at->format('d M, h:i A') }}
                            </td>
                        </tr>
                        @endforeach

                        @if($wastages->isEmpty() && $stockMovements->isEmpty())
                        <tr><td colspan="5" class="px-4 py-12 text-center text-slate-400 text-xs">No stock adjustments or wastage logs recorded for this date.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ── 4. LOW STOCK INVENTORY ALERTS & PENDING SALES ── -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <!-- Low Stock Items Alert (1/3) -->
        <div class="xl:col-span-1 bg-white rounded-2xl border border-amber-200 shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-amber-100 bg-amber-50/60 flex items-center justify-between">
                <h2 class="text-base font-bold text-amber-900 flex items-center gap-2">
                    <i class="fas fa-triangle-exclamation text-amber-600"></i>
                    <span>Low Stock Alerts</span>
                </h2>
                <span class="text-xs font-bold text-amber-800 bg-amber-200 px-2.5 py-0.5 rounded-full">
                    {{ $lowStockProducts->count() + $lowStockIngredients->count() }} items
                </span>
            </div>
            <div class="p-4 space-y-2.5 flex-1 overflow-y-auto max-h-[300px]">
                @forelse($lowStockProducts as $lp)
                <div class="flex items-center justify-between p-2.5 bg-amber-50/50 rounded-xl border border-amber-100">
                    <div>
                        <p class="text-xs font-bold text-slate-900">{{ $lp->name }}</p>
                        <p class="text-[10px] text-slate-500">{{ $lp->category->name ?? 'Product' }}</p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-black text-rose-600">{{ $lp->quantity }} left</span>
                        <span class="block text-[10px] text-slate-400">Min: {{ $lp->alert_quantity ?? 5 }}</span>
                    </div>
                </div>
                @empty
                    @if($lowStockIngredients->isEmpty())
                        <p class="text-center text-slate-400 py-8 text-xs">All inventory stock levels are healthy!</p>
                    @endif
                @endforelse

                @foreach($lowStockIngredients as $li)
                <div class="flex items-center justify-between p-2.5 bg-rose-50/50 rounded-xl border border-rose-100">
                    <div>
                        <p class="text-xs font-bold text-slate-900">{{ $li->name }}</p>
                        <p class="text-[10px] text-slate-500">Raw Ingredient ({{ $li->unit }})</p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-black text-rose-600">{{ (float) $li->quantity }} {{ $li->unit }}</span>
                        <span class="block text-[10px] text-slate-400">Min: {{ $li->alert_quantity }} {{ $li->unit }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Pending / Open Bills (2/3) -->
        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fas fa-clock text-amber-500"></i>
                    <span>Pending / Open Bills</span>
                    <span class="text-xs font-bold text-amber-800 bg-amber-100 px-2 py-0.5 rounded-full ml-1">{{ $pendingCount }} open</span>
                </h2>
                <span class="text-xs font-bold text-slate-700">Total: LKR {{ number_format($pendingTotal, 2) }}</span>
            </div>
            <div class="overflow-x-auto flex-1 max-h-[300px] overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider sticky top-0 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left">Order #</th>
                            <th class="px-4 py-3 text-left">Token</th>
                            <th class="px-4 py-3 text-left">Customer</th>
                            <th class="px-4 py-3 text-left">Items</th>
                            <th class="px-4 py-3 text-right">Bill Total</th>
                            <th class="px-4 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($pendingSales as $pending)
                        @php
                            $statusColors = [
                                'pending'   => 'bg-amber-100 text-amber-700',
                                'hold'      => 'bg-blue-100 text-blue-700',
                                'confirmed' => 'bg-green-100 text-green-700',
                            ];
                            $statusColor = $statusColors[$pending->status] ?? 'bg-slate-100 text-slate-600';
                            $itemList = $pending->items->map(fn($i) => $i->quantity . '× ' . $i->product_name)->implode(', ');
                        @endphp
                        <tr class="hover:bg-amber-50/40 transition-colors">
                            <td class="px-4 py-2.5 font-mono text-xs font-bold text-slate-800">{{ $pending->order_number }}</td>
                            <td class="px-4 py-2.5 text-slate-600 font-semibold text-xs">
                                {{ $pending->token_number ? '#' . str_pad($pending->token_number, 2, '0', STR_PAD_LEFT) : '—' }}
                            </td>
                            <td class="px-4 py-2.5 text-slate-600 text-xs">{{ $pending->customer_name ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-slate-500 text-xs max-w-[150px] truncate" title="{{ $itemList }}">
                                {{ $itemList ?: '—' }}
                            </td>
                            <td class="px-4 py-2.5 text-right font-bold text-slate-900 text-xs">
                                LKR {{ number_format($pending->total, 2) }}
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $statusColor }}">
                                    {{ ucfirst($pending->status) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-slate-400 text-xs">No pending bills right now — all clear!</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ── 5. REVENUE TREND CHART (LAST 7 DAYS) ── -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
            <i class="fas fa-chart-bar text-blue-500"></i>
            <span>Daily Revenue Trend (Last 7 Days)</span>
        </h2>
        <div style="position:relative; height:240px;">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

</div>
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    .flatpickr-calendar { border-radius: 14px !important; box-shadow: 0 8px 30px rgba(0,0,0,0.12) !important; border: 1px solid #e2e8f0 !important; }
    .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange { background: #2563eb !important; border-color: #2563eb !important; }
    .flatpickr-day.inRange { background: #dbeafe !important; border-color: #dbeafe !important; color: #1d4ed8 !important; }
    .flatpickr-day:hover { background: #eff6ff !important; border-color: #93c5fd !important; }
    .flatpickr-months .flatpickr-month { background: #2563eb !important; color: #fff !important; border-radius: 14px 14px 0 0 !important; }
    .flatpickr-current-month, .flatpickr-current-month select, .flatpickr-current-month .numInputWrapper { color: #fff !important; }
    .flatpickr-weekday { color: #2563eb !important; font-weight: 700 !important; }
    .flatpickr-prev-month svg, .flatpickr-next-month svg { fill: #fff !important; }
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const labels  = {!! $chartLabels !!};
    const data    = {!! $chartData !!};

    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Revenue (LKR)',
                data: data,
                backgroundColor: 'rgba(37, 99, 235, 0.18)',
                borderColor: '#2563eb',
                borderWidth: 2,
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ' LKR ' + ctx.parsed.y.toLocaleString('en-US', { minimumFractionDigits: 2 })
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.04)' },
                    ticks: {
                        callback: v => 'LKR ' + v.toLocaleString()
                    }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // ── Dashboard Flatpickr — populates hidden from/to fields ──────────
    (function () {
        const fromInput = document.getElementById('fpFrom');
        const toInput   = document.getElementById('fpTo');
        const form      = document.getElementById('dashDateForm');

        // Set initial dates if already filtered via from/to (not preset)
        const initFrom = fromInput.value;
        const initTo   = toInput.value;

        flatpickr('#dashboardDatePicker', {
            mode: 'range',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd M Y',
            showMonths: 1,
            disableMobile: true,
            // Pre-fill if the view was loaded with an explicit from/to date
            defaultDate: (initFrom && initTo) ? [initFrom, initTo] : undefined,
            onChange: function (selectedDates) {
                if (selectedDates.length === 1) {
                    // Single date selected — fill both from and to
                    const d = selectedDates[0].toISOString().slice(0, 10);
                    fromInput.value = d;
                    toInput.value   = d;
                } else if (selectedDates.length === 2) {
                    // Range selected
                    fromInput.value = selectedDates[0].toISOString().slice(0, 10);
                    toInput.value   = selectedDates[1].toISOString().slice(0, 10);
                } else {
                    fromInput.value = '';
                    toInput.value   = '';
                }
            },
        });
    })();
})();
</script>
@endsection
