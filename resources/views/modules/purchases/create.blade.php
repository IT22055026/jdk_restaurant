@extends('layouts.app')

@section('title', 'Add Purchase')

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
                            <li class="text-gray-900 font-semibold">Add Purchase</li>
                        </ol>
                    </nav>
                <h1 class="text-4xl font-bold text-gray-900 mt-2">Add Purchase</h1>
                @endsection
            </div>
            <p class="text-gray-600 mt-2">Pick a supplier, add as many of their Finished Goods and Included Items as you're buying, then save.</p>
        </div>

        @if($errors->any() && $errors->has('items'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center gap-2">
                <i class="fas fa-triangle-exclamation"></i>{{ $errors->first('items') }}
            </div>
        @endif

        <form action="{{ route('purchases.store') }}" method="POST" id="purchase-form" class="space-y-6">
            @csrf

            <!-- Step 1: Supplier -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-8 w-full">
                <h2 class="text-lg font-bold text-gray-900 mb-1">1. Supplier</h2>
                <p class="text-sm text-gray-500 mb-4">Choose who you're buying from. Their Finished Goods and Included Items will load below automatically.</p>

                <div class="max-w-md">
                    <x-combobox
                        name="supplier_id"
                        id="supplier_id"
                        label="Supplier"
                        required
                        placeholder="Search supplier…"
                        empty-option="-- Select a supplier --"
                        :options="$suppliers->map(fn($s) => ['value' => $s->id, 'label' => $s->name])" />
                </div>
            </div>

            <!-- Step 2: Item builder -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-8 w-full">
                <h2 class="text-lg font-bold text-gray-900 mb-1">2. Add Items</h2>
                <p class="text-sm text-gray-500 mb-4" id="item_builder_hint">Select a supplier above, then add each item you're purchasing from them one at a time.</p>

                <div class="flex items-center gap-2 border-b border-gray-200 mb-6" id="item_type_tabs">
                    <button type="button" data-type="finished_good" class="item-type-tab px-4 py-2.5 text-sm font-semibold border-b-2 -mb-px transition-colors border-blue-600 text-blue-600">
                        <i class="fas fa-box mr-2"></i>Finished Goods
                    </button>
                    <button type="button" data-type="included_item" class="item-type-tab px-4 py-2.5 text-sm font-semibold border-b-2 -mb-px transition-colors border-transparent text-gray-500 hover:text-gray-800">
                        <i class="fas fa-layer-group mr-2"></i>Included Items
                    </button>
                </div>

                <!-- Finished Good picker -->
                <div id="section_finished_good" class="item-type-section">
                    <x-combobox
                        name="builder_finished_good"
                        id="builder_finished_good"
                        label="Finished Good"
                        placeholder="Search finished goods…"
                        empty-option="-- Select a supplier first --"
                        :options="$finishedGoods->map(fn($p) => ['value' => $p->id, 'label' => $p->name, 'data' => ['parent' => $p->supplier_id, 'cost' => $p->cost_price, 'unlimited' => $p->is_unlimited_stock ? '1' : '0']])" />
                    <p class="text-xs text-gray-400 mt-2" data-empty-hint>No finished goods are linked to this supplier yet — add one under <a href="{{ route('products.index') }}" class="underline hover:text-gray-600">Products</a>.</p>
                </div>

                <!-- Included Item picker -->
                <div id="section_included_item" class="item-type-section hidden">
                    <x-combobox
                        name="builder_included_item"
                        id="builder_included_item"
                        label="Included Item"
                        placeholder="Search included items…"
                        empty-option="-- Select a supplier first --"
                        :options="$includedItems->map(fn($i) => ['value' => $i->id, 'label' => $i->name, 'data' => ['parent' => $i->supplier_id, 'cost' => $i->cost_per_unit, 'unit' => $i->unit]])" />
                    <p class="text-xs text-gray-400 mt-2" data-empty-hint>No included items are linked to this supplier yet — add one under <a href="{{ route('ingredients.index') }}" class="underline hover:text-gray-600">Included Items</a>.</p>
                </div>

                <!-- Common row fields -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-6">
                    <div>
                        <label for="builder_quantity" class="block text-sm font-semibold text-gray-900 mb-2">Quantity <span class="text-red-600">*</span></label>
                        <input type="number" step="0.001" min="0.001" id="builder_quantity" placeholder="0.000"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Unit</label>
                        <input type="text" id="builder_unit_display" readonly value="pcs"
                            class="w-full px-4 py-2 border border-gray-200 bg-gray-50 text-gray-600 rounded-lg">
                    </div>

                    <div>
                        <label for="builder_unit_price" class="block text-sm font-semibold text-gray-900 mb-2">Unit Price (LKR) <span class="text-gray-400 font-normal">(Optional)</span></label>
                        <input type="number" step="0.01" min="0" id="builder_unit_price" placeholder="0.00"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="builder_amount" class="block text-sm font-semibold text-gray-900 mb-2">Amount (LKR) <span class="text-red-600">*</span></label>
                        <input type="number" step="0.01" min="0.01" id="builder_amount" placeholder="0.00"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <p id="builder_unlimited_note" class="text-xs text-amber-600 mt-3 hidden">
                    <i class="fas fa-circle-info mr-1"></i>This item's stock isn't tracked (unlimited stock) — quantity is recorded for cost history only.
                </p>
                <p id="builder_error" class="text-sm text-red-600 mt-3 hidden"></p>

                <div class="mt-6">
                    <button type="button" id="add_item_btn" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-plus mr-2"></i>Add to Purchase
                    </button>
                </div>
            </div>

            <!-- Items table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-8 w-full">
                <h2 class="text-lg font-bold text-gray-900 mb-1">3. Items on This Purchase</h2>
                <p class="text-sm text-gray-500 mb-4">Everything added above shows up here. You can remove a line before saving.</p>

                <div class="overflow-x-auto border border-gray-100 rounded-lg">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Type</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Item</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold text-gray-900">Quantity</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold text-gray-900">Unit Price</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold text-gray-900">Amount</th>
                                <th class="px-4 py-3 text-center text-sm font-semibold text-gray-900">Remove</th>
                            </tr>
                        </thead>
                        <tbody id="items_table_body" class="divide-y divide-gray-200">
                            <tr id="items_empty_row">
                                <td colspan="6" class="px-4 py-8 text-center text-gray-400 text-sm">No items added yet — build one above and click "Add to Purchase".</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-50 border-t border-gray-200">
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-sm font-bold text-gray-900 text-right">Total</td>
                                <td class="px-4 py-3 text-sm font-bold text-amber-600 text-right" id="items_total">LKR 0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div id="hidden_items_container"></div>
            </div>

            <!-- Step 3: Purchase details -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-8 w-full">
                <h2 class="text-lg font-bold text-gray-900 mb-4">4. Purchase Details</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="purchase_date" class="block text-sm font-semibold text-gray-900 mb-2">
                            Purchase Date <span class="text-red-600">*</span>
                        </label>
                        <input type="date" name="purchase_date" id="purchase_date" value="{{ old('purchase_date', date('Y-m-d')) }}" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('purchase_date') ? 'border-red-600' : '' }}">
                        @error('purchase_date')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="payment_method" class="block text-sm font-semibold text-gray-900 mb-2">
                            Payment Method <span class="text-red-600">*</span>
                        </label>
                        <select name="payment_method" id="payment_method" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('payment_method') ? 'border-red-600' : '' }}">
                            @foreach($paymentMethods as $value => $label)
                                <option value="{{ $value }}" {{ old('payment_method', 'cash') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-2">Bank, Card and Split need nothing else — the method alone is recorded.</p>
                        @error('payment_method')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <div>
                        <label for="supplier_invoice_no" class="block text-sm font-semibold text-gray-900 mb-2">
                            Supplier Invoice / Bill No. <span class="text-gray-400 font-normal">(Optional)</span>
                        </label>
                        <input type="text" name="supplier_invoice_no" id="supplier_invoice_no" value="{{ old('supplier_invoice_no') }}"
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
                        placeholder="Enter any additional details about this purchase...">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex gap-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                    <i class="fas fa-save mr-2"></i>Save Purchase
                </button>
                <a href="{{ route('purchases.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-900 px-6 py-2 rounded-lg font-semibold transition-colors">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var supplierSelect = document.getElementById('supplier_id');
    var finishedGoodSelect = document.getElementById('builder_finished_good');
    var includedItemSelect = document.getElementById('builder_included_item');
    var items = [];

    // An empty supplier means "show nothing" — Finished Goods/Included Items are
    // meaningless without a supplier to scope them to.
    function filterBySupplier(select) {
        var supplierVal = supplierSelect.value;
        var visibleCount = 0;
        Array.from(select.options).forEach(function (opt) {
            if (opt.value === '') { opt.disabled = false; return; }
            var belongs = !!supplierVal && opt.getAttribute('data-parent') === supplierVal;
            opt.disabled = !belongs;
            if (belongs) visibleCount++;
        });
        select.value = '';
        if (select._combobox) select._combobox.refresh();

        var hint = select.closest('.item-type-section').querySelector('[data-empty-hint]');
        if (hint) hint.classList.toggle('hidden', !supplierVal || visibleCount > 0);
    }

    function refreshSupplierScoped() {
        filterBySupplier(finishedGoodSelect);
        filterBySupplier(includedItemSelect);

        var hint = document.getElementById('item_builder_hint');
        hint.textContent = supplierSelect.value
            ? 'Pick an item below, set the quantity/price, then click "Add to Purchase". Repeat for every item you\'re buying from this supplier.'
            : 'Select a supplier above, then add each item you\'re purchasing from them one at a time.';
    }

    supplierSelect.addEventListener('change', function () {
        if (items.length && !confirm('Changing the supplier may no longer match the items already added. Clear the item table?')) {
            // Keep the table but the user was warned; server-side validation will
            // still reject a mismatched supplier/item combination on submit.
        } else if (items.length) {
            items = [];
            renderTable();
        }
        refreshSupplierScoped();
    });
    refreshSupplierScoped();

    // ---- Item type tabs ----
    var activeType = 'finished_good';
    document.querySelectorAll('.item-type-tab').forEach(function (btn) {
        btn.addEventListener('click', function () {
            activeType = btn.dataset.type;
            document.querySelectorAll('.item-type-tab').forEach(function (b) {
                b.classList.toggle('border-blue-600', b === btn);
                b.classList.toggle('text-blue-600', b === btn);
                b.classList.toggle('border-transparent', b !== btn);
                b.classList.toggle('text-gray-500', b !== btn);
            });
            document.querySelectorAll('.item-type-section').forEach(function (s) {
                s.classList.toggle('hidden', s.id !== 'section_' + activeType);
            });
            document.getElementById('builder_unit_display').value = activeType === 'finished_good' ? 'pcs' : '';
            clearBuilderError();
            document.getElementById('builder_unlimited_note').classList.add('hidden');
        });
    });

    // ---- Auto-fill unit/price from the selected catalog item ----
    finishedGoodSelect.addEventListener('change', function () {
        var opt = finishedGoodSelect.options[finishedGoodSelect.selectedIndex];
        document.getElementById('builder_unit_display').value = 'pcs';
        if (opt && opt.value !== '') {
            var cost = opt.getAttribute('data-cost');
            if (cost) document.getElementById('builder_unit_price').value = parseFloat(cost).toFixed(2);
            document.getElementById('builder_unlimited_note').classList.toggle('hidden', opt.getAttribute('data-unlimited') !== '1');
        } else {
            document.getElementById('builder_unlimited_note').classList.add('hidden');
        }
        recalcAmount();
    });

    includedItemSelect.addEventListener('change', function () {
        var opt = includedItemSelect.options[includedItemSelect.selectedIndex];
        if (opt && opt.value !== '') {
            document.getElementById('builder_unit_display').value = opt.getAttribute('data-unit') || '';
            var cost = opt.getAttribute('data-cost');
            if (cost) document.getElementById('builder_unit_price').value = parseFloat(cost).toFixed(2);
        }
        recalcAmount();
    });

    // ---- Amount auto-calc (quantity × unit price), editable override ----
    var qtyInput = document.getElementById('builder_quantity');
    var priceInput = document.getElementById('builder_unit_price');
    var amountInput = document.getElementById('builder_amount');
    var amountTouched = false;
    amountInput.addEventListener('input', function () { amountTouched = true; });

    function recalcAmount() {
        var qty = parseFloat(qtyInput.value);
        var price = parseFloat(priceInput.value);
        if (!amountTouched && !isNaN(qty) && !isNaN(price)) {
            amountInput.value = (qty * price).toFixed(2);
        }
    }
    qtyInput.addEventListener('input', recalcAmount);
    priceInput.addEventListener('input', recalcAmount);

    function resetBuilderRow() {
        qtyInput.value = '';
        priceInput.value = '';
        amountInput.value = '';
        amountTouched = false;
        document.getElementById('builder_unlimited_note').classList.add('hidden');
        [finishedGoodSelect, includedItemSelect].forEach(function (sel) {
            sel.value = '';
            if (sel._combobox) sel._combobox.refresh();
        });
        document.getElementById('builder_unit_display').value = activeType === 'finished_good' ? 'pcs' : '';
    }

    function builderError(msg) {
        var el = document.getElementById('builder_error');
        el.textContent = msg;
        el.classList.remove('hidden');
    }
    function clearBuilderError() {
        document.getElementById('builder_error').classList.add('hidden');
    }

    // ---- Add to Purchase ----
    document.getElementById('add_item_btn').addEventListener('click', function () {
        clearBuilderError();

        if (!supplierSelect.value) {
            builderError('Select a supplier first.');
            return;
        }

        var quantity = parseFloat(qtyInput.value);
        var amount = parseFloat(amountInput.value);
        var unitPrice = priceInput.value !== '' ? parseFloat(priceInput.value) : null;

        if (isNaN(quantity) || quantity <= 0) {
            builderError('Enter a valid quantity.');
            return;
        }
        if (isNaN(amount) || amount <= 0) {
            builderError('Enter a valid amount.');
            return;
        }

        var row = {
            item_type: activeType,
            product_id: null,
            ingredient_id: null,
            label: '',
            unit: 'pcs',
            quantity: quantity,
            unit_price: unitPrice,
            amount: amount,
        };

        if (activeType === 'finished_good') {
            if (!finishedGoodSelect.value) { builderError('Choose a finished good.'); return; }
            if (Math.floor(quantity) !== quantity) { builderError('Finished goods are purchased in whole units.'); return; }
            var fgOpt = finishedGoodSelect.options[finishedGoodSelect.selectedIndex];
            row.product_id = finishedGoodSelect.value;
            row.label = fgOpt.textContent.trim();
            row.unit = 'pcs';
        } else {
            if (!includedItemSelect.value) { builderError('Choose an included item.'); return; }
            var iiOpt = includedItemSelect.options[includedItemSelect.selectedIndex];
            row.ingredient_id = includedItemSelect.value;
            row.label = iiOpt.textContent.trim();
            row.unit = iiOpt.getAttribute('data-unit') || '';
        }

        // Merge into an existing row for the same catalog item instead of duplicating it.
        var existing = items.find(function (i) {
            return i.item_type === row.item_type &&
                i.product_id === row.product_id && i.ingredient_id === row.ingredient_id;
        });
        if (existing) {
            existing.quantity = round3(existing.quantity + row.quantity);
            existing.amount = round2(existing.amount + row.amount);
            existing.unit_price = existing.quantity > 0 ? round2(existing.amount / existing.quantity) : existing.unit_price;
        } else {
            items.push(row);
        }

        renderTable();
        resetBuilderRow();
    });

    function round2(n) { return Math.round(n * 100) / 100; }
    function round3(n) { return Math.round(n * 1000) / 1000; }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str == null ? '' : str;
        return div.innerHTML;
    }

    var typeBadges = {
        finished_good: 'bg-blue-50 text-blue-700 border-blue-100',
        included_item: 'bg-emerald-50 text-emerald-700 border-emerald-100',
    };
    var typeLabels = { finished_good: 'Finished Good', included_item: 'Included Item' };

    function renderTable() {
        var body = document.getElementById('items_table_body');
        body.innerHTML = '';

        if (!items.length) {
            body.innerHTML = '<tr id="items_empty_row"><td colspan="6" class="px-4 py-8 text-center text-gray-400 text-sm">No items added yet — build one above and click "Add to Purchase".</td></tr>';
        } else {
            items.forEach(function (item, idx) {
                var tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50 transition-colors';
                tr.innerHTML =
                    '<td class="px-4 py-3 text-sm"><span class="px-3 py-1 rounded-full text-xs font-semibold border ' + typeBadges[item.item_type] + '">' + typeLabels[item.item_type] + '</span></td>' +
                    '<td class="px-4 py-3 text-sm font-medium text-gray-900">' + escapeHtml(item.label) + '</td>' +
                    '<td class="px-4 py-3 text-sm text-gray-700 text-right">' + trimNum(item.quantity) + ' ' + escapeHtml(item.unit) + '</td>' +
                    '<td class="px-4 py-3 text-sm text-gray-700 text-right">' + (item.unit_price != null ? item.unit_price.toFixed(2) : '—') + '</td>' +
                    '<td class="px-4 py-3 text-sm font-bold text-amber-600 text-right">' + item.amount.toFixed(2) + '</td>' +
                    '<td class="px-4 py-3 text-center"><button type="button" data-idx="' + idx + '" class="remove-item text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button></td>';
                body.appendChild(tr);
            });
        }

        body.querySelectorAll('.remove-item').forEach(function (btn) {
            btn.addEventListener('click', function () {
                items.splice(parseInt(btn.dataset.idx, 10), 1);
                renderTable();
            });
        });

        var total = items.reduce(function (sum, i) { return sum + i.amount; }, 0);
        document.getElementById('items_total').textContent = 'LKR ' + total.toFixed(2);
    }

    function trimNum(n) {
        return (Math.round(n * 1000) / 1000).toString();
    }

    // ---- Submit: serialize `items` into hidden inputs the server understands ----
    document.getElementById('purchase-form').addEventListener('submit', function (e) {
        if (!items.length) {
            e.preventDefault();
            builderError('Add at least one item to the purchase before saving.');
            window.scrollTo({ top: document.getElementById('item_type_tabs').getBoundingClientRect().top + window.scrollY - 100, behavior: 'smooth' });
            return;
        }

        var container = document.getElementById('hidden_items_container');
        container.innerHTML = '';
        items.forEach(function (item, idx) {
            ['item_type', 'product_id', 'ingredient_id', 'unit', 'quantity', 'unit_price', 'amount'].forEach(function (field) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'items[' + idx + '][' + field + ']';
                input.value = item[field] === null || item[field] === undefined ? '' : item[field];
                container.appendChild(input);
            });
        });
    });
});
</script>
@endsection
