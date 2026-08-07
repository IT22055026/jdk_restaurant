@extends('layouts.app')

@section('title', 'Edit Purchase')

@section('content')
    <div>
        <div class="mb-8">
            <div>
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
                            <li class="text-gray-900 font-semibold">Edit Purchase</li>
                        </ol>
                    </nav>
                <h1 class="text-4xl font-bold text-gray-900 mt-2">Edit Purchase</h1>
                @endsection
            </div>
            <p class="text-gray-600 mt-2">Modify details for purchase #{{ $purchase->id }}</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-8 w-full">
            <form action="{{ route('purchases.update', $purchase) }}" method="POST" class="space-y-0">
                @csrf
                @method('PUT')

                @include('modules.purchases._form')

                <div class="flex gap-4 pt-6">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-save mr-2"></i>Update Purchase
                    </button>
                    <a href="{{ route('purchases.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-900 px-6 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
