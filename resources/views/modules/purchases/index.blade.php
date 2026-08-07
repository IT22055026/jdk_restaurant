@extends('layouts.app')

@section('title', 'Purchases')

@php
    $filteredCategory = request()->filled('category_id')
        ? $mainCategories->firstWhere('id', (int) request('category_id')) ?? $subcategories->firstWhere('id', (int) request('category_id'))
        : null;
    $filterTopId = $filteredCategory ? ($filteredCategory->parent_id ?? $filteredCategory->id) : null;
    $filterSubId = $filteredCategory && $filteredCategory->parent_id ? $filteredCategory->id : null;
@endphp

@section('content')
    <div>
        <div class="mb-6 flex items-center justify-between flex-wrap gap-4">
            <div>
                @section('breadcrumb')
                    <nav class="text-sm text-gray-600" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-2">
                            <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a></li>
                            <li class="text-gray-300">/</li>
                            <li class="text-gray-900 font-semibold">Purchases & Expenses</li>
                        </ol>
                    </nav>
                <h1 class="text-4xl font-bold text-gray-900">Purchases & Expenses</h1>
                @endsection
                <p class="text-gray-600 mt-2">Record supplier purchases by category and track monthly totals</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('purchase-categories.index') }}" class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 px-5 py-3 rounded-lg font-semibold transition-colors flex items-center gap-2">
                    <i class="fas fa-sitemap"></i>Manage Categories
                </a>
                <a href="{{ route('purchases.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors flex items-center gap-2">
                    <i class="fas fa-plus"></i>Add Purchase
                </a>
            </div>
        </div>

        @include('modules.partials.purchase-expense-tabs')

        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-2">
                <i class="fas fa-check-circle"></i>{{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center gap-2">
                <i class="fas fa-triangle-exclamation"></i>{{ session('error') }}
            </div>
        @endif

        <!-- Summary KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold">
                    <i class="fas fa-basket-shopping"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Purchases (Filtered)</p>
                    <h3 class="text-2xl font-bold text-gray-900">LKR {{ number_format($totalPurchases, 2) }}</h3>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center text-xl font-bold">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">This Month's Purchases</p>
                    <h3 class="text-2xl font-bold text-gray-900">LKR {{ number_format($thisMonthPurchases, 2) }}</h3>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl font-bold">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Today's Purchases</p>
                    <h3 class="text-2xl font-bold text-gray-900">LKR {{ number_format($todayPurchases, 2) }}</h3>
                </div>
            </div>
        </div>

        <!-- Monthly Total by Supplier -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm mb-8">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Monthly Total by Supplier</h2>
                    <p class="text-sm text-gray-500 mt-0.5">How much was bought from each supplier in the selected month</p>
                </div>
                <form method="GET" action="{{ route('purchases.index') }}" class="flex items-center gap-2">
                    <input type="month" name="summary_month" value="{{ $summaryMonth }}"
                        class="text-sm border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 text-sm rounded-lg font-semibold transition-colors">
                        View
                    </button>
                </form>
            </div>
            @if($supplierTotals->isEmpty())
                <p class="text-center text-gray-500 text-sm py-8">No purchases recorded from any supplier in this month yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Supplier</th>
                                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900"># Purchases</th>
                                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Total (LKR)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($supplierTotals as $row)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-3 text-sm font-semibold text-gray-900">{{ $row->supplier?->name ?? '—' }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-600 text-right">{{ $row->purchase_count }}</td>
                                    <td class="px-6 py-3 text-sm font-bold text-amber-600 text-right">{{ number_format($row->total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 border-t border-gray-200">
                            <tr>
                                <td class="px-6 py-3 text-sm font-bold text-gray-900">Total</td>
                                <td class="px-6 py-3 text-sm font-bold text-gray-900 text-right">{{ $supplierTotals->sum('purchase_count') }}</td>
                                <td class="px-6 py-3 text-sm font-bold text-gray-900 text-right">{{ number_format($supplierTotals->sum('total'), 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm mb-6">
            <form method="GET" action="{{ route('purchases.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                <x-combobox
                    name="filter_main_category"
                    id="filter_main_category"
                    label="Main Category"
                    placeholder="All categories"
                    empty-option="All Categories"
                    :selected="$filterTopId"
                    :options="$mainCategories->map(fn($c) => ['value' => $c->id, 'label' => $c->name])" />

                <div>
                    <x-combobox
                        name="category_id"
                        id="filter_category_id"
                        label="Sub-category"
                        placeholder="All sub-categories"
                        empty-option="All Sub-categories"
                        :selected="$filterSubId"
                        :options="$subcategories->map(fn($c) => ['value' => $c->id, 'label' => $c->name, 'data' => ['parent' => $c->parent_id]])" />
                </div>

                <x-combobox
                    name="supplier_id"
                    id="filter_supplier_id"
                    label="Supplier"
                    placeholder="All suppliers"
                    empty-option="All Suppliers"
                    :selected="request('supplier_id')"
                    :options="$suppliers->map(fn($s) => ['value' => $s->id, 'label' => $s->name])" />

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">From Date</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="w-full text-sm border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="flex items-end gap-2">
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-500 mb-1">To Date</label>
                        <input type="date" name="to" value="{{ request('to') }}" class="w-full text-sm border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 text-sm rounded-lg font-semibold transition-colors">
                        <i class="fas fa-filter"></i>
                    </button>
                    @if(request()->hasAny(['category_id', 'supplier_id', 'from', 'to']))
                        <a href="{{ route('purchases.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 text-sm rounded-lg font-semibold transition-colors">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Table Container -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
            @if($purchases->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Date</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Category</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Supplier</th>
                                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Qty</th>
                                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Amount (LKR)</th>
                                <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($purchases as $purchase)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                        {{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d M Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-100">
                                            {{ $purchase->category?->parent?->name ? $purchase->category->parent->name . ' › ' : '' }}{{ $purchase->category?->name ?? '—' }}
                                        </span>
                                        @if($purchase->item_name)
                                            <p class="text-xs text-gray-400 font-normal mt-1">{{ $purchase->item_name }}</p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $purchase->supplier?->name ?? '—' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 text-right">
                                        {{ rtrim(rtrim(number_format((float) $purchase->quantity, 3), '0'), '.') }} {{ $purchase->unit }}
                                    </td>
                                    <td class="px-6 py-4 text-sm font-bold text-amber-600 text-right">
                                        LKR {{ number_format($purchase->amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-3">
                                            <a href="{{ route('purchases.edit', $purchase) }}" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </a>
                                            <form action="{{ route('purchases.destroy', $purchase) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this purchase?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-semibold">
                                                    <i class="fas fa-trash mr-1"></i>Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $purchases->links('pagination::tailwind') }}
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-basket-shopping text-gray-300 text-5xl mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No purchases recorded yet</h3>
                    <p class="text-gray-600 mb-4">Start by recording your first supplier purchase</p>
                    <a href="{{ route('purchases.create') }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-plus mr-2"></i>Add Purchase
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        Combobox.setupCascade('filter_category_id', 'filter_main_category', 'data-parent');
    });
</script>
@endsection
