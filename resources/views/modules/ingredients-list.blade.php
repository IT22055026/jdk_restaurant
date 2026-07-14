@extends('layouts.app')

@section('title', 'Ingredients')

@section('content')
    <div>
        <div class="mb-8 flex items-center justify-between flex-wrap gap-4">
            <div>
                @section('breadcrumb')
                    <nav class="text-sm text-gray-600" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-2">
                            <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a></li>
                            <li class="text-gray-300">/</li>
                            <li class="text-gray-900 font-semibold">Ingredients</li>
                        </ol>
                    </nav>
                <h1 class="text-4xl font-bold text-gray-900">Ingredients</h1>
                @endsection
                <p class="text-gray-600 mt-2">Manage raw ingredient stock used in product recipes</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('stock.adjustments.index') }}" class="relative inline-flex items-center gap-2 px-6 py-3 rounded-lg font-semibold transition-colors {{ $lowStockCount > 0 ? 'bg-amber-100 hover:bg-amber-200 text-amber-800 border border-amber-300' : 'bg-gray-200 hover:bg-gray-300 text-gray-900' }}">
                    <i class="fas fa-layer-group"></i>
                    Stock Adjustments
                    @if($lowStockCount > 0)
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-amber-500 text-white text-xs font-bold leading-none">
                            {{ $lowStockCount > 99 ? '99+' : $lowStockCount }}
                        </span>
                    @endif
                </a>
                <a href="{{ route('ingredients.create') }}" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add Ingredient
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
            <form action="{{ route('ingredients.index') }}" method="GET" class="relative">
                <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" name="search" placeholder="Search by ingredient name…"
                       value="{{ $searchQuery ?? '' }}"
                       class="w-full pl-10 pr-12 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent"
                       onkeypress="if(event.key=='Enter') this.form.submit()">
                @if($searchQuery)
                    <a href="{{ route('ingredients.index') }}" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </form>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
            @if($ingredients->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Name</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Unit</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Supplier</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Stock</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                                <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($ingredients as $ingredient)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $ingredient->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $ingredient->unit }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $ingredient->supplierRecord?->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        @if((float) $ingredient->quantity == 0)
                                            <span class="px-3 py-1 rounded-lg bg-red-100 text-red-800 font-semibold">0 {{ $ingredient->unit }}</span>
                                        @elseif($ingredient->isLowStock())
                                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-amber-100 text-amber-800 font-semibold">
                                                {{ rtrim(rtrim(number_format((float) $ingredient->quantity, 3), '0'), '.') }} {{ $ingredient->unit }}
                                                <i class="fas fa-triangle-exclamation text-xs"></i>
                                            </span>
                                        @else
                                            <span class="px-3 py-1 rounded-lg bg-blue-100 text-blue-800">{{ rtrim(rtrim(number_format((float) $ingredient->quantity, 3), '0'), '.') }} {{ $ingredient->unit }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $ingredient->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ ucfirst($ingredient->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ route('ingredients.edit', $ingredient) }}" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </a>
                                            <form action="{{ route('ingredients.destroy', $ingredient) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?')">
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
                    {{ $ingredients->links('pagination::tailwind') }}
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-carrot text-gray-300 text-5xl mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No ingredients yet</h3>
                    <p class="text-gray-600 mb-4">Start by adding your raw ingredient stock (rice, chicken, oil, bottled drinks, etc.)</p>
                    <a href="{{ route('ingredients.create') }}" class="inline-block bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-plus mr-2"></i>Add Ingredient
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
