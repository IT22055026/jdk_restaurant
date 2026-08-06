@extends('layouts.app')

@section('title', 'Expenses')

@section('content')
    <div>
        <!-- Header & Breadcrumbs -->
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
                <p class="text-gray-600 mt-2">Track and manage restaurant operational expenses</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('expense-categories.index') }}" class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 px-5 py-3 rounded-lg font-semibold transition-colors flex items-center gap-2">
                    <i class="fas fa-sitemap"></i>Manage Categories
                </a>
                <a href="{{ route('expenses.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors flex items-center gap-2">
                    <i class="fas fa-plus"></i>Add Expense
                </a>
            </div>
        </div>

        @include('modules.partials.purchase-expense-tabs')

        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-2">
                <i class="fas fa-check-circle"></i>{{ session('success') }}
            </div>
        @endif

        <!-- Summary KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-xl font-bold">
                    <i class="fas fa-receipt"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Expenses (Filtered)</p>
                    <h3 class="text-2xl font-bold text-gray-900">LKR {{ number_format($totalExpenses, 2) }}</h3>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center text-xl font-bold">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">This Month's Expenses</p>
                    <h3 class="text-2xl font-bold text-gray-900">LKR {{ number_format($thisMonthExpenses, 2) }}</h3>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl font-bold">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Today's Expenses</p>
                    <h3 class="text-2xl font-bold text-gray-900">LKR {{ number_format($todayExpenses, 2) }}</h3>
                </div>
            </div>
        </div>

        <!-- Monthly Category Breakdown -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm mb-8">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Monthly Breakdown by Category</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Totals for the selected month, whenever you need them</p>
                </div>
                <form method="GET" action="{{ route('expenses.index') }}" class="flex items-center gap-2">
                    <input type="month" name="summary_month" value="{{ $summaryMonth }}"
                        class="text-sm border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 text-sm rounded-lg font-semibold transition-colors">
                        View
                    </button>
                </form>
            </div>

            @if($categoryTotals->isEmpty())
                <p class="text-center text-gray-500 text-sm py-8">No expenses recorded in this month yet.</p>
            @else
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($categoryTotals as $row)
                        <div class="border border-gray-100 rounded-xl p-4">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-gray-900 text-sm">{{ $row['category']->name }}</span>
                                <span class="font-bold text-red-600 text-sm">LKR {{ number_format($row['total'], 2) }}</span>
                            </div>
                            @if($row['children']->isNotEmpty())
                                <div class="mt-2 pt-2 border-t border-gray-50 space-y-1">
                                    @foreach($row['children'] as $child)
                                        @if($child['total'] > 0)
                                            <div class="flex items-center justify-between text-xs text-gray-500">
                                                <span><i class="fas fa-angle-right mr-1 text-gray-300"></i>{{ $child['category']->name }}</span>
                                                <span>LKR {{ number_format($child['total'], 2) }}</span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if($staffBreakdown && $staffBreakdown->isNotEmpty())
                <div class="px-6 pb-6">
                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
                        <h3 class="text-sm font-bold text-blue-900 mb-3"><i class="fas fa-user-tie mr-2"></i>Staff Expense Breakdown</h3>
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                            @foreach($staffBreakdown as $row)
                                <div class="bg-white rounded-lg p-3 text-center border border-blue-100">
                                    <p class="text-xs text-gray-500">{{ $row['category']->name }}</p>
                                    <p class="text-sm font-bold text-gray-900 mt-1">LKR {{ number_format($row['total'], 2) }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm mb-6">
            <form method="GET" action="{{ route('expenses.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <x-combobox
                    name="category_id"
                    id="filter_category_id"
                    label="Category"
                    placeholder="All categories"
                    empty-option="All Categories"
                    :selected="request('category_id')"
                    :options="$categories->map(fn($c) => ['value' => $c->id, 'label' => $c->parent ? $c->parent->name . ' › ' . $c->name : $c->name])" />

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">From Date</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="w-full text-sm border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">To Date</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="w-full text-sm border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 text-sm rounded-lg font-semibold transition-colors">
                        <i class="fas fa-filter mr-1"></i>Filter
                    </button>
                    @if(request()->hasAny(['category_id', 'from', 'to']))
                        <a href="{{ route('expenses.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 text-sm rounded-lg font-semibold transition-colors">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Table Container -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
            @if($expenses->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Date</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Title</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Category</th>
                                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Amount (LKR)</th>
                                <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($expenses as $expense)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                        {{ \Carbon\Carbon::parse($expense->expense_date)->format('d M Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 font-semibold">
                                        {{ $expense->title }}
                                        @if($expense->notes)
                                            <p class="text-xs text-gray-400 font-normal mt-0.5">{{ Str::limit($expense->notes, 40) }}</p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                                            {{ $expense->category?->parent?->name ? $expense->category->parent->name . ' › ' : '' }}{{ $expense->category?->name ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-bold text-red-600 text-right">
                                        LKR {{ number_format($expense->amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-3">
                                            <a href="{{ route('expenses.edit', $expense) }}" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </a>
                                            <form action="{{ route('expenses.destroy', $expense) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this expense?')">
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
                    {{ $expenses->links('pagination::tailwind') }}
                </div>
            @else
                <!-- Empty State matching Supplier Management UI -->
                <div class="text-center py-12">
                    <i class="fas fa-receipt text-gray-300 text-5xl mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No expenses recorded yet</h3>
                    <p class="text-gray-600 mb-4">Start by adding your first restaurant operational expense</p>
                    <a href="{{ route('expenses.create') }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-plus mr-2"></i>Add Expense
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
