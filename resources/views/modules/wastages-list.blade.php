@extends('layouts.app')

@section('title', 'Wastage Management')

@section('content')
    <div>
        <div class="mb-8 flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-4xl font-bold text-gray-900">Wastage Management</h1>
                <p class="text-gray-600 mt-2">Track product wastage and losses</p>
            </div>
            <a href="{{ route('wastages.create') }}" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                <i class="fas fa-plus mr-2"></i>Record Wastage
            </a>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
            @if($wastages->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Product</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Quantity</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Reason</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Date</th>
                                <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($wastages as $wastage)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $wastage->product->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <span class="px-3 py-1 rounded-lg bg-red-100 text-red-800">
                                            {{ $wastage->quantity }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $wastage->reason }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $wastage->date->format('M d, Y') }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ route('wastages.edit', $wastage) }}" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </a>
                                            <form action="{{ route('wastages.destroy', $wastage) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-semibold">
                                                    <i class="fas fa-trash mr-1"></i>Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $wastages->links('pagination::tailwind') }}
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-trash text-gray-300 text-5xl mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No wastage recorded yet</h3>
                    <p class="text-gray-600 mb-4">Start recording wastage entries</p>
                    <a href="{{ route('wastages.create') }}" class="inline-block bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-plus mr-2"></i>Record Wastage
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
