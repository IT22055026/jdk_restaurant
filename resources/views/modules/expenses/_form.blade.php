@php
    $currentTopId = old('expense_top_category', $selectedTopId);
    $currentSubId = old('expense_category_id', $selectedSubId);
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <x-combobox
        name="expense_top_category"
        id="expense_top_category"
        label="Category"
        required
        placeholder="Search category…"
        :selected="$currentTopId"
        :options="$mainCategories->map(fn($c) => ['value' => $c->id, 'label' => $c->name, 'data' => ['has-children' => $c->children->count() > 0 ? 1 : 0]])" />

    <div id="expense_sub_wrapper" class="hidden">
        <x-combobox
            name="expense_sub_category"
            id="expense_sub_category"
            label="Sub-category"
            placeholder="Search sub-category…"
            :selected="$currentSubId"
            :options="$subcategories->map(fn($c) => ['value' => $c->id, 'label' => $c->name, 'data' => ['parent' => $c->parent_id]])" />
    </div>
</div>

<input type="hidden" name="expense_category_id" id="expense_category_id_final" value="{{ $currentSubId ?: $currentTopId }}">
@error('expense_category_id')
    <p class="text-red-600 text-sm -mt-4 mb-6">{{ $message }}</p>
@enderror

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
    <div>
        <label for="expense_date" class="block text-sm font-semibold text-gray-900 mb-2">
            Expense Date <span class="text-red-600">*</span>
        </label>
        <input type="date" name="expense_date" id="expense_date" value="{{ old('expense_date', isset($expense) ? $expense->expense_date->format('Y-m-d') : date('Y-m-d')) }}" required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('expense_date') ? 'border-red-600' : '' }}">
        @error('expense_date')
            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="amount" class="block text-sm font-semibold text-gray-900 mb-2">
            Amount (LKR) <span class="text-red-600">*</span>
        </label>
        <input type="number" step="0.01" name="amount" id="amount" value="{{ old('amount', $expense->amount ?? '') }}" required
            placeholder="0.00"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('amount') ? 'border-red-600' : '' }}">
        @error('amount')
            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6">
    <label for="title" class="block text-sm font-semibold text-gray-900 mb-2">
        Expense Title / Description <span class="text-red-600">*</span>
    </label>
    <input type="text" name="title" id="title" value="{{ old('title', $expense->title ?? '') }}" required
        placeholder="e.g. July Electricity Bill, Kitchen Equipment Repair, Advance for Kamal"
        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('title') ? 'border-red-600' : '' }}">
    @error('title')
        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
    @enderror
</div>

<div class="mt-6">
    <label for="notes" class="block text-sm font-semibold text-gray-900 mb-2">Additional Notes <span class="text-gray-400 font-normal">(Optional)</span></label>
    <textarea name="notes" id="notes" rows="3"
        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        placeholder="Enter any additional details about this expense...">{{ old('notes', $expense->notes ?? '') }}</textarea>
    @error('notes')
        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
    @enderror
</div>

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var topSelect = document.getElementById('expense_top_category');
        var subWrapper = document.getElementById('expense_sub_wrapper');
        var subSelect = document.getElementById('expense_sub_category');
        var finalInput = document.getElementById('expense_category_id_final');

        Combobox.setupCascade('expense_sub_category', 'expense_top_category', 'data-parent');

        function sync() {
            var opt = topSelect.options[topSelect.selectedIndex];
            var hasChildren = opt && opt.dataset.hasChildren === '1';

            if (topSelect.value && hasChildren) {
                subWrapper.classList.remove('hidden');
                finalInput.value = subSelect.value;
            } else if (topSelect.value) {
                subWrapper.classList.add('hidden');
                finalInput.value = topSelect.value;
            } else {
                subWrapper.classList.add('hidden');
                finalInput.value = '';
            }
        }

        topSelect.addEventListener('change', sync);
        subSelect.addEventListener('change', function () { finalInput.value = subSelect.value; });
        sync();
    });
</script>
@endsection
