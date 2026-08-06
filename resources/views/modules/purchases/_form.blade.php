@php
    // Figure out which main category to pre-select the sub-category combobox under.
    $selectedSubId = old('purchase_category_id', $purchase->purchase_category_id ?? null);
    $selectedMain = $selectedSubId ? $subcategories->firstWhere('id', (int) $selectedSubId) : null;
    $selectedMainId = $selectedMain->parent_id ?? null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <x-combobox
        name="main_category"
        id="main_category"
        label="Main Category"
        required
        placeholder="Search main category…"
        :selected="$selectedMainId"
        :options="$mainCategories->map(fn($c) => ['value' => $c->id, 'label' => $c->name])" />

    <x-combobox
        name="purchase_category_id"
        id="purchase_category_id"
        label="Sub-category"
        required
        placeholder="Search sub-category…"
        :selected="$selectedSubId"
        :options="$subcategories->map(fn($c) => ['value' => $c->id, 'label' => $c->name, 'data' => ['parent' => $c->parent_id]])" />
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
    <x-combobox
        name="supplier_id"
        id="supplier_id"
        label="Supplier"
        placeholder="Search supplier…"
        empty-option="-- No supplier / cash purchase --"
        :selected="old('supplier_id', $purchase->supplier_id ?? null)"
        :options="$suppliers->map(fn($s) => ['value' => $s->id, 'label' => $s->name])" />

    <div>
        <label for="item_name" class="block text-sm font-semibold text-gray-900 mb-2">
            Item / Description <span class="text-gray-400 font-normal">(Optional)</span>
        </label>
        <input type="text" name="item_name" id="item_name" value="{{ old('item_name', $purchase->item_name ?? '') }}"
            placeholder="e.g. Big Onion - Grade A"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('item_name') ? 'border-red-600' : '' }}">
        @error('item_name')
            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
    <div>
        <label for="quantity" class="block text-sm font-semibold text-gray-900 mb-2">
            Quantity <span class="text-red-600">*</span>
        </label>
        <input type="number" step="0.001" min="0.001" name="quantity" id="quantity" value="{{ old('quantity', $purchase->quantity ?? '') }}" required
            placeholder="0.000"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('quantity') ? 'border-red-600' : '' }}">
        @error('quantity')
            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="unit" class="block text-sm font-semibold text-gray-900 mb-2">
            Unit <span class="text-red-600">*</span>
        </label>
        <select name="unit" id="unit" required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('unit') ? 'border-red-600' : '' }}">
            @foreach($units as $value => $label)
                <option value="{{ $value }}" {{ old('unit', $purchase->unit ?? 'kg') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('unit')
            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="unit_price" class="block text-sm font-semibold text-gray-900 mb-2">
            Unit Price (LKR) <span class="text-gray-400 font-normal">(Optional)</span>
        </label>
        <input type="number" step="0.01" min="0" name="unit_price" id="unit_price" value="{{ old('unit_price', $purchase->unit_price ?? '') }}"
            placeholder="0.00"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('unit_price') ? 'border-red-600' : '' }}">
        @error('unit_price')
            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
    <div>
        <label for="amount" class="block text-sm font-semibold text-gray-900 mb-2">
            Total Amount (LKR) <span class="text-red-600">*</span>
        </label>
        <input type="number" step="0.01" min="0.01" name="amount" id="amount" value="{{ old('amount', $purchase->amount ?? '') }}" required
            placeholder="0.00"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('amount') ? 'border-red-600' : '' }}">
        <p class="text-xs text-gray-500 mt-2">Auto-filled from quantity × unit price — feel free to adjust it.</p>
        @error('amount')
            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="purchase_date" class="block text-sm font-semibold text-gray-900 mb-2">
            Purchase Date <span class="text-red-600">*</span>
        </label>
        <input type="date" name="purchase_date" id="purchase_date" value="{{ old('purchase_date', isset($purchase) ? $purchase->purchase_date->format('Y-m-d') : date('Y-m-d')) }}" required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('purchase_date') ? 'border-red-600' : '' }}">
        @error('purchase_date')
            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6">
    <label for="notes" class="block text-sm font-semibold text-gray-900 mb-2">Additional Notes <span class="text-gray-400 font-normal">(Optional)</span></label>
    <textarea name="notes" id="notes" rows="3"
        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        placeholder="Enter any additional details about this purchase...">{{ old('notes', $purchase->notes ?? '') }}</textarea>
    @error('notes')
        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
    @enderror
</div>

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        Combobox.setupCascade('purchase_category_id', 'main_category', 'data-parent');

        var quantityInput = document.getElementById('quantity');
        var unitPriceInput = document.getElementById('unit_price');
        var amountInput = document.getElementById('amount');
        var amountTouched = amountInput.value !== '';

        amountInput.addEventListener('input', function () { amountTouched = true; });

        function recalcAmount() {
            var qty = parseFloat(quantityInput.value);
            var price = parseFloat(unitPriceInput.value);
            if (!amountTouched && !isNaN(qty) && !isNaN(price)) {
                amountInput.value = (qty * price).toFixed(2);
            }
        }
        quantityInput.addEventListener('input', recalcAmount);
        unitPriceInput.addEventListener('input', recalcAmount);
    });
</script>
@endsection
