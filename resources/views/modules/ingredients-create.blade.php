@extends('layouts.app')

@section('title', 'Add Included Item')

@section('content')
    <div>
        <div class="mb-8">
            <div>
                <a href="{{ route('ingredients.index') }}" class="flex items-center justify-center w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 hover:text-gray-900 transition-colors mb-2" title="Back">
                    <i class="fas fa-arrow-left text-sm"></i>
                </a>
                @section('breadcrumb')
                    <nav class="text-sm text-gray-600" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-2">
                            <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a></li>
                            <li class="text-gray-300">/</li>
                            <li class="text-gray-900 font-semibold">Add Included Item</li>
                        </ol>
                    </nav>
                <h1 class="text-4xl font-bold text-gray-900">Add Included Item</h1>
                @endsection
            </div>
            <p class="text-gray-600 mt-2">Create a new included item stock entry</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-8 w-full">
            <form action="{{ route('ingredients.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-900 mb-2">Item Name <span class="text-red-600">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('name') ? 'border-red-600' : '' }}"
                            placeholder="e.g. Rice, Chicken, Pepsi 400ml">
                        @error('name')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="unit" class="block text-sm font-semibold text-gray-900 mb-2">Unit <span class="text-red-600">*</span></label>
                        <input type="text" name="unit" id="unit" value="{{ old('unit') }}" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('unit') ? 'border-red-600' : '' }}"
                            placeholder="e.g. kg, g, litre, ml, pcs, bottle">
                        @error('unit')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="quantity" class="block text-sm font-semibold text-gray-900 mb-2">Current Stock <span class="text-red-600">*</span></label>
                        <input type="number" name="quantity" id="quantity" value="{{ old('quantity', 0) }}" step="0.001" min="0" required
                            onfocus="this.select()"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('quantity') ? 'border-red-600' : '' }}"
                            placeholder="0">
                        @error('quantity')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="low_stock_threshold" class="block text-sm font-semibold text-gray-900 mb-2">Low Stock Threshold</label>
                        <input type="number" name="low_stock_threshold" id="low_stock_threshold" value="{{ old('low_stock_threshold') }}" step="0.001" min="0"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('low_stock_threshold') ? 'border-red-600' : '' }}"
                            placeholder="e.g. 5">
                        @error('low_stock_threshold')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-2">Alert when stock falls to or below this amount.</p>
                    </div>

                    <div>
                        <label for="supplier_id" class="block text-sm font-semibold text-gray-900 mb-2">Supplier</label>
                        <select name="supplier_id" id="supplier_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('supplier_id') ? 'border-red-600' : '' }}">
                            <option value="">Select supplier</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="cost_per_unit" class="block text-sm font-semibold text-gray-900 mb-2">Cost per Unit</label>
                        <input type="number" name="cost_per_unit" id="cost_per_unit" value="{{ old('cost_per_unit') }}" step="0.01" min="0"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('cost_per_unit') ? 'border-red-600' : '' }}"
                            placeholder="0.00">
                        @error('cost_per_unit')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-2">What you pay the supplier — for stock valuation, not what a customer is charged.</p>
                    </div>

                    <div>
                        <label for="selling_price" class="block text-sm font-semibold text-gray-900 mb-2">Selling Price (per unit) <span class="text-red-600">*</span></label>
                        <input type="number" name="selling_price" id="selling_price" value="{{ old('selling_price') }}" step="0.01" min="0" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('selling_price') ? 'border-red-600' : '' }}"
                            placeholder="0.00">
                        @error('selling_price')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-2">What to charge a customer buying this directly in POS (e.g. "extra mayonnaise").</p>
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-semibold text-gray-900 mb-2">Status <span class="text-red-600">*</span></label>
                        <select name="status" id="status" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('status') ? 'border-red-600' : '' }}">
                            <option value="">Select status</option>
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="image" class="block text-sm font-semibold text-gray-900 mb-2">Photo</label>
                    <input type="file" name="image" id="image" accept=".jpg,.jpeg,.png,.webp,.gif"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('image') ? 'border-red-600' : '' }}"
                        onchange="previewImage(this)">
                    <p class="text-xs text-gray-500 mt-1">Accepted formats: .png / .jpg (max 2MB)</p>
                    @error('image')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                    <img id="image-preview" src="#" alt="Preview" class="mt-2 w-28 h-28 object-cover rounded-lg border border-gray-200 hidden">
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-save mr-2"></i>Save Included Item
                    </button>
                    <a href="{{ route('ingredients.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-900 px-6 py-2 rounded-lg font-semibold transition-colors">
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
</script>
@endsection
