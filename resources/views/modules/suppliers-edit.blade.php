@extends('layouts.app')

@section('title', 'Edit Supplier')

@section('content')
    <div>
        <div class="mb-8">
            <div>
                <a href="{{ route('suppliers.index') }}" class="flex items-center justify-center w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 hover:text-gray-900 transition-colors mb-2" title="Back">
                    <i class="fas fa-arrow-left text-sm"></i>
                </a>
                <h1 class="text-4xl font-bold text-gray-900">Edit Supplier</h1>
            </div>
            <p class="text-gray-600 mt-2">Update supplier information</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-8 max-w-3xl">
            <form action="{{ route('suppliers.update', $supplier) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-900 mb-2">Supplier Name <span class="text-red-600">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name', $supplier->name) }}" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent {{ $errors->has('name') ? 'border-red-600' : '' }}"
                            placeholder="Supplier name">
                        @error('name')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="contact_name" class="block text-sm font-semibold text-gray-900 mb-2">Company Name</label>
                        <input type="text" name="contact_name" id="contact_name" value="{{ old('contact_name', $supplier->contact_name) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent {{ $errors->has('contact_name') ? 'border-red-600' : '' }}"
                            placeholder="Primary contact">
                        @error('contact_name')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-900 mb-2">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $supplier->email) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent {{ $errors->has('email') ? 'border-red-600' : '' }}"
                            placeholder="name@example.com">
                        @error('email')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-semibold text-gray-900 mb-2">Phone</label>
                        <input type="tel" name="phone" id="phone" value="{{ old('phone', $supplier->phone) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent {{ $errors->has('phone') ? 'border-red-600' : '' }}"
                            placeholder="Phone number">
                        @error('phone')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="city" class="block text-sm font-semibold text-gray-900 mb-2">City</label>
                        <input type="text" name="city" id="city" value="{{ old('city', $supplier->city) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent {{ $errors->has('city') ? 'border-red-600' : '' }}"
                            placeholder="City">
                        @error('city')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="state" class="block text-sm font-semibold text-gray-900 mb-2">State</label>
                        <input type="text" name="state" id="state" value="{{ old('state', $supplier->state) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent {{ $errors->has('state') ? 'border-red-600' : '' }}"
                            placeholder="State">
                        @error('state')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="postal_code" class="block text-sm font-semibold text-gray-900 mb-2">Postal Code</label>
                        <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $supplier->postal_code) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent {{ $errors->has('postal_code') ? 'border-red-600' : '' }}"
                            placeholder="Postal code">
                        @error('postal_code')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-semibold text-gray-900 mb-2">Status <span class="text-red-600">*</span></label>
                        <select name="status" id="status" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent {{ $errors->has('status') ? 'border-red-600' : '' }}">
                            <option value="active" {{ old('status', $supplier->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $supplier->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="address" class="block text-sm font-semibold text-gray-900 mb-2">Address</label>
                    <textarea name="address" id="address" rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                        placeholder="Street address">{{ old('address', $supplier->address) }}</textarea>
                    @error('address')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="notes" class="block text-sm font-semibold text-gray-900 mb-2">Notes</label>
                    <textarea name="notes" id="notes" rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                        placeholder="Additional notes">{{ old('notes', $supplier->notes) }}</textarea>
                    @error('notes')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-save mr-2"></i>Update Supplier
                    </button>
                    <a href="{{ route('suppliers.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-900 px-6 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
