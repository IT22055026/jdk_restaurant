@extends('layouts.app')

@section('title', 'Edit Wastage')

@section('content')
    <div>
        <div class="mb-8">
            <div>
                <a href="{{ route('wastage.index') }}" class="flex items-center justify-center w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 hover:text-gray-900 transition-colors mb-2" title="Back">
                    <i class="fas fa-arrow-left text-sm"></i>
                </a>
                @section('breadcrumb')
                    <nav class="text-sm text-gray-600" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-2">
                            <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a></li>
                            <li class="text-gray-300">/</li>
                            <li class="text-gray-900 font-semibold">Edit Wastage</li>
                        </ol>
                    </nav>
                <h1 class="text-4xl font-bold text-gray-900">Edit Wastage</h1>
                @endsection
            </div>
            <p class="text-gray-600 mt-2">Update wastage information</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-8 w-full">
            <form action="{{ route('wastages.update', $wastage) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <label for="product_id" class="block text-sm font-semibold text-gray-900 mb-2">Product <span class="text-red-600">*</span></label>
                    <select name="product_id" id="product_id" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent {{ $errors->has('product_id') ? 'border-red-600' : '' }}">
                        <option value="">Select a product</option>
                        @foreach($products as $product)
                            @php $avail = $product->is_unlimited_stock ? null : $product->availableStock(); @endphp
                            <option value="{{ $product->id }}" {{ old('product_id', $wastage->product_id) == $product->id ? 'selected' : '' }}>
                                {{ $product->name }} (Available: {{ $product->is_unlimited_stock ? '∞' : $avail }})
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="quantity" class="block text-sm font-semibold text-gray-900 mb-2">Quantity Wasted <span class="text-red-600">*</span></label>
                    <input type="number" name="quantity" id="quantity" value="{{ old('quantity', $wastage->quantity) }}" required min="1"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent {{ $errors->has('quantity') ? 'border-red-600' : '' }}"
                        placeholder="0">
                    @error('quantity')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="reason" class="block text-sm font-semibold text-gray-900 mb-2">Reason <span class="text-red-600">*</span></label>
                    <input type="text" name="reason" id="reason" value="{{ old('reason', $wastage->reason) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent {{ $errors->has('reason') ? 'border-red-600' : '' }}"
                        placeholder="e.g., Expired, Damaged, Spoiled">
                    @error('reason')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="date" class="block text-sm font-semibold text-gray-900 mb-2">Date <span class="text-red-600">*</span></label>
                    <input type="date" name="date" id="date" value="{{ old('date', $wastage->date->format('Y-m-d')) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent {{ $errors->has('date') ? 'border-red-600' : '' }}">
                    @error('date')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="notes" class="block text-sm font-semibold text-gray-900 mb-2">Notes <span class="text-gray-500">(Optional)</span></label>
                    <textarea name="notes" id="notes" rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                        placeholder="Additional notes about the wastage">{{ old('notes', $wastage->notes) }}</textarea>
                    @error('notes')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-save mr-2"></i>Update Wastage
                    </button>
                    <a href="{{ route('wastage.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-900 px-6 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
