@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div>
    <!-- Page header -->
    <div class="mb-8 flex items-center justify-between flex-wrap gap-4">
        <div>
                @section('breadcrumb')
                    <nav class="text-sm text-gray-600" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-2">
                            <li class="text-gray-900 font-semibold">Dashboard</li>
                        </ol>
                    </nav>
                <h1 class="text-4xl font-bold text-gray-900">Dashboard</h1>
                @endsection
            <p class="text-gray-600 mt-2">Business analytics and sales overview</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <span class="text-sm text-gray-500 font-medium">
                <i class="fas fa-clock mr-1"></i>As of {{ now()->format('d M Y, H:i') }}
            </span>
            <div class="flex gap-2">
                <a href="{{ route('reports.export.sales') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                    <i class="fas fa-download"></i> Sales PDF
                </a>
                <a href="{{ route('reports.export.products') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition text-sm font-medium">
                    <i class="fas fa-download"></i> Products PDF
                </a>
                <a href="{{ route('reports.export.stock') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm font-medium">
                    <i class="fas fa-boxes-stacked"></i> Stock Report
                </a>
                <a href="{{ route('reports.export.combined') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-800 transition text-sm font-medium">
                    <i class="fas fa-download"></i> Complete PDF
                </a>
            </div>
        </div>
    </div>

    <!-- ── OVERVIEW STATS ── -->
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4 mb-4">
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-xs font-semibold uppercase tracking-wide">Total Sales</p>
                    <p class="text-lg font-bold text-gray-900 mt-1">LKR {{ number_format($totalRevenue, 2) }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-sack-dollar text-blue-600 text-sm"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-xs font-semibold uppercase tracking-wide">Active Orders</p>
                    <p class="text-lg font-bold text-gray-900 mt-1">{{ $activeOrders }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-shopping-cart text-amber-500 text-sm"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-xs font-semibold uppercase tracking-wide">Inventory Items</p>
                    <p class="text-lg font-bold text-gray-900 mt-1">{{ $inventoryItems }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-boxes text-emerald-600 text-sm"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-xs font-semibold uppercase tracking-wide">Active Users</p>
                    <p class="text-lg font-bold text-gray-900 mt-1">{{ $activeUsers }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-users text-purple-600 text-sm"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-xs font-semibold uppercase tracking-wide">This Month</p>
                    <p class="text-lg font-bold text-gray-900 mt-1">LKR {{ number_format($monthRevenue, 2) }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-teal-50 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-calendar-check text-teal-600 text-sm"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- ── SUMMARY CARDS (7 cards) ── -->
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-7 gap-4 mb-8">

        <!-- Total Revenue -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Total Revenue</p>
            <p class="text-xl font-bold text-gray-900">LKR {{ number_format($totalRevenue, 2) }}</p>
            <div class="mt-2 w-8 h-1 rounded-full bg-blue-500"></div>
        </div>

        <!-- Today's Sales -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Today's Sales</p>
            <p class="text-xl font-bold text-gray-900">LKR {{ number_format($todaySales, 2) }}</p>
            <div class="mt-2 w-8 h-1 rounded-full bg-amber-400"></div>
        </div>

        <!-- This Month -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">This Month</p>
            <p class="text-xl font-bold text-gray-900">LKR {{ number_format($monthRevenue, 2) }}</p>
            <div class="mt-2 w-8 h-1 rounded-full bg-teal-400"></div>
        </div>

        <!-- Total Orders -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Completed Orders</p>
            <p class="text-xl font-bold text-gray-900">{{ number_format($totalOrders) }}</p>
            <div class="mt-2 w-8 h-1 rounded-full bg-blue-400"></div>
        </div>

        <!-- Average Order Value -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Avg. Order Value</p>
            <p class="text-xl font-bold text-gray-900">LKR {{ number_format($avgOrderValue, 2) }}</p>
            <div class="mt-2 w-8 h-1 rounded-full bg-purple-400"></div>
        </div>

        <!-- Top Selling Product -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Top Product</p>
            <p class="text-base font-bold text-gray-900 truncate" title="{{ $topProduct }}">{{ $topProduct }}</p>
            <div class="mt-2 w-8 h-1 rounded-full bg-green-400"></div>
        </div>

        <!-- Pending Sales -->
        <div class="bg-amber-50 rounded-2xl p-5 border border-amber-200 shadow-sm hover:shadow-md transition-shadow">
            <p class="text-xs font-semibold text-amber-600 uppercase tracking-wide mb-1">Pending Bills</p>
            <p class="text-xl font-bold text-amber-700">{{ $pendingCount }} <span class="text-sm font-normal">open</span></p>
            <p class="text-xs text-amber-600 mt-0.5">LKR {{ number_format($pendingTotal, 2) }}</p>
            <div class="mt-2 w-8 h-1 rounded-full bg-amber-400"></div>
        </div>

    </div>

    <!-- ── PENDING SALES ── -->
    <div class="bg-white rounded-2xl border border-amber-200 shadow-sm overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-amber-100 flex items-center justify-between bg-amber-50">
            <h2 class="text-lg font-bold text-amber-800">
                <i class="fas fa-clock text-amber-500 mr-2"></i>Pending Sales
                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-200 text-amber-800">{{ $pendingCount }} unsettled</span>
            </h2>
            <span class="text-sm font-semibold text-amber-700">Total: LKR {{ number_format($pendingTotal, 2) }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left">Order #</th>
                        <th class="px-4 py-3 text-left">Token</th>
                        <th class="px-4 py-3 text-left">Customer</th>
                        <th class="px-4 py-3 text-left">Items</th>
                        <th class="px-4 py-3 text-right">Bill Total</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Started</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($pendingSales as $pending)
                    @php
                        $statusColors = [
                            'pending'   => 'bg-amber-100 text-amber-700',
                            'hold'      => 'bg-blue-100 text-blue-700',
                            'confirmed' => 'bg-green-100 text-green-700',
                        ];
                        $statusColor = $statusColors[$pending->status] ?? 'bg-gray-100 text-gray-600';
                        $itemList = $pending->items->map(fn($i) => $i->quantity . '× ' . $i->product_name)->implode(', ');
                    @endphp
                    <tr class="hover:bg-amber-50 transition-colors">
                        <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $pending->order_number }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $pending->token_number ? '#' . str_pad($pending->token_number, 2, '0', STR_PAD_LEFT) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $pending->customer_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500 max-w-xs truncate" title="{{ $itemList }}">
                            {{ $itemList ?: '—' }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900">
                            LKR {{ number_format($pending->total, 2) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusColor }}">
                                {{ ucfirst($pending->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-400 text-xs whitespace-nowrap">
                            {{ $pending->created_at->format('d M, H:i') }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">No pending bills right now — all clear!</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── REVENUE CHART (last 7 days) ── -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-8">
        <h2 class="text-lg font-bold text-gray-900 mb-4">
            <i class="fas fa-chart-bar text-blue-500 mr-2"></i>Revenue — Last 7 Days
        </h2>
        <div style="position:relative; height:260px;">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    <!-- ── TWO-COLUMN LOWER SECTION (Recent Sales + Payment Breakdown) ── -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">

        <!-- Recent Sales Table (2/3) -->
        <div class="xl:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-900">
                    <i class="fas fa-receipt text-blue-500 mr-2"></i>Recent Sales
                </h2>
                <span class="text-xs text-gray-400">Last 20 completed orders</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        <tr>
                            <th class="px-4 py-3 text-left">Order #</th>
                            <th class="px-4 py-3 text-left">Token</th>
                            <th class="px-4 py-3 text-left">Customer</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-center">Payment</th>
                            <th class="px-4 py-3 text-right">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentSales as $sale)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $sale->order_number }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $sale->token_number ? '#' . str_pad($sale->token_number, 2, '0', STR_PAD_LEFT) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $sale->customer_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900">
                                LKR {{ number_format($sale->total, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $pmColors = [
                                        'cash'          => 'bg-green-100 text-green-700',
                                        'card'          => 'bg-blue-100 text-blue-700',
                                        'bank_transfer' => 'bg-purple-100 text-purple-700',
                                        'mixed'         => 'bg-orange-100 text-orange-700',
                                        'pickme'        => 'bg-red-100 text-red-700',
                                        'uber'          => 'bg-gray-800 text-white',
                                    ];
                                    $pmColor = $pmColors[$sale->payment_method] ?? 'bg-gray-100 text-gray-600';
                                    $pmLabels = ['pickme' => 'PickMe', 'uber' => 'Uber'];
                                    $pmLabel = $pmLabels[$sale->payment_method] ?? ucfirst(str_replace('_', ' ', $sale->payment_method ?? '—'));
                                @endphp
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $pmColor }}">
                                    {{ $pmLabel }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-gray-400 text-xs whitespace-nowrap">
                                {{ $sale->created_at->format('d M, H:i') }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">No completed orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payment Methods Breakdown (1/3) -->
        <div class="xl:col-span-1 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-900 mb-3">
                    <i class="fas fa-credit-card text-blue-500 mr-2"></i>Payment Methods
                </h2>
                <!-- Date range filter -->
                <div class="flex items-center gap-2">
                    <div class="relative flex-1 flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5">
                        <i class="fas fa-calendar text-red-400 text-xs"></i>
                        <input id="pmDatePicker" type="text" placeholder="All time"
                            class="text-xs text-gray-600 bg-transparent outline-none w-full cursor-pointer"
                            readonly>
                    </div>
                    <button id="pmClearBtn" onclick="clearPmFilter()"
                        class="hidden px-2 py-1.5 text-xs text-gray-500 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
                <p id="pmDateLabel" class="text-xs text-gray-400 mt-1.5 hidden"></p>
            </div>
            <div id="pmBreakdownBody" class="p-6 space-y-4">
                @forelse($paymentBreakdown as $pm)
                @php
                    $pmIcons = [
                        'cash'          => 'fas fa-money-bill-wave text-green-500',
                        'card'          => 'fas fa-credit-card text-blue-500',
                        'bank_transfer' => 'fas fa-university text-purple-500',
                        'mixed'         => 'fas fa-shuffle text-orange-500',
                        'pickme'        => 'fas fa-taxi text-red-500',
                        'uber'          => 'fab fa-uber text-gray-800',
                    ];
                    $icon = $pmIcons[$pm->payment_method] ?? 'fas fa-circle-question text-gray-400';
                    $pmLabels = ['pickme' => 'PickMe', 'uber' => 'Uber'];
                    $pmLabel = $pmLabels[$pm->payment_method] ?? ucfirst(str_replace('_', ' ', $pm->payment_method));
                    $pct  = $totalOrders > 0 ? round(($pm->order_count / $totalOrders) * 100) : 0;
                @endphp
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            <i class="{{ $icon }}"></i>
                            {{ $pmLabel }}
                        </span>
                        <span class="text-sm font-bold text-gray-900">{{ $pm->order_count }} orders</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex-1 bg-gray-100 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                        </div>
                        <span class="text-xs text-gray-400 w-10 text-right">{{ $pct }}%</span>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">LKR {{ number_format($pm->total_revenue, 2) }}</p>
                </div>
                @empty
                <p class="text-center text-gray-400 py-6">No payment data yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- ── TOP PRODUCTS TABLE ── -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900">
                <i class="fas fa-trophy text-amber-400 mr-2"></i>Top Selling Products
            </h2>
            <span class="text-xs text-gray-400">By quantity sold</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-6 py-3 text-left">#</th>
                        <th class="px-6 py-3 text-left">Product</th>
                        <th class="px-6 py-3 text-left">Category</th>
                        <th class="px-6 py-3 text-right">Qty Sold</th>
                        <th class="px-6 py-3 text-right">Total Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($topProducts as $i => $prod)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-3 text-gray-400 font-semibold">
                            @if($i === 0) <i class="fas fa-medal text-amber-400"></i>
                            @elseif($i === 1) <i class="fas fa-medal text-gray-400"></i>
                            @elseif($i === 2) <i class="fas fa-medal text-orange-400"></i>
                            @else {{ $i + 1 }}
                            @endif
                        </td>
                        <td class="px-6 py-3 font-semibold text-gray-900">{{ $prod->product_name }}</td>
                        <td class="px-6 py-3 text-gray-500">{{ $prod->category_name }}</td>
                        <td class="px-6 py-3 text-right font-bold text-gray-900">{{ number_format($prod->total_qty) }}</td>
                        <td class="px-6 py-3 text-right font-semibold text-gray-900">
                            LKR {{ number_format($prod->total_revenue, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-10 text-center text-gray-400">No sales data yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    .flatpickr-calendar { border-radius: 14px !important; box-shadow: 0 8px 30px rgba(0,0,0,0.12) !important; border: 1px solid #e2e8f0 !important; }
    .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange { background: #dc2626 !important; border-color: #dc2626 !important; }
    .flatpickr-day.inRange { background: #fee2e2 !important; border-color: #fee2e2 !important; color: #dc2626 !important; }
    .flatpickr-day:hover { background: #fef2f2 !important; border-color: #fca5a5 !important; }
    .flatpickr-months .flatpickr-month { background: #dc2626 !important; color: #fff !important; border-radius: 14px 14px 0 0 !important; }
    .flatpickr-current-month, .flatpickr-current-month select, .flatpickr-current-month .numInputWrapper { color: #fff !important; }
    .flatpickr-weekday { color: #dc2626 !important; font-weight: 700 !important; }
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
                backgroundColor: 'rgba(220, 38, 38, 0.15)',
                borderColor: '#dc2626',
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
})();

// ── Flatpickr — Payment Methods date range ──
(function () {
    const pmIcons = {
        cash:          'fas fa-money-bill-wave text-green-500',
        card:          'fas fa-credit-card text-blue-500',
        bank_transfer: 'fas fa-university text-purple-500',
        mixed:         'fas fa-shuffle text-orange-500',
        pickme:        'fas fa-taxi text-red-500',
        uber:          'fab fa-uber text-gray-800',
    };

    const endpoint = '{{ route("reports.payment.breakdown") }}';
    let activeDates = { from: null, to: null };

    function fmtDate(d) { return d.toISOString().slice(0, 10); }

    function renderBreakdown(data) {
        const body = document.getElementById('pmBreakdownBody');
        const label = document.getElementById('pmDateLabel');

        if (!data.breakdown.length) {
            body.innerHTML = '<p class="text-center text-gray-400 py-6">No payment data for this range.</p>';
            return;
        }

        body.innerHTML = data.breakdown.map(pm => {
            const icon = pmIcons[pm.method] || 'fas fa-circle-question text-gray-400';
            return `<div>
                <div class="flex items-center justify-between mb-1">
                    <span class="flex items-center gap-2 text-sm font-medium text-gray-700">
                        <i class="${icon}"></i>${pm.label}
                    </span>
                    <span class="text-sm font-bold text-gray-900">${pm.order_count} orders</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex-1 bg-gray-100 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full transition-all" style="width:${pm.pct}%"></div>
                    </div>
                    <span class="text-xs text-gray-400 w-10 text-right">${pm.pct}%</span>
                </div>
                <p class="text-xs text-gray-400 mt-0.5">LKR ${parseFloat(pm.total_revenue).toLocaleString('en-US', {minimumFractionDigits:2})}</p>
            </div>`;
        }).join('');

        if (activeDates.from && activeDates.to) {
            label.textContent = `${data.total_count} orders · LKR ${parseFloat(data.total_revenue).toLocaleString('en-US', {minimumFractionDigits:2})}`;
            label.classList.remove('hidden');
        } else {
            label.classList.add('hidden');
        }
    }

    async function loadBreakdown(from, to) {
        const url = new URL(endpoint);
        if (from) url.searchParams.set('from', from);
        if (to)   url.searchParams.set('to', to);
        try {
            const res  = await fetch(url);
            const data = await res.json();
            renderBreakdown(data);
        } catch(e) { console.error('Payment breakdown error', e); }
    }

    window.clearPmFilter = function () {
        activeDates = { from: null, to: null };
        pmFp.clear();
        document.getElementById('pmClearBtn').classList.add('hidden');
        document.getElementById('pmDateLabel').classList.add('hidden');
        loadBreakdown(null, null);
    };

    const pmFp = flatpickr('#pmDatePicker', {
        mode: 'range',
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd M Y',
        showMonths: 1,
        disableMobile: true,
        onChange: function (selectedDates) {
            if (selectedDates.length === 2) {
                activeDates.from = fmtDate(selectedDates[0]);
                activeDates.to   = fmtDate(selectedDates[1]);
                document.getElementById('pmClearBtn').classList.remove('hidden');
                loadBreakdown(activeDates.from, activeDates.to);
            }
        }
    });
})();
</script>
@endsection
