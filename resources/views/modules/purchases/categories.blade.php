@extends('layouts.app')

@section('title', 'Manage Purchase Categories')

@section('content')
    <div>
        <div class="mb-6">
            <a href="{{ route('purchases.index') }}" class="flex items-center justify-center w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 hover:text-gray-900 transition-colors mb-2" title="Back">
                <i class="fas fa-arrow-left text-sm"></i>
            </a>
            @section('breadcrumb')
                <nav class="text-sm text-gray-600" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-2">
                        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a></li>
                        <li class="text-gray-300">/</li>
                        <li><a href="{{ route('purchases.index') }}" class="text-gray-500 hover:text-gray-700">Purchases & Expenses</a></li>
                        <li class="text-gray-300">/</li>
                        <li class="text-gray-900 font-semibold">Manage Categories</li>
                    </ol>
                </nav>
            <h1 class="text-4xl font-bold text-gray-900 mt-2">Manage Purchase Categories</h1>
            @endsection
            <p class="text-gray-600 mt-2">Add new main categories and sub-categories, or rename / deactivate existing ones</p>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-2">
                <i class="fas fa-check-circle"></i>{{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center gap-2">
                <i class="fas fa-triangle-exclamation"></i>{{ session('error') }}
            </div>
        @endif
        @error('name')
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center gap-2">
                <i class="fas fa-triangle-exclamation"></i>{{ $message }}
            </div>
        @enderror

        <!-- Add Main Category -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-6">
            <h2 class="text-sm font-bold text-gray-900 mb-3">Add a Main Category</h2>
            <form action="{{ route('purchase-categories.store') }}" method="POST" class="flex flex-wrap items-end gap-3">
                @csrf
                <input type="hidden" name="type" value="main">
                <div class="flex-1 min-w-[240px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Category Name</label>
                    <input type="text" name="name" required placeholder="e.g. Beverages"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg font-semibold transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add Main Category
                </button>
            </form>
        </div>

        <!-- Category Tree -->
        <div class="space-y-6">
            @foreach($categories as $main)
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-5 bg-gray-50 border-b border-gray-100 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="w-9 h-9 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                                <i class="fas fa-folder"></i>
                            </span>
                            <div>
                                <h3 class="font-bold text-gray-900">{{ $main->name }}</h3>
                                <p class="text-xs text-gray-400">{{ $main->children->count() }} sub-categories</p>
                            </div>
                            @if(!$main->is_active)
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">Inactive</span>
                            @endif
                        </div>

                        <div class="flex items-center gap-4">
                            <form action="{{ route('purchase-categories.update', $main) }}" method="POST" class="flex items-center gap-2">
                                @csrf
                                @method('PUT')
                                <input type="text" name="name" value="{{ $main->name }}" required
                                    class="text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <label class="inline-flex items-center gap-1.5 text-xs text-gray-600">
                                    <input type="checkbox" name="is_active" value="1" {{ $main->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600">
                                    Active
                                </label>
                                <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">Save</button>
                            </form>
                            <form action="{{ route('purchase-categories.destroy', $main) }}" method="POST" onsubmit="return confirm('Delete this main category?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-semibold">
                                    <i class="fas fa-trash mr-1"></i>Delete
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="p-5">
                        @if($main->children->isNotEmpty())
                            <div class="divide-y divide-gray-100 mb-4">
                                @foreach($main->children as $sub)
                                    <div class="py-3 flex flex-wrap items-center justify-between gap-3">
                                        <div class="flex items-center gap-2 text-sm text-gray-800">
                                            <i class="fas fa-angle-right text-gray-300"></i>
                                            {{ $sub->name }}
                                            @if(!$sub->is_active)
                                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">Inactive</span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <form action="{{ route('purchase-categories.update', $sub) }}" method="POST" class="flex items-center gap-2">
                                                @csrf
                                                @method('PUT')
                                                <input type="text" name="name" value="{{ $sub->name }}" required
                                                    class="text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                <label class="inline-flex items-center gap-1.5 text-xs text-gray-600">
                                                    <input type="checkbox" name="is_active" value="1" {{ $sub->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600">
                                                    Active
                                                </label>
                                                <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">Save</button>
                                            </form>
                                            <form action="{{ route('purchase-categories.destroy', $sub) }}" method="POST" onsubmit="return confirm('Delete this sub-category?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-semibold">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400 mb-4">No sub-categories yet.</p>
                        @endif

                        <form action="{{ route('purchase-categories.store') }}" method="POST" class="flex flex-wrap items-end gap-3 pt-3 border-t border-gray-100">
                            @csrf
                            <input type="hidden" name="type" value="sub">
                            <input type="hidden" name="parent_id" value="{{ $main->id }}">
                            <div class="flex-1 min-w-[200px]">
                                <label class="block text-xs font-medium text-gray-500 mb-1">New sub-category under {{ $main->name }}</label>
                                <input type="text" name="name" required placeholder="e.g. Radish"
                                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-1.5 text-sm rounded-lg font-semibold transition-colors">
                                <i class="fas fa-plus mr-1"></i>Add Sub-category
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
