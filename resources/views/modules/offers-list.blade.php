@extends('layouts.app')

@section('title', 'Discounts & Offers')

@section('content')
    <div>
        <div class="mb-6 flex items-center justify-between flex-wrap gap-4">
            <div>
                <nav class="text-xs text-gray-500 mb-1" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1.5">
                        <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a></li>
                        <li class="text-gray-300">/</li>
                        <li class="text-gray-900 font-semibold">Discounts & Offers</li>
                    </ol>
                </nav>
                <h1 class="text-2xl font-bold text-gray-900">Discounts & Offers</h1>
                <p class="text-gray-500 text-sm mt-1">Manage promotional discounts and special offers</p>
            </div>
            <a href="{{ route('offers.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold text-sm transition-colors shadow-xs">
                <i class="fas fa-plus mr-1.5"></i>Add Offer
            </a>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-xs border border-gray-100 overflow-hidden">
            @if($offers->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Image</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Includes</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Price</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($offers as $offer)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3.5">
                                        @if($offer->image)
                                            <div class="h-10 w-10 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0">
                                                <img src="{{ Str::startsWith($offer->image, ['http://', 'https://']) ? $offer->image : asset('storage/' . $offer->image) }}"
                                                    alt="{{ $offer->name }}" class="h-full w-full object-cover">
                                            </div>
                                        @else
                                            <div class="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center text-gray-300">
                                                <i class="fas fa-gift"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 font-medium text-gray-900">{{ $offer->name }}</td>
                                    <td class="px-4 py-3.5 text-gray-600">
                                        @php
                                            $inclusions = [];
                                            foreach ($offer->ingredients as $i) {
                                                $inclusions[] = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 mr-1 mb-1">' . e($i->name) . ' (' . rtrim(rtrim(number_format($i->pivot->quantity, 3), '0'), '.') . ' ' . e($i->unit) . ')</span>';
                                            }
                                            foreach ($offer->products as $p) {
                                                $inclusions[] = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-50 text-indigo-700 mr-1 mb-1">' . e($p->name) . ' (x' . rtrim(rtrim(number_format($p->pivot->quantity, 2), '0'), '.') . ')</span>';
                                            }
                                            foreach ($offer->choiceGroups as $cg) {
                                                $inclusions[] = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-50 text-purple-700 mr-1 mb-1"><i class="fas fa-hand-pointer mr-1"></i>Pick ' . $cg->choice_qty . ' ' . e($cg->name) . '</span>';
                                            }
                                            if (empty($inclusions) && $offer->flavours->isNotEmpty()) {
                                                $inclusions[] = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-50 text-purple-700 mr-1 mb-1"><i class="fas fa-hand-pointer mr-1"></i>Pick ' . ($offer->flavour_qty ?? 1) . ' Flavour(s)</span>';
                                            }
                                        @endphp
                                        {!! !empty($inclusions) ? implode('', $inclusions) : '—' !!}
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-semibold text-gray-900">Rs. {{ number_format($offer->price, 2) }}</td>
                                    <td class="px-4 py-3.5 text-center">
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $offer->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $offer->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ route('offers.edit', $offer) }}" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </a>
                                            <form action="{{ route('offers.destroy', $offer) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this offer? It will no longer be available in POS.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-semibold">
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
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No offers created yet</h3>
                    <p class="text-gray-600 mb-4">Create your first bundle or combo offer</p>
                    <a href="{{ route('offers.create') }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold text-sm transition-colors">
                        <i class="fas fa-plus mr-1.5"></i>Add Offer
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
