@extends('layouts.app')

@section('title', 'Add Offer')

@section('content')
    <div>
        <div class="mb-8">
            <div>
                <a href="{{ route('offers.index') }}" class="flex items-center justify-center w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 hover:text-gray-900 transition-colors mb-2" title="Back">
                    <i class="fas fa-arrow-left text-sm"></i>
                </a>
                @section('breadcrumb')
                    <nav class="text-sm text-gray-600" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-2">
                            <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a></li>
                            <li class="text-gray-300">/</li>
                            <li><a href="{{ route('offers.index') }}" class="text-gray-500 hover:text-gray-700">Discounts & Offers</a></li>
                            <li class="text-gray-300">/</li>
                            <li class="text-gray-900 font-semibold">Add Offer</li>
                        </ol>
                    </nav>
                <h1 class="text-4xl font-bold text-gray-900">Add Offer</h1>
                @endsection
            </div>
            <p class="text-gray-600 mt-2">Bundle included items into a new offer</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-8 w-full">
            <form action="{{ route('offers.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-900 mb-2">Offer Name <span class="text-red-600">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('name') ? 'border-red-600' : '' }}"
                            placeholder="e.g. Family Combo">
                        @error('name')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="price" class="block text-sm font-semibold text-gray-900 mb-2">Price <span class="text-red-600">*</span></label>
                        <div class="relative">
                            <span class="absolute left-4 top-2.5 text-gray-500 font-semibold">Rs.</span>
                            <input type="number" name="price" id="price" value="{{ old('price') }}" step="0.01" min="0" required
                                class="w-full pl-12 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('price') ? 'border-red-600' : '' }}"
                                placeholder="0.00">
                        </div>
                        @error('price')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-semibold text-gray-900 mb-2">Description <span class="text-gray-400 font-normal">— optional</span></label>
                    <textarea name="description" id="description" rows="2"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Short description shown in POS">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="image" class="block text-sm font-semibold text-gray-900 mb-2">Offer Image <span class="text-gray-400 font-normal">— optional</span></label>
                    <input type="file" name="image" id="image" accept=".jpg,.jpeg,.png,.webp,.gif"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('image') ? 'border-red-600' : '' }}"
                        onchange="previewImage(this)">
                    <p class="text-xs text-gray-500 mt-1">Accepted formats: .png / .jpg (max 2MB)</p>
                    @error('image')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                    <img id="image-preview" src="#" alt="Preview" class="mt-2 w-28 h-28 object-cover rounded-lg border border-gray-200 hidden">
                </div>

                <div>
                    <label class="inline-flex items-center space-x-2">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm font-semibold text-gray-900">Active (visible in POS)</span>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Included Items <span class="text-red-600">*</span></label>
                    <p class="text-sm text-gray-500 mb-3">Check each included item that goes into this offer, and how much of it is used per offer sold.</p>

                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 w-10"></th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Included Item</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 w-48">Quantity per Offer</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($ingredients as $ingredient)
                                    <tr>
                                        <td class="px-4 py-2">
                                            <input type="checkbox" name="ingredient_ids[]" value="{{ $ingredient->id }}"
                                                {{ collect(old('ingredient_ids'))->contains($ingredient->id) ? 'checked' : '' }}
                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-900">{{ $ingredient->name }}</td>
                                        <td class="px-4 py-2">
                                            <div class="relative">
                                                <input type="number" name="quantities[{{ $ingredient->id }}]" step="0.001" min="0.001"
                                                    value="{{ old('quantities.' . $ingredient->id, 1) }}"
                                                    class="w-full pr-14 pl-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                <span class="absolute right-3 top-1.5 text-xs text-gray-400">{{ $ingredient->unit }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">
                                            No included items yet — add some under Inventory → Included Items first.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @error('ingredient_ids')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-save mr-2"></i>Save Offer
                    </button>
                    <a href="{{ route('offers.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-900 px-6 py-2 rounded-lg font-semibold transition-colors">
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
        } else {
            preview.classList.add('hidden');
        }
    }
</script>
@endsection
