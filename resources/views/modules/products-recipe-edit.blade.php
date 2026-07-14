@extends('layouts.app')

@section('title', 'Recipe - ' . $product->name)

@section('content')
    <div>
        <div class="mb-8">
            <div>
                <a href="{{ route('products.index') }}" class="flex items-center justify-center w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 hover:text-gray-900 transition-colors mb-2" title="Back">
                    <i class="fas fa-arrow-left text-sm"></i>
                </a>
                @section('breadcrumb')
                    <nav class="text-sm text-gray-600" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-2">
                            <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a></li>
                            <li class="text-gray-300">/</li>
                            <li><a href="{{ route('inventory.index') }}" class="text-gray-500 hover:text-gray-700">Inventory & Products</a></li>
                            <li class="text-gray-300">/</li>
                            <li class="text-gray-900 font-semibold">Recipe: {{ $product->name }}</li>
                        </ol>
                    </nav>
                <h1 class="text-4xl font-bold text-gray-900">Recipe: {{ $product->name }}</h1>
                @endsection
            </div>
            <p class="text-gray-600 mt-2">Ingredients consumed per 1 unit sold. Deducted automatically when a KOT is sent for this item.</p>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            </div>
        @endif

        @if($ingredients->isEmpty())
            <div class="mb-6 bg-amber-50 border border-amber-300 text-amber-800 rounded-lg px-5 py-3 flex items-center gap-3">
                <i class="fas fa-triangle-exclamation text-amber-500 flex-shrink-0"></i>
                <span class="text-sm font-medium">No ingredients exist yet. <a href="{{ route('ingredients.create') }}" class="underline font-semibold">Add an ingredient</a> before building a recipe.</span>
            </div>
        @endif

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-8 w-full">
            <form action="{{ route('products.recipe.update', $product) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div id="recipe-rows" class="space-y-3">
                    @forelse($product->ingredients as $ingredient)
                        <div class="recipe-row grid grid-cols-1 md:grid-cols-[1fr_180px_40px] gap-3 items-start">
                            <select name="ingredient_id[]" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                @foreach($ingredients as $option)
                                    <option value="{{ $option->id }}" {{ $option->id == $ingredient->id ? 'selected' : '' }}>
                                        {{ $option->name }} ({{ $option->unit }})
                                    </option>
                                @endforeach
                            </select>
                            <input type="number" name="quantity_per_unit[]" step="0.0001" min="0.0001" required
                                value="{{ $ingredient->pivot->quantity_per_unit }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                placeholder="Qty per unit">
                            <button type="button" onclick="this.closest('.recipe-row').remove()"
                                class="w-full h-full flex items-center justify-center text-red-600 hover:text-red-800" title="Remove">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    @empty
                    @endforelse
                </div>

                <button type="button" id="add-recipe-row" class="text-red-600 hover:text-red-800 text-sm font-semibold">
                    <i class="fas fa-plus mr-1"></i>Add Ingredient
                </button>

                <div class="flex gap-4 pt-4 border-t border-gray-100">
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-save mr-2"></i>Save Recipe
                    </button>
                    <a href="{{ route('products.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-900 px-6 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<template id="recipe-row-template">
    <div class="recipe-row grid grid-cols-1 md:grid-cols-[1fr_180px_40px] gap-3 items-start">
        <select name="ingredient_id[]" required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
            <option value="">Select ingredient</option>
            @foreach($ingredients as $option)
                <option value="{{ $option->id }}">{{ $option->name }} ({{ $option->unit }})</option>
            @endforeach
        </select>
        <input type="number" name="quantity_per_unit[]" step="0.0001" min="0.0001" required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
            placeholder="Qty per unit">
        <button type="button" onclick="this.closest('.recipe-row').remove()"
            class="w-full h-full flex items-center justify-center text-red-600 hover:text-red-800" title="Remove">
            <i class="fas fa-trash"></i>
        </button>
    </div>
</template>
<script>
    document.getElementById('add-recipe-row').addEventListener('click', function () {
        const template = document.getElementById('recipe-row-template');
        const clone = template.content.cloneNode(true);
        document.getElementById('recipe-rows').appendChild(clone);
    });
</script>
@endsection
