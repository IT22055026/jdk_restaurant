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
                    <h1 class="text-4xl font-bold text-gray-900">Stock Adjustments</h1>
                </div>
                <p class="text-gray-600 mt-2">Manual stock movements and history</p>
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

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
            @if($movements->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Date</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Product</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Type</th>
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
                                        {{ $movement->created_at?->format('M d, Y H:i') ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                        {{ $movement->product?->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $movement->change_type === 'increase' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $movement->change_type === 'increase' ? 'Increase' : 'Decrease' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $movement->quantity }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $movement->reason ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ ucfirst($movement->source) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $movement->user?->name ?? 'System' }}
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
