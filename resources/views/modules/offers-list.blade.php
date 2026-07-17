@extends('layouts.app')

@section('title', 'Discounts & Offers')

@section('content')
    <div>
        <div class="mb-8 flex items-center justify-between flex-wrap gap-4">
            <div>
                @section('breadcrumb')
                    <nav class="text-sm text-gray-600" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-2">
                            <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a></li>
                            <li class="text-gray-300">/</li>
                            <li class="text-gray-900 font-semibold">Discounts & Offers</li>
                        </ol>
                    </nav>
                <h1 class="text-4xl font-bold text-gray-900">Discounts & Offers</h1>
                @endsection
                <p class="text-gray-600 mt-2">Bundle included items into offers usable in billing</p>
            </div>
            <a href="{{ route('offers.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                <i class="fas fa-plus mr-2"></i>Add Offer
            </a>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
            @if($offers->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Image</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Name</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Includes</th>
                                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Price</th>
                                <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">Status</th>
                                <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($offers as $offer)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 text-sm">
                                        @if($offer->image)
                                            <div class="h-12 w-12 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0">
                                                <img src="{{ Str::startsWith($offer->image, ['http://', 'https://']) ? $offer->image : asset('storage/' . $offer->image) }}"
                                                    alt="{{ $offer->name }}" class="h-full w-full object-cover">
                                            </div>
                                        @else
                                            <div class="h-12 w-12 rounded-lg bg-gray-100 flex items-center justify-center text-gray-300">
                                                <i class="fas fa-gift"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $offer->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $offer->ingredients->map(fn($i) => $i->name . ' (' . rtrim(rtrim(number_format($i->pivot->quantity, 3), '0'), '.') . ' ' . $i->unit . ')')->implode(', ') ?: '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right font-semibold text-gray-900">Rs. {{ number_format($offer->price, 2) }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $offer->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $offer->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ route('offers.edit', $offer) }}" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </a>
                                            <form action="{{ route('offers.destroy', $offer) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this offer? It will no longer be available in POS.')">
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
                    {{ $offers->links('pagination::tailwind') }}
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-gift text-gray-300 text-5xl mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No offers yet</h3>
                    <p class="text-gray-600 mb-4">Start by adding your first offer</p>
                    <a href="{{ route('offers.create') }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-plus mr-2"></i>Add Offer
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
