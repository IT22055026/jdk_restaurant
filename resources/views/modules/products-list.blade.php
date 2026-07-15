@extends('layouts.app')

@section('title', 'Products & Inventory')

@section('content')
    <div>
        <div class="mb-8 flex items-center justify-between flex-wrap gap-4">
            <div>
                @section('breadcrumb')
                    <nav class="text-sm text-gray-600" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-2">
                            <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a></li>
                            <li class="text-gray-300">/</li>
                            <li class="text-gray-900 font-semibold">Inventory & Products</li>
                        </ol>
                    </nav>

                <h1 class="text-4xl font-bold text-gray-900">Products & Inventory</h1>
                <p class="text-gray-600 mt-2">Manage products and inventory stock</p>
                @endsection
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('categories.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-900 px-6 py-3 rounded-lg font-semibold transition-colors">
                    <i class="fas fa-tags mr-2"></i>Categories
                </a>
                <a href="{{ route('inventory.dashboard') }}" class="relative inline-flex items-center gap-2 px-6 py-3 rounded-lg font-semibold transition-colors {{ $lowStockCount > 0 ? 'bg-amber-100 hover:bg-amber-200 text-amber-800 border border-amber-300' : 'bg-gray-200 hover:bg-gray-300 text-gray-900' }}">
                    <i class="fas fa-triangle-exclamation"></i>
                    Low Stock Alerts
                    @if($lowStockCount > 0)
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-amber-500 text-white text-xs font-bold leading-none">
                            {{ $lowStockCount > 99 ? '99+' : $lowStockCount }}
                        </span>
                    @endif
                </a>
                <a href="{{ route('stock.adjustments.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-900 px-6 py-3 rounded-lg font-semibold transition-colors">
                    <i class="fas fa-layer-group mr-2"></i>Stock Adjustments
                </a>
                <a href="{{ route('ingredients.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-900 px-6 py-3 rounded-lg font-semibold transition-colors">
                    <i class="fas fa-carrot mr-2"></i>Included Items
                </a>
                <a href="{{ route('products.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add Product
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            </div>
        @endif

        <!-- Search Bar -->
        <div class="mb-6">
            <form action="{{ route('inventory.index') }}" method="GET" class="relative">
                <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" name="search" placeholder="Search by product name…"
                       value="{{ $searchQuery ?? '' }}"
                       class="w-full pl-10 pr-12 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       onkeypress="if(event.key=='Enter') this.form.submit()">
                @if($searchQuery)
                    <a href="{{ route('inventory.index') }}" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </form>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
            @if($products->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Image</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Name</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Category</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Supplier</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Price</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Stock</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                                <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($products as $product)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 text-sm">
                                        @if($product->image)
                                            <div class="h-12 w-12 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0 group cursor-pointer relative">
                                                   <img src="{{ Str::startsWith($product->image, ['http://', 'https://']) ? $product->image : asset('storage/' . $product->image) }}"
                                                       alt="{{ $product->name }}"
                                                       class="h-full w-full object-cover group-hover:scale-110 transition-transform duration-200"
                                                       onload="this.parentElement.classList.add('loaded')"
                                                       onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27200%27 height=%27120%27 viewBox=%270 0 200 120%27%3E%3Crect width=%27100%25%27 height=%27100%25%27 fill=%27%23f3f4f6%27/%3E%3Ctext x=%2750%25%27 y=%2750%25%27 dominant-baseline=%27middle%27 text-anchor=%27middle%27 fill=%27%23888%27 font-size=%2720%27%3ENo image%3C/text%3E%3C/svg%3E'"
                                                       title="{{ $product->name }}">
                                                <div class="absolute inset-0 bg-black opacity-0 group-hover:opacity-10 transition-opacity"></div>
                                            </div>
                                        @else
                                            <div class="h-12 w-12 rounded-lg bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center text-gray-400 text-lg flex-shrink-0">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $product->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $product->category?->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $product->supplierRecord?->name ?? ($product->getAttribute('supplier') ?? 'N/A') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        LKR {{ number_format($product->selling_price ?? $product->price, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        @if($product->is_unlimited_stock)
                                            <span class="px-3 py-1 rounded-lg bg-purple-100 text-purple-800">Unlimited</span>
                                        @elseif(!$product->is_finished_good)
                                            @php $avail = $product->availableStock(); @endphp
                                                @if(!$product->hasRecipe())
                                                    <span class="px-3 py-1 rounded-lg bg-red-100 text-red-800 font-semibold" title="No recipe defined — this item can't be made until ingredients are assigned">
                                                        <i class="fas fa-triangle-exclamation"></i> No recipe
                                                    </span>
                                                @elseif($avail === 0)
                                                    <span class="px-3 py-1 rounded-lg bg-red-100 text-red-800 font-semibold">Out of Stock</span>
                                                @else
                                                    <span class="px-3 py-1 rounded-lg bg-blue-100 text-blue-800">BOM</span>
                                                @endif
                                        @else
                                            @if($product->quantity == 0)
                                                <span class="px-3 py-1 rounded-lg bg-red-100 text-red-800 font-semibold">0</span>
                                            @elseif($product->isLowStock())
                                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-amber-100 text-amber-800 font-semibold">
                                                    {{ $product->quantity }}
                                                    <i class="fas fa-triangle-exclamation text-xs"></i>
                                                </span>
                                            @else
                                                <span class="px-3 py-1 rounded-lg bg-blue-100 text-blue-800">{{ $product->quantity }}</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $product->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ ucfirst($product->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ route('products.edit', $product) }}" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </a>
                                            @if(!$product->is_finished_good)
                                                <a href="{{ route('products.recipe.edit', $product) }}" class="text-emerald-600 hover:text-emerald-800 text-sm font-semibold">
                                                    <i class="fas fa-flask mr-1"></i>Recipe
                                                </a>
                                            @endif
                                            <form action="{{ route('products.destroy', $product) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?')">
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
                    {{ $products->links('pagination::tailwind') }}
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-boxes text-gray-300 text-5xl mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No products yet</h3>
                    <p class="text-gray-600 mb-4">Start by adding your first product</p>
                    <a href="{{ route('products.create') }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-plus mr-2"></i>Add Product
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
