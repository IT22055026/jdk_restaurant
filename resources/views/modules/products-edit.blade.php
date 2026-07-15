@extends('layouts.app')

@section('title', 'Edit Product')

@section('content')
    <div>
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <nav class="text-sm text-gray-600 mb-3" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-2">
                            <li>
                                <a href="{{ route('inventory.index') }}" class="text-gray-500 hover:text-gray-700">Inventory & Products</a>
                            </li>
                            <li class="text-gray-300">/</li>
                            <li class="text-gray-900 font-semibold">Edit Product</li>
                        </ol>
                    </nav>

                    <h1 class="text-4xl font-bold text-gray-900">Edit Product</h1>
                    <p class="text-gray-600 mt-2">Update product information</p>
                </div>
                <a href="{{ route('inventory.index') }}" class="flex items-center justify-center w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 hover:text-gray-900 transition-colors mb-2" title="Back">
                    <i class="fas fa-arrow-left text-sm"></i>
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-8 w-full">
            <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-900 mb-2">Product Name <span class="text-red-600">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('name') ? 'border-red-600' : '' }}"
                            placeholder="Product name">
                        @error('name')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Finished Good</label>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="is_finished_good" id="is_finished_good" value="1" {{ old('is_finished_good', $product->is_finished_good) ? 'checked' : '' }}
                                class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-blue-500">
                            Sold as-is (e.g. Pepsi, Coca-Cola)
                        </label>
                        @error('is_finished_good')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-2">Off = this item is cooked from included items (e.g. Biriyani) — stock is tracked via its Recipe instead of the Quantity field.</p>
                    </div>

                    <div>
                        <label for="category_id" class="block text-sm font-semibold text-gray-900 mb-2">Category</label>
                        <select name="category_id" id="category_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('category_id') ? 'border-red-600' : '' }}">
                            <option value="">Select category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="supplier_id" class="block text-sm font-semibold text-gray-900 mb-2">Supplier</label>
                        <select name="supplier_id" id="supplier_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('supplier_id') ? 'border-red-600' : '' }}">
                            <option value="">Select supplier</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ old('supplier_id', $product->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="product_code" class="block text-sm font-semibold text-gray-900 mb-2">Product Code</label>
                        <input type="text" name="product_code" id="product_code" value="{{ old('product_code', $product->product_code) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('product_code') ? 'border-red-600' : '' }}"
                            placeholder="SKU or code">
                        @error('product_code')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="barcode" class="block text-sm font-semibold text-gray-900 mb-2">Barcode</label>
                        <input type="text" name="barcode" id="barcode" value="{{ old('barcode', $product->barcode) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('barcode') ? 'border-red-600' : '' }}"
                            placeholder="Barcode">
                        @error('barcode')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="selling_price" class="block text-sm font-semibold text-gray-900 mb-2">Selling Price <span class="text-red-600">*</span></label>
                        <input type="number" name="selling_price" id="selling_price" value="{{ old('selling_price', $product->selling_price) }}" step="0.01" min="0" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('selling_price') ? 'border-red-600' : '' }}"
                            placeholder="0.00">
                        @error('selling_price')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="cost_price" class="block text-sm font-semibold text-gray-900 mb-2">Cost Price</label>
                        <input type="number" name="cost_price" id="cost_price" value="{{ old('cost_price', $product->cost_price) }}" step="0.01" min="0"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('cost_price') ? 'border-red-600' : '' }}"
                            placeholder="0.00">
                        @error('cost_price')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="discount" class="block text-sm font-semibold text-gray-900 mb-2">Discount (%)</label>
                        <input type="number" name="discount" id="discount" value="{{ old('discount', $product->discount) }}" step="0.01" min="0" max="100"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('discount') ? 'border-red-600' : '' }}"
                            placeholder="0">
                        @error('discount')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="quantity" class="block text-sm font-semibold text-gray-900 mb-2">Quantity <span class="text-red-600">*</span></label>
                        <input type="number" name="quantity" id="quantity" value="{{ old('quantity', $product->quantity) }}" min="0"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('quantity') ? 'border-red-600' : '' }}"
                            placeholder="0">
                        @error('quantity')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-2">Required only for Finished Goods.</p>
                    </div>

                    <div>
                        <label for="low_stock_threshold" class="block text-sm font-semibold text-gray-900 mb-2">Low Stock Threshold</label>
                        <input type="number" name="low_stock_threshold" id="low_stock_threshold" value="{{ old('low_stock_threshold', $product->low_stock_threshold) }}" min="0"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('low_stock_threshold') ? 'border-red-600' : '' }}"
                            placeholder="e.g. 5">
                        @error('low_stock_threshold')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-2">Alert when stock falls to or below this number.</p>
                    </div>

                    <!-- Unlimited Stock option removed; use Finished Good to indicate BOM vs tracked -->

                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Finished Good</label>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="is_finished_good" id="is_finished_good" value="1" {{ old('is_finished_good', $product->is_finished_good) ? 'checked' : '' }}
                                class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-blue-500">
                            Sold as-is (e.g. Pepsi, Coca-Cola)
                        </label>
                        @error('is_finished_good')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-2">Off = this item is cooked from included items (e.g. Biriyani) — stock is tracked via its Recipe instead of the Quantity field.</p>
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-semibold text-gray-900 mb-2">Status <span class="text-red-600">*</span></label>
                        <select name="status" id="status" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('status') ? 'border-red-600' : '' }}">
                            <option value="active" {{ old('status', $product->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $product->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-semibold text-gray-900 mb-2">Description</label>
                    <textarea name="description" id="description" rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Product description">{{ old('description', $product->description) }}</textarea>
                    @error('description')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="image" class="block text-sm font-semibold text-gray-900 mb-2">Product Image</label>
                    <input type="file" name="image" id="image" accept=".jpg,.jpeg,.png,.webp,.gif"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('image') ? 'border-red-600' : '' }}"
                        onchange="previewImage(this)">
                    <p class="text-xs text-gray-500 mt-1">Accepted formats: .png / .jpg (max 2MB)</p>
                    @error('image')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                    @if($product->image)
                        <p class="text-xs text-gray-500 mt-2">Current image:</p>
                        <img id="image-preview"
                            src="{{ Str::startsWith($product->image, ['http://', 'https://']) ? $product->image : asset("storage/{$product->image}") }}"
                            alt="{{ $product->name }}"
                            class="mt-2 w-28 h-28 object-cover rounded-lg border border-gray-200"
                        >
                    @else
                        <img id="image-preview" src="#" alt="Preview" class="mt-2 w-28 h-28 object-cover rounded-lg border border-gray-200 hidden">
                    @endif
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-save mr-2"></i>Update Product
                    </button>
                    <a href="{{ route('inventory.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-900 px-6 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    function previewImage(input) {
        const preview = document.getElementById('image-preview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const finishedGoodCheckbox = document.querySelector('input[name="is_finished_good"]');
        const quantityInput = document.getElementById('quantity');
        const thresholdInput = document.getElementById('low_stock_threshold');

        const toggleQuantity = () => {
            const tracksOwnQuantity = finishedGoodCheckbox.checked;
            quantityInput.disabled = !tracksOwnQuantity;
            quantityInput.required = tracksOwnQuantity;
            thresholdInput.disabled = !tracksOwnQuantity;
            if (!tracksOwnQuantity) {
                quantityInput.value = 0;
                thresholdInput.value = '';
            }
        };

        toggleQuantity();
        finishedGoodCheckbox.addEventListener('change', toggleQuantity);
    });
</script>
@endsection
