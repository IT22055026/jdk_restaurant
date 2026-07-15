@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
    <div>
        <div class="mb-8">
            <div>
                <a href="{{ route('employees.index') }}" class="flex items-center justify-center w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 hover:text-gray-900 transition-colors mb-2" title="Back">
                    <i class="fas fa-arrow-left text-sm"></i>
                </a>
                @section('breadcrumb')
                    <nav class="text-sm text-gray-600" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-2">
                            <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a></li>
                            <li class="text-gray-300">/</li>
                            <li class="text-gray-900 font-semibold">Edit User</li>
                        </ol>
                    </nav>
                <h1 class="text-4xl font-bold text-gray-900">Edit User</h1>
                @endsection
            </div>
            <p class="text-gray-600 mt-2">Update user information and status</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-8 w-full">
            <form action="{{ route('employees.update', $employee) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- User Information Section -->
                <div class="pb-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Account Information</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-semibold text-gray-900 mb-2">Full Name <span class="text-red-600">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $employee->user->name) }}" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('name') ? 'border-red-600' : '' }}"
                                placeholder="John Doe">
                            @error('name')
                                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-900 mb-2">Email <span class="text-red-600">*</span></label>
                            <input type="email" name="email" id="email" value="{{ old('email', $employee->user->email) }}" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('email') ? 'border-red-600' : '' }}"
                                placeholder="john@example.com">
                            @error('email')
                                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-semibold text-gray-900 mb-2">Password <span class="text-gray-500">(Leave blank to keep current)</span></label>
                            <input type="password" name="password" id="password"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('password') ? 'border-red-600' : '' }}"
                                placeholder="••••••••">
                            @error('password')
                                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-semibold text-gray-900 mb-2">Confirm Password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="••••••••">
                        </div>

                        <div>
                            <label for="role_id" class="block text-sm font-semibold text-gray-900 mb-2">Role <span class="text-red-600">*</span></label>
                            <select name="role_id" id="role_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('role_id') ? 'border-red-600' : '' }}">
                                <option value="">Select role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ old('role_id', $employee->user->role_id) == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                                @endforeach
                            </select>
                            @error('role_id')
                                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-semibold text-gray-900 mb-2">Status <span class="text-red-600">*</span></label>
                            <select name="status" id="status" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('status') ? 'border-red-600' : '' }}">
                                <option value="active" {{ old('status', $employee->user->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $employee->user->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Employee Information Section -->
                <div class="pb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Employee Information</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="phone" class="block text-sm font-semibold text-gray-900 mb-2">Phone <span class="text-gray-500">(Optional)</span></label>
                            <input type="tel" name="phone" id="phone" value="{{ old('phone', $employee->phone) }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="+1 (555) 000-0000">
                            @error('phone')
                                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="hire_date" class="block text-sm font-semibold text-gray-900 mb-2">Hire Date <span class="text-gray-500">(Optional)</span></label>
                            <input type="date" name="hire_date" id="hire_date" value="{{ old('hire_date', $employee->hire_date?->format('Y-m-d')) }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('hire_date')
                                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="salary" class="block text-sm font-semibold text-gray-900 mb-2">Salary <span class="text-gray-500">(Optional)</span></label>
                            <input type="number" name="salary" id="salary" value="{{ old('salary', $employee->salary) }}" step="0.01" min="0"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="0.00">
                            @error('salary')
                                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div></div>

                        <div>
                            <label for="address" class="block text-sm font-semibold text-gray-900 mb-2">Address <span class="text-gray-500">(Optional)</span></label>
                            <input type="text" name="address" id="address" value="{{ old('address', $employee->address) }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="123 Main Street">
                            @error('address')
                                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="city" class="block text-sm font-semibold text-gray-900 mb-2">City <span class="text-gray-500">(Optional)</span></label>
                            <input type="text" name="city" id="city" value="{{ old('city', $employee->city) }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="New York">
                            @error('city')
                                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="state" class="block text-sm font-semibold text-gray-900 mb-2">State <span class="text-gray-500">(Optional)</span></label>
                            <input type="text" name="state" id="state" value="{{ old('state', $employee->state) }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="NY">
                            @error('state')
                                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="postal_code" class="block text-sm font-semibold text-gray-900 mb-2">Postal Code <span class="text-gray-500">(Optional)</span></label>
                            <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $employee->postal_code) }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="10001">
                            @error('postal_code')
                                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-save mr-2"></i>Update User
                    </button>
                    <a href="{{ route('employees.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-900 px-6 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
