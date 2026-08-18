@extends('layouts.app')

@section('title', 'Add Offer')

@section('content')
    <div>
        <div class="mb-8">
            <div>
                <a href="{{ route('offers.index') }}" class="flex items-center justify-center w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 hover:text-gray-900 transition-colors mb-2" title="Back">
                    <i class="fas fa-arrow-left text-sm"></i>
                </a>
                @section('breadcrumb')
                    <nav class="text-sm text-gray-600" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-2">
                            <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a></li>
                            <li class="text-gray-300">/</li>
                            <li><a href="{{ route('offers.index') }}" class="text-gray-500 hover:text-gray-700">Discounts & Offers</a></li>
                            <li class="text-gray-300">/</li>
                            <li class="text-gray-900 font-semibold">Add Offer</li>
                        </ol>
                    </nav>
                <h1 class="text-4xl font-bold text-gray-900">Add Offer</h1>
                @endsection
            </div>
            <p class="text-gray-600 mt-2">Create a new combo offer with fixed inclusions and customer choices</p>
        </div>

        <form action="{{ route('offers.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8" id="offerForm">
            @csrf

            <!-- 1. BASIC INFORMATION -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 md:p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="fas fa-tag text-blue-600"></i> Basic Information
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-900 mb-2">Offer Name <span class="text-red-600">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('name') ? 'border-red-600' : '' }}"
                            placeholder="e.g. Family Combo 01">
                        @error('name')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="price" class="block text-sm font-semibold text-gray-900 mb-2">Price <span class="text-red-600">*</span></label>
                        <div class="relative">
                            <span class="absolute left-4 top-2.5 text-gray-500 font-semibold">Rs.</span>
                            <input type="number" name="price" id="price" value="{{ old('price') }}" step="0.01" min="0" required
                                class="w-full pl-12 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('price') ? 'border-red-600' : '' }}"
                                placeholder="0.00">
                        </div>
                        @error('price')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6">
                    <label for="description" class="block text-sm font-semibold text-gray-900 mb-2">Description <span class="text-gray-400 font-normal">— optional</span></label>
                    <textarea name="description" id="description" rows="2"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Short description shown in POS">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 items-center">
                    <div>
                        <label for="image" class="block text-sm font-semibold text-gray-900 mb-2">Offer Image <span class="text-gray-400 font-normal">— optional</span></label>
                        <input type="file" name="image" id="image" accept=".jpg,.jpeg,.png,.webp,.gif"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('image') ? 'border-red-600' : '' }}"
                            onchange="previewImage(this)">
                        <p class="text-xs text-gray-500 mt-1">Accepted formats: .png / .jpg (max 2MB)</p>
                        @error('image')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <img id="image-preview" src="#" alt="Preview" class="mt-2 w-24 h-24 object-cover rounded-lg border border-gray-200 hidden">
                    </div>

                    <div class="pt-4">
                        <label class="inline-flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-semibold text-gray-900">Active in POS Billing</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- 2. FIXED INCLUDED ITEMS (MUST-INCLUDED THINGS) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 md:p-8">
                <div class="mb-4">
                    <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                        <i class="fas fa-boxes-stacked text-indigo-600"></i> Fixed Included Items
                        <span class="text-xs font-normal text-gray-500 bg-gray-100 px-2 py-0.5 rounded">Must-Included</span>
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">Select items (dishes, finished goods, and raw materials) that are automatically included in this offer every time it is sold.</p>
                </div>

                <!-- Tabs: Dishes / Finished Goods vs Raw Included Items -->
                <div class="flex border-b border-gray-200 mb-4 gap-4">
                    <button type="button" onclick="switchInclusionTab('products')" id="tabBtn-products"
                        class="py-2.5 px-4 font-semibold text-sm border-b-2 border-blue-600 text-blue-600 flex items-center gap-2">
                        <i class="fas fa-utensils"></i> Dishes & Products (Finished Goods)
                    </button>
                    <button type="button" onclick="switchInclusionTab('ingredients')" id="tabBtn-ingredients"
                        class="py-2.5 px-4 font-semibold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 flex items-center gap-2">
                        <i class="fas fa-flask"></i> Included Items (Raw Materials)
                    </button>
                </div>

                <!-- TAB 1: Menu Products (Dishes & Finished Goods) -->
                <div id="tabContent-products" class="space-y-3">
                    <input type="text" id="productSearch" onkeyup="filterTableRows('productList', this.value)" placeholder="Search dishes & products… e.g. Fried Rice, Fries"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">

                    <div class="border border-gray-200 rounded-lg overflow-hidden max-h-72 overflow-y-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200 sticky top-0">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 w-10"></th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600">Product Name</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600">Category</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-600">Price</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 w-44">Quantity per Offer</th>
                                </tr>
                            </thead>
                            <tbody id="productList" class="divide-y divide-gray-100">
                                @forelse($products as $product)
                                    <tr data-name="{{ strtolower($product->name . ' ' . optional($product->category)->name) }}">
                                        <td class="px-4 py-2">
                                            <input type="checkbox" name="product_ids[]" value="{{ $product->id }}"
                                                {{ collect(old('product_ids'))->contains($product->id) ? 'checked' : '' }}
                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        </td>
                                        <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $product->name }}</td>
                                        <td class="px-4 py-2 text-xs text-gray-500">{{ optional($product->category)->name ?? 'General' }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-600 text-right">Rs. {{ number_format($product->price, 2) }}</td>
                                        <td class="px-4 py-2">
                                            <div class="relative">
                                                <input type="number" name="product_quantities[{{ $product->id }}]" step="0.1" min="0.1"
                                                    value="{{ old('product_quantities.' . $product->id, 1) }}"
                                                    class="w-full pr-12 pl-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                                                <span class="absolute right-3 top-1.5 text-xs text-gray-400">pcs/qty</span>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">No products available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 2: Ingredients (Raw Included Items) -->
                <div id="tabContent-ingredients" class="space-y-3 hidden">
                    <input type="text" id="ingredientSearch" onkeyup="filterTableRows('ingredientList', this.value)" placeholder="Search included items… e.g. Chicken, Mayonnaise, Sauce"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">

                    <div class="border border-gray-200 rounded-lg overflow-hidden max-h-72 overflow-y-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200 sticky top-0">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 w-10"></th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600">Included Item Name</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 w-44">Quantity per Offer</th>
                                </tr>
                            </thead>
                            <tbody id="ingredientList" class="divide-y divide-gray-100">
                                @forelse($ingredients as $ingredient)
                                    <tr data-name="{{ strtolower($ingredient->name) }}">
                                        <td class="px-4 py-2">
                                            <input type="checkbox" name="ingredient_ids[]" value="{{ $ingredient->id }}"
                                                {{ collect(old('ingredient_ids'))->contains($ingredient->id) ? 'checked' : '' }}
                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        </td>
                                        <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $ingredient->name }}</td>
                                        <td class="px-4 py-2">
                                            <div class="relative">
                                                <input type="number" name="ingredient_quantities[{{ $ingredient->id }}]" step="0.001" min="0.001"
                                                    value="{{ old('ingredient_quantities.' . $ingredient->id, 1) }}"
                                                    class="w-full pr-14 pl-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                                                <span class="absolute right-3 top-1.5 text-xs text-gray-400">{{ $ingredient->unit }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">No raw included items available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 3. CUSTOMER CHOICE GROUPS (e.g. 2 Drinks + 1 Dessert) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 md:p-8">
                <div class="flex items-center justify-between flex-wrap gap-4 mb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-hand-pointer text-purple-600"></i> Customer Choice Groups
                            <span class="text-xs font-normal text-gray-500 bg-gray-100 px-2 py-0.5 rounded">Optional Choices</span>
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            Set up choices for the customer (e.g. <strong>2 Drinks</strong> of any flavour, <strong>1 Dessert</strong>). The cashier will pick the customer's choice when billing.
                        </p>
                    </div>
                    <button type="button" onclick="addChoiceGroup()" class="bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 px-4 py-2 rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
                        <i class="fas fa-plus"></i> Add Choice Group
                    </button>
                </div>

                <div id="choiceGroupsContainer" class="space-y-4">
                    <!-- Dynamic choice group cards inserted here -->
                </div>

                <div id="noChoiceGroupsMessage" class="border-2 border-dashed border-gray-200 rounded-xl p-8 text-center text-gray-500">
                    <i class="fas fa-layer-group text-3xl mb-2 text-gray-300"></i>
                    <p class="text-sm font-medium">No choice groups added yet.</p>
                    <p class="text-xs text-gray-400 mt-1">If this offer requires the customer to pick specific drink flavours, desserts, or sides, click "+ Add Choice Group" above.</p>
                </div>
            </div>

            <!-- FORM ACTIONS -->
            <div class="flex gap-4 pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold transition-colors shadow-sm text-base flex items-center gap-2">
                    <i class="fas fa-save"></i> Save Offer
                </button>
                <a href="{{ route('offers.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-8 py-3 rounded-lg font-semibold transition-colors text-base flex items-center gap-2">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
<script>
    // Categories and products data for dynamic choice groups
    const availableCategories = @json($categories);
    const availableProducts = @json($products);

    let groupCounter = 0;

    function previewImage(input) {
        const preview = document.getElementById('image-preview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.classList.add('hidden');
        }
    }

    function switchInclusionTab(tab) {
        document.getElementById('tabContent-products').classList.toggle('hidden', tab !== 'products');
        document.getElementById('tabContent-ingredients').classList.toggle('hidden', tab !== 'ingredients');

        const btnProd = document.getElementById('tabBtn-products');
        const btnIngr = document.getElementById('tabBtn-ingredients');

        if (tab === 'products') {
            btnProd.className = 'py-2.5 px-4 font-semibold text-sm border-b-2 border-blue-600 text-blue-600 flex items-center gap-2';
            btnIngr.className = 'py-2.5 px-4 font-semibold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 flex items-center gap-2';
        } else {
            btnIngr.className = 'py-2.5 px-4 font-semibold text-sm border-b-2 border-blue-600 text-blue-600 flex items-center gap-2';
            btnProd.className = 'py-2.5 px-4 font-semibold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 flex items-center gap-2';
        }
    }

    function filterTableRows(tbodyId, query) {
        const term = query.trim().toLowerCase();
        const rows = document.querySelectorAll('#' + tbodyId + ' tr[data-name]');
        rows.forEach(function (row) {
            row.style.display = row.dataset.name.includes(term) ? '' : 'none';
        });
    }

    function addChoiceGroup(initialData = null) {
        groupCounter++;
        const id = groupCounter;
        const container = document.getElementById('choiceGroupsContainer');
        document.getElementById('noChoiceGroupsMessage').classList.add('hidden');

        const groupName = initialData ? initialData.name : '';
        const categoryId = initialData ? initialData.category_id : '';
        const choiceQty = initialData ? initialData.choice_qty : 1;
        const selectedProductIds = initialData ? (initialData.product_ids || []) : [];

        let categoryOptions = '<option value="">-- Select Category (or select specific items) --</option>';
        availableCategories.forEach(cat => {
            const selected = (categoryId == cat.id) ? 'selected' : '';
            categoryOptions += `<option value="${cat.id}" ${selected}>Category: ${cat.name}</option>`;
        });

        const card = document.createElement('div');
        card.id = `choice-group-card-${id}`;
        card.className = 'border border-purple-200 rounded-xl p-5 bg-purple-50/40 relative space-y-4';

        card.innerHTML = `
            <div class="flex items-center justify-between pb-3 border-b border-purple-100 flex-wrap gap-2">
                <div class="flex items-center gap-2">
                    <span class="bg-purple-600 text-white text-xs font-bold px-2.5 py-1 rounded-full">Choice Group</span>
                    <input type="text" name="choice_groups[${id}][name]" value="${escapeHtml(groupName)}" placeholder="e.g. Drinks, Dessert, Side Dish" required
                        class="font-bold text-gray-900 border border-purple-200 rounded-lg px-3 py-1 text-sm bg-white focus:ring-2 focus:ring-purple-500">
                </div>
                <button type="button" onclick="removeChoiceGroup(${id})" class="text-red-500 hover:text-red-700 text-sm font-semibold flex items-center gap-1">
                    <i class="fas fa-trash-alt"></i> Remove Group
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Source Category <span class="text-gray-400 font-normal">(optional quick filter)</span></label>
                    <select name="choice_groups[${id}][category_id]" onchange="onGroupCategoryChange(${id}, this.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-purple-500">
                        ${categoryOptions}
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Number of picks per offer <span class="text-red-600">*</span></label>
                    <div class="flex items-center gap-2">
                        <input type="number" name="choice_groups[${id}][choice_qty]" value="${choiceQty}" min="1" step="1" required
                            class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white font-bold text-purple-700 focus:ring-2 focus:ring-purple-500">
                        <span class="text-xs text-gray-500">e.g. 2 for 2 drinks, 1 for dessert</span>
                    </div>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="text-xs font-semibold text-gray-700">Eligible Items for this Choice <span class="text-gray-400 font-normal">(all checked will be options)</span></label>
                    <div class="flex gap-2">
                        <button type="button" onclick="checkAllGroupProducts(${id}, true)" class="text-xs text-purple-600 hover:underline font-semibold">Select All</button>
                        <span class="text-xs text-gray-300">|</span>
                        <button type="button" onclick="checkAllGroupProducts(${id}, false)" class="text-xs text-purple-600 hover:underline font-semibold">Deselect All</button>
                    </div>
                </div>
                <div class="border border-gray-200 rounded-lg overflow-hidden max-h-48 overflow-y-auto bg-white">
                    <div class="divide-y divide-gray-100" id="group-products-${id}">
                        <!-- Product rows for this group -->
                    </div>
                </div>
            </div>
        `;

        container.appendChild(card);
        renderGroupProducts(id, categoryId, selectedProductIds);
    }

    function removeChoiceGroup(id) {
        const card = document.getElementById(`choice-group-card-${id}`);
        if (card) card.remove();
        const container = document.getElementById('choiceGroupsContainer');
        if (!container.children.length) {
            document.getElementById('noChoiceGroupsMessage').classList.remove('hidden');
        }
    }

    function renderGroupProducts(groupId, categoryId, selectedIds = []) {
        const container = document.getElementById(`group-products-${groupId}`);
        if (!container) return;

        // If category is selected, prioritize products in that category, or show all products
        let productsToShow = availableProducts;
        if (categoryId) {
            const filtered = availableProducts.filter(p => p.category_id == categoryId);
            if (filtered.length > 0) productsToShow = filtered;
        }

        container.innerHTML = productsToShow.map(p => {
            // Auto check if in selectedIds OR if no explicit selectedIds and category is selected
            const isChecked = selectedIds.includes(p.id) || (selectedIds.length === 0 && categoryId && p.category_id == categoryId);
            return `
                <label class="flex items-center justify-between px-4 py-2 hover:bg-purple-50/50 cursor-pointer text-sm">
                    <div class="flex items-center gap-2.5">
                        <input type="checkbox" name="choice_groups[${groupId}][product_ids][]" value="${p.id}" ${isChecked ? 'checked' : ''}
                            class="group-product-cb-${groupId} rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                        <span class="font-medium text-gray-800">${escapeHtml(p.name)}</span>
                    </div>
                    <span class="text-xs text-gray-500">Rs. ${parseFloat(p.price).toFixed(2)}</span>
                </label>
            `;
        }).join('');
    }

    function onGroupCategoryChange(groupId, categoryId) {
        // Auto fill group name if empty
        const nameInput = document.querySelector(`input[name="choice_groups[${groupId}][name]"]`);
        if (nameInput && (!nameInput.value || nameInput.value.startsWith('Choice Group'))) {
            const cat = availableCategories.find(c => c.id == categoryId);
            if (cat) nameInput.value = cat.name;
        }
        renderGroupProducts(groupId, categoryId, []);
    }

    function checkAllGroupProducts(groupId, check) {
        document.querySelectorAll(`.group-product-cb-${groupId}`).forEach(cb => cb.checked = check);
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }
</script>
@endsection
