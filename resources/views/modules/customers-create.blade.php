@extends('layouts.app')

@section('title', 'Add Customer')

@section('content')
    <div>
        <div class="mb-8">
            <div>
                <a href="{{ route('customers.index') }}" class="flex items-center justify-center w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 hover:text-gray-900 transition-colors mb-2" title="Back">
                    <i class="fas fa-arrow-left text-sm"></i>
                </a>
                @section('breadcrumb')
                    <nav class="text-sm text-gray-600" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-2">
                            <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a></li>
                            <li class="text-gray-300">/</li>
                            <li class="text-gray-900 font-semibold">Add Customer</li>
                        </ol>
                    </nav>
                <h1 class="text-4xl font-bold text-gray-900">Add Customer</h1>
                @endsection
            </div>
            <p class="text-gray-600 mt-2">Create a new customer record</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-8 w-full">
            <form action="{{ route('customers.store') }}" method="POST" class="space-y-6">
                @csrf

                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-900 mb-2">Name <span class="text-red-600">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('name') ? 'border-red-600' : '' }}"
                        placeholder="Customer name">
                    @error('name')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div class="relative">
                    <label for="phone_number" class="block text-sm font-semibold text-gray-900 mb-2">Phone Number <span class="text-red-600">*</span></label>
                    <input type="tel" name="phone_number" id="phone_number" value="{{ old('phone_number') }}" required autocomplete="off"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('phone_number') ? 'border-red-600' : '' }}"
                        placeholder="0771234567">
                    <div id="phone-suggestions" class="absolute z-10 w-full bg-white border border-gray-200 rounded-lg shadow-lg mt-1 hidden"></div>
                    @error('phone_number')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-900 mb-2">Email <span class="text-gray-500">(Optional)</span></label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('email') ? 'border-red-600' : '' }}"
                        placeholder="customer@example.com">
                    @error('email')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="address" class="block text-sm font-semibold text-gray-900 mb-2">Address <span class="text-gray-500">(Optional)</span></label>
                    <textarea name="address" id="address" rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Customer address">{{ old('address') }}</textarea>
                    @error('address')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status" class="block text-sm font-semibold text-gray-900 mb-2">Status <span class="text-red-600">*</span></label>
                    <select name="status" id="status" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('status') ? 'border-red-600' : '' }}">
                        <option value="">Select status</option>
                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-save mr-2"></i>Save Customer
                    </button>
                    <a href="{{ route('customers.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-900 px-6 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const phoneInput = document.getElementById('phone_number');
        const dropdown = document.getElementById('phone-suggestions');
        const searchUrl = '{{ route('customers.search') }}';

        phoneInput.addEventListener('input', function () {
            const val = this.value.trim();
            if (val.length < 3) {
                dropdown.classList.add('hidden');
                dropdown.innerHTML = '';
                return;
            }
            fetch(searchUrl + '?phone=' + encodeURIComponent(val))
                .then(r => r.json())
                .then(customers => {
                    if (customers.length === 0) {
                        dropdown.classList.add('hidden');
                        dropdown.innerHTML = '';
                        return;
                    }
                    dropdown.innerHTML = customers.map(c => {
                        const escapedName = c.name.replace(/"/g, '&quot;');
                        const escapedEmail = (c.email || '').replace(/"/g, '&quot;');
                        const escapedAddress = (c.address || '').replace(/"/g, '&quot;');
                        const escapedPhone = c.phone_number.replace(/"/g, '&quot;');
                        return `<div class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-sm border-b border-gray-100 last:border-0"
                                    data-phone="${escapedPhone}"
                                    data-name="${escapedName}"
                                    data-email="${escapedEmail}"
                                    data-address="${escapedAddress}">
                                    <span class="font-medium text-gray-900">${c.phone_number}</span>
                                    <span class="text-gray-500 ml-2">— ${c.name}</span>
                                </div>`;
                    }).join('');
                    dropdown.classList.remove('hidden');
                });
        });

        dropdown.addEventListener('click', function (e) {
            const item = e.target.closest('[data-phone]');
            if (!item) return;
            phoneInput.value = item.dataset.phone;
            document.getElementById('name').value = item.dataset.name;
            document.getElementById('email').value = item.dataset.email;
            document.getElementById('address').value = item.dataset.address;
            dropdown.classList.add('hidden');
            dropdown.innerHTML = '';
        });

        document.addEventListener('click', function (e) {
            if (!phoneInput.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    </script>
@endsection
