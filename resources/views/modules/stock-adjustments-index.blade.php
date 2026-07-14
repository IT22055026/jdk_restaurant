@extends('layouts.app')

@section('title', 'Stock Adjustments')

@section('content')
    <div>
        <div class="mb-8 flex items-center justify-between flex-wrap gap-4">
            <div>
                <div class="flex items-center">
                    <a href="{{ route('inventory.index') }}" class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 hover:text-gray-900 transition-colors mr-3 flex-shrink-0" title="Back">
                        <i class="fas fa-arrow-left text-sm"></i>
                    </a>
                    @section('breadcrumb')
                        <nav class="text-sm text-gray-600" aria-label="Breadcrumb">
                            <ol class="inline-flex items-center space-x-2">
                                <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a></li>
                                <li class="text-gray-300">/</li>
                                <li class="text-gray-900 font-semibold">Stock Adjustments</li>
                            </ol>
                        </nav>
                    <h1 class="text-4xl font-bold text-gray-900">Stock Adjustments</h1>
                    @endsection
                </div>
                <p class="text-gray-600 mt-2">Manual stock movements for finished goods and ingredients</p>
            </div>
            <a href="{{ route('stock.adjustments.create') }}" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                <i class="fas fa-plus mr-2"></i>New Adjustment
            </a>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            </div>
        @endif

        <form method="GET" action="{{ route('stock.adjustments.index') }}" class="mb-6 flex items-center gap-3">
            <label for="type" class="text-sm font-medium text-gray-700">Filter by type:</label>
            <select id="type" name="type" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                <option value="" {{ $type === null ? 'selected' : '' }}>All</option>
                <option value="finished_good" {{ $type === 'finished_good' ? 'selected' : '' }}>Finished Good</option>
                <option value="ingredient" {{ $type === 'ingredient' ? 'selected' : '' }}>Ingredient</option>
            </select>
            @if($type)
                <a href="{{ route('stock.adjustments.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
            @endif
        </form>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
            @if($movements->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Date</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Type</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Item</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Change</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Quantity</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Reason</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Source</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">User</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($movements as $movement)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $movement['created_at']?->format('M d, Y H:i') ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $movement['type'] === 'Ingredient' ? 'bg-emerald-100 text-emerald-800' : 'bg-indigo-100 text-indigo-800' }}">
                                            {{ $movement['type'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                        {{ $movement['name'] }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $movement['change_type'] === 'increase' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $movement['change_type'] === 'increase' ? 'Increase' : 'Decrease' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ rtrim(rtrim(number_format($movement['quantity'], 3), '0'), '.') }} {{ $movement['unit'] }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $movement['reason'] ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ ucfirst($movement['source']) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $movement['user'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $movements->links('pagination::tailwind') }}
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-layer-group text-gray-300 text-5xl mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No stock movements yet</h3>
                    <p class="text-gray-600 mb-4">Create a stock adjustment to get started</p>
                    <a href="{{ route('stock.adjustments.create') }}" class="inline-block bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-plus mr-2"></i>New Adjustment
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
