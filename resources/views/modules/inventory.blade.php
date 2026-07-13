@extends('layouts.app')

@section('title', 'Inventory & Products')

@section('content')
<div>
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900">Inventory & Products</h1>
        <p class="text-gray-600 mt-2">Overview of stock levels and product alerts</p>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 rounded-lg px-5 py-3 flex items-center gap-3">
            <i class="fas fa-check-circle text-green-500"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('low_stock_warning'))
        <div class="mb-6 bg-amber-50 border border-amber-300 text-amber-800 rounded-lg px-5 py-3 flex items-center gap-3">
            <i class="fas fa-triangle-exclamation text-amber-500"></i>
            <span>{{ session('low_stock_warning') }}</span>
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-boxes text-blue-600 text-lg"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Total Products</p>
                <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ $totalProducts }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-green-50 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-check text-green-600 text-lg"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Active</p>
                <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ $activeProducts }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-red-50 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-ban text-red-600 text-lg"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Out of Stock</p>
                <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ $outOfStock }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-amber-50 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-triangle-exclamation text-amber-500 text-lg"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Low Stock</p>
                <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ $lowStockProducts->count() }}</p>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="flex flex-wrap gap-3 mb-8">
        <a href="{{ route('products.create') }}" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold text-sm transition-colors">
            <i class="fas fa-plus"></i> Add Product
        </a>
        <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 px-4 py-2 rounded-lg font-semibold text-sm transition-colors">
            <i class="fas fa-list"></i> All Products
        </a>
        <a href="{{ route('stock.adjustments.index') }}" class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 px-4 py-2 rounded-lg font-semibold text-sm transition-colors">
            <i class="fas fa-sliders"></i> Stock Adjustments
        </a>
        <a href="{{ route('wastage.index') }}" class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 px-4 py-2 rounded-lg font-semibold text-sm transition-colors">
            <i class="fas fa-trash-can"></i> Wastage Log
        </a>
    </div>

    {{-- Low Stock Alerts --}}
    <div class="bg-white rounded-xl border border-amber-200 shadow-sm mb-8">
        <div class="px-6 py-4 border-b border-amber-100 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
                <i class="fas fa-triangle-exclamation text-amber-500"></i>
            </div>
            <h2 class="text-base font-bold text-gray-900">Low Stock Alerts</h2>
            @if($lowStockProducts->count() > 0)
                <span class="ml-auto bg-amber-100 text-amber-700 text-xs font-bold px-2.5 py-1 rounded-full">
                    {{ $lowStockProducts->count() }} {{ Str::plural('product', $lowStockProducts->count()) }}
                </span>
            @endif
        </div>

        @if($lowStockProducts->isEmpty())
            <div class="py-12 text-center">
                <i class="fas fa-circle-check text-green-300 text-4xl mb-3"></i>
                <p class="text-gray-500 font-medium">All stock levels are healthy.</p>
                <p class="text-gray-400 text-sm mt-1">No products have hit their low stock threshold.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-amber-50">
                            <th class="text-left px-6 py-3 text-xs font-semibold text-amber-700 uppercase tracking-wide">Product</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-amber-700 uppercase tracking-wide">Category</th>
                            <th class="text-center px-6 py-3 text-xs font-semibold text-amber-700 uppercase tracking-wide">Current Stock</th>
                            <th class="text-center px-6 py-3 text-xs font-semibold text-amber-700 uppercase tracking-wide">Threshold</th>
                            <th class="text-center px-6 py-3 text-xs font-semibold text-amber-700 uppercase tracking-wide">Status</th>
                            <th class="text-right px-6 py-3 text-xs font-semibold text-amber-700 uppercase tracking-wide">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($lowStockProducts as $product)
                            <tr class="hover:bg-amber-50/40 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-900">{{ $product->name }}</div>
                                    @if($product->product_code)
                                        <div class="text-xs text-gray-400 mt-0.5">{{ $product->product_code }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ $product->category->name ?? '—' }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="font-bold {{ $product->quantity == 0 ? 'text-red-600' : 'text-amber-600' }} text-base">
                                        {{ $product->quantity }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center text-gray-500">{{ $product->low_stock_threshold }}</td>
                                <td class="px-6 py-4 text-center">
                                    @if($product->quantity == 0)
                                        <span class="inline-flex items-center gap-1 bg-red-100 text-red-700 text-xs font-semibold px-2.5 py-1 rounded-full">
                                            <i class="fas fa-circle text-[6px]"></i> Out of Stock
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 bg-amber-100 text-amber-700 text-xs font-semibold px-2.5 py-1 rounded-full">
                                            <i class="fas fa-circle text-[6px]"></i> Low Stock
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('products.edit', $product) }}" class="text-red-600 hover:text-red-700 font-semibold text-sm">
                                        <i class="fas fa-pen-to-square mr-1"></i>Edit
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Recent Products --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-bold text-gray-900">Recent Products</h2>
            <a href="{{ route('products.index') }}" class="text-sm text-red-600 hover:text-red-700 font-semibold">View All &rarr;</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Product</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Category</th>
                        <th class="text-center px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Stock</th>
                        <th class="text-center px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Threshold</th>
                        <th class="text-center px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recentProducts as $product)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-900">{{ $product->name }}</div>
                                @if($product->product_code)
                                    <div class="text-xs text-gray-400 mt-0.5">{{ $product->product_code }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ $product->category->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-center">
                                @if($product->is_unlimited_stock)
                                    <span class="text-blue-600 font-medium">∞</span>
                                @else
                                    <span class="font-semibold {{ $product->quantity == 0 ? 'text-red-600' : ($product->isLowStock() ? 'text-amber-600' : 'text-gray-800') }}">
                                        {{ $product->quantity }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center text-gray-500">
                                {{ $product->low_stock_threshold ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($product->status === 'active')
                                    <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs font-semibold px-2.5 py-1 rounded-full">
                                        <i class="fas fa-circle text-[6px]"></i> Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-500 text-xs font-semibold px-2.5 py-1 rounded-full">
                                        <i class="fas fa-circle text-[6px]"></i> Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('products.edit', $product) }}" class="text-red-600 hover:text-red-700 font-semibold text-sm">
                                    <i class="fas fa-pen-to-square mr-1"></i>Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-400">No products found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
