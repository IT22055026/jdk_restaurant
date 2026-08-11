@extends('layouts.app')

@section('title', 'New Stock Adjustment')

@section('content')
    <div>
        <div class="mb-8">
            <div>
                <a href="{{ route('stock.adjustments.index') }}" class="flex items-center justify-center w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 hover:text-gray-900 transition-colors mb-2" title="Back">
                    <i class="fas fa-arrow-left text-sm"></i>
                </a>
                @section('breadcrumb')
                    <nav class="text-sm text-gray-600" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-2">
                            <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a></li>
                            <li class="text-gray-300">/</li>
                            <li><a href="{{ route('stock.adjustments.index') }}" class="text-gray-500 hover:text-gray-700">Stock Adjustments</a></li>
                            <li class="text-gray-300">/</li>
                            <li class="text-gray-900 font-semibold">New Stock Adjustment</li>
                        </ol>
                    </nav>
                <h1 class="text-4xl font-bold text-gray-900">New Stock Adjustment</h1>
                @endsection
            </div>
            <p class="text-gray-600 mt-2">Manually increase or decrease stock for a finished good or an ingredient</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-8 w-full">
            <form action="{{ route('stock.adjustments.store') }}" method="POST" class="space-y-6">
                @csrf

                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Item Type <span class="text-red-600">*</span></label>
                    <div class="flex gap-6">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="radio" name="item_type" id="item_type_product" value="product" {{ old('item_type', 'product') === 'product' ? 'checked' : '' }}
                                class="w-4 h-4 text-red-600 border-gray-300 focus:ring-blue-500">
                            Finished Good
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="radio" name="item_type" id="item_type_ingredient" value="ingredient" {{ old('item_type') === 'ingredient' ? 'checked' : '' }}
                                class="w-4 h-4 text-red-600 border-gray-300 focus:ring-blue-500">
                            Included Item
                        </label>
                    </div>
                    @error('item_type')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div id="product-field">
                    <label for="product_id" class="block text-sm font-semibold text-gray-900 mb-2">Finished Good <span class="text-red-600">*</span></label>
                    <select name="product_id" id="product_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('product_id') ? 'border-red-600' : '' }}">
                        <option value="">Select a finished good</option>
                        @foreach($products as $product)
                            @php $avail = $product->is_unlimited_stock ? null : $product->availableStock(); @endphp
                            <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                {{ $product->name }} ({{ $product->is_unlimited_stock ? 'Unlimited' : 'Available: ' . $avail }})
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                    @if($products->isEmpty())
                        <p class="text-xs text-amber-600 mt-2">No finished goods yet. Mark a product as "Finished Good" on its edit page first.</p>
                    @endif
                </div>

                <div id="ingredient-field">
                    <label for="ingredient_id" class="block text-sm font-semibold text-gray-900 mb-2">Included Item <span class="text-red-600">*</span></label>
                    <select name="ingredient_id" id="ingredient_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('ingredient_id') ? 'border-red-600' : '' }}">
                        <option value="">Select an included item</option>
                        @foreach($ingredients as $ingredient)
                            <option value="{{ $ingredient->id }}" {{ old('ingredient_id') == $ingredient->id ? 'selected' : '' }}>
                                {{ $ingredient->name }} (Available: {{ rtrim(rtrim(number_format((float) $ingredient->quantity, 3), '0'), '.') }} {{ $ingredient->unit }})
                            </option>
                        @endforeach
                    </select>
                    @error('ingredient_id')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="change_type" class="block text-sm font-semibold text-gray-900 mb-2">Change Type <span class="text-red-600">*</span></label>
                    <select name="change_type" id="change_type" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('change_type') ? 'border-red-600' : '' }}">
                        <option value="">Select type</option>
                        <option value="increase" {{ old('change_type') === 'increase' ? 'selected' : '' }}>Increase</option>
                        <option value="decrease" {{ old('change_type') === 'decrease' ? 'selected' : '' }}>Decrease</option>
                    </select>
                    @error('change_type')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="quantity" class="block text-sm font-semibold text-gray-900 mb-2">Quantity <span class="text-red-600">*</span></label>
                    <input type="number" name="quantity" id="quantity" value="{{ old('quantity') }}" required step="1" min="1"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('quantity') ? 'border-red-600' : '' }}"
                        placeholder="0">
                    @error('quantity')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                    <p id="quantity-hint" class="text-xs text-gray-500 mt-2">Finished goods are counted in whole units.</p>
                </div>

                <div>
                    <label for="reason" class="block text-sm font-semibold text-gray-900 mb-2">Reason <span class="text-red-600">*</span></label>
                    <input type="text" name="reason" id="reason" value="{{ old('reason') }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('reason') ? 'border-red-600' : '' }}"
                        placeholder="e.g., New delivery received, Stock count correction">
                    @error('reason')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-900 mb-2">Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('name') ? 'border-red-600' : '' }}"
                        placeholder="Name of person making the adjustment">
                    @error('name')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-save mr-2"></i>Save Adjustment
                    </button>
                    <a href="{{ route('stock.adjustments.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-900 px-6 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const productRadio = document.getElementById('item_type_product');
        const ingredientRadio = document.getElementById('item_type_ingredient');
        const productField = document.getElementById('product-field');
        const ingredientField = document.getElementById('ingredient-field');
        const productSelect = document.getElementById('product_id');
        const ingredientSelect = document.getElementById('ingredient_id');
        const quantityInput = document.getElementById('quantity');
        const quantityHint = document.getElementById('quantity-hint');

        const toggleItemType = () => {
            const isProduct = productRadio.checked;
            productField.classList.toggle('hidden', !isProduct);
            ingredientField.classList.toggle('hidden', isProduct);
            productSelect.disabled = !isProduct;
            ingredientSelect.disabled = isProduct;

            if (isProduct) {
                quantityInput.step = '1';
                quantityInput.min = '1';
                quantityHint.textContent = 'Finished goods are counted in whole units.';
            } else {
                quantityInput.step = '0.001';
                quantityInput.min = '0.001';
                quantityHint.textContent = 'Included items can use fractional amounts (e.g. 0.25 kg).';
            }
        };

        toggleItemType();
        productRadio.addEventListener('change', toggleItemType);
        ingredientRadio.addEventListener('change', toggleItemType);
    });
</script>
@endsection
