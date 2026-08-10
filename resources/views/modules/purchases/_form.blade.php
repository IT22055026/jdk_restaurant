@php
    $itemType = $purchase->itemType();
    $lockedLabel = match ($itemType) {
        \App\Models\Purchase::TYPE_FINISHED_GOOD => 'Finished Good',
        \App\Models\Purchase::TYPE_INCLUDED_ITEM => 'Included Item',
        default => 'Other (legacy entry)',
    };
    $lockedIcon = match ($itemType) {
        \App\Models\Purchase::TYPE_FINISHED_GOOD => 'box',
        \App\Models\Purchase::TYPE_INCLUDED_ITEM => 'layer-group',
        default => 'clock-rotate-left',
    };
@endphp

<div class="mb-6 bg-blue-50 border border-blue-100 rounded-lg px-4 py-3 flex items-center gap-3">
    <i class="fas fa-{{ $lockedIcon }} text-blue-600"></i>
    <div>
        <p class="text-sm font-semibold text-gray-900">
            {{ $lockedLabel }}: {{ $purchase->item_name ?? '—' }}
            @if($itemType === \App\Models\Purchase::TYPE_OTHER && $purchase->category)
                <span class="text-gray-400 font-normal">({{ $purchase->category->parent?->name ? $purchase->category->parent->name . ' › ' : '' }}{{ $purchase->category->name }})</span>
            @endif
        </p>
        <p class="text-xs text-gray-500 mt-0.5">The item a purchase line is linked to can't be changed here — delete this line and add a new one instead if it's wrong.</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <x-combobox
        name="supplier_id"
        id="supplier_id"
        label="Supplier"
        placeholder="Search supplier…"
        empty-option="-- No supplier / cash purchase --"
        :selected="old('supplier_id', $purchase->supplier_id)"
        :options="$suppliers->map(fn($s) => ['value' => $s->id, 'label' => $s->name])" />

    <div>
        <label for="payment_method" class="block text-sm font-semibold text-gray-900 mb-2">
            Payment Method <span class="text-red-600">*</span>
        </label>
        <select name="payment_method" id="payment_method" required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('payment_method') ? 'border-red-600' : '' }}">
            @foreach($paymentMethods as $value => $label)
                <option value="{{ $value }}" {{ old('payment_method', $purchase->payment_method) === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('payment_method')
            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
    <div>
        <label for="quantity" class="block text-sm font-semibold text-gray-900 mb-2">
            Quantity <span class="text-red-600">*</span>
        </label>
        <input type="number" step="{{ $itemType === 'finished_good' ? '1' : '0.001' }}" min="{{ $itemType === 'finished_good' ? '1' : '0.001' }}" name="quantity" id="quantity" value="{{ old('quantity', $purchase->quantity) }}" required
            placeholder="0.000"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('quantity') ? 'border-red-600' : '' }}">
        @error('quantity')
            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-900 mb-2">Unit</label>
        <input type="text" readonly value="{{ $purchase->unitLabel() }}" class="w-full px-4 py-2 border border-gray-200 bg-gray-50 text-gray-600 rounded-lg">
    </div>

    <div>
        <label for="unit_price" class="block text-sm font-semibold text-gray-900 mb-2">
            Unit Price (LKR) <span class="text-gray-400 font-normal">(Optional)</span>
        </label>
        <input type="number" step="0.01" min="0" name="unit_price" id="unit_price" value="{{ old('unit_price', $purchase->unit_price) }}"
            placeholder="0.00"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('unit_price') ? 'border-red-600' : '' }}">
        @error('unit_price')
            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
    <div>
        <label for="amount" class="block text-sm font-semibold text-gray-900 mb-2">
            Total Amount (LKR) <span class="text-red-600">*</span>
        </label>
        <input type="number" step="0.01" min="0.01" name="amount" id="amount" value="{{ old('amount', $purchase->amount) }}" required
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
        <input type="date" name="purchase_date" id="purchase_date" value="{{ old('purchase_date', $purchase->purchase_date->format('Y-m-d')) }}" required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('purchase_date') ? 'border-red-600' : '' }}">
        @error('purchase_date')
            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="supplier_invoice_no" class="block text-sm font-semibold text-gray-900 mb-2">
            Supplier Invoice / Bill No. <span class="text-gray-400 font-normal">(Optional)</span>
        </label>
        <input type="text" name="supplier_invoice_no" id="supplier_invoice_no" value="{{ old('supplier_invoice_no', $purchase->supplier_invoice_no) }}"
            placeholder="e.g. INV-00231"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('supplier_invoice_no') ? 'border-red-600' : '' }}">
        @error('supplier_invoice_no')
            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6">
    <label for="notes" class="block text-sm font-semibold text-gray-900 mb-2">Additional Notes <span class="text-gray-400 font-normal">(Optional)</span></label>
    <textarea name="notes" id="notes" rows="3"
        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        placeholder="Enter any additional details about this purchase...">{{ old('notes', $purchase->notes) }}</textarea>
    @error('notes')
        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
    @enderror
</div>

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
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
