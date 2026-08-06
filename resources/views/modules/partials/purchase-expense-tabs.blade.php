@php
    $activeTab = request()->routeIs('expenses.*') ? 'expenses' : 'purchases';
@endphp

<div class="flex items-center gap-2 border-b border-gray-200 mb-6">
    <a href="{{ route('purchases.index') }}"
        class="px-4 py-2.5 text-sm font-semibold border-b-2 -mb-px transition-colors
            {{ $activeTab === 'purchases' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
        <i class="fas fa-basket-shopping mr-2"></i>Purchases
    </a>
    <a href="{{ route('expenses.index') }}"
        class="px-4 py-2.5 text-sm font-semibold border-b-2 -mb-px transition-colors
            {{ $activeTab === 'expenses' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
        <i class="fas fa-receipt mr-2"></i>Expenses
    </a>
</div>
