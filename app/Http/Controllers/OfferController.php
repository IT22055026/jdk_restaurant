<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Offer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OfferController extends Controller
{
    public function index()
    {
        $offers = Offer::with(['ingredients', 'products', 'choiceGroups.category', 'choiceGroups.products'])
            ->orderBy('name')
            ->paginate(10);
        $modules = $this->currentUser()->role->modules()->get();

        return view('modules.offers-list', [
            'offers' => $offers,
            'modules' => $modules,
        ]);
    }

    public function create()
    {
        $ingredients = Ingredient::where('status', 'active')->orderBy('name')->get();
        $products = Product::where('status', 'active')->with('category')->orderBy('name')->get();
        $categories = Category::where('status', 'active')->orderBy('sort_order')->orderBy('name')->get();
        $modules = $this->currentUser()->role->modules()->get();

        return view('modules.offers-create', [
            'ingredients' => $ingredients,
            'products' => $products,
            'categories' => $categories,
            'modules' => $modules,
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            'price' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',

            // Fixed Raw / Included Items (Ingredients)
            'ingredient_ids' => 'nullable|array',
            'ingredient_ids.*' => 'exists:ingredients,id',
            'quantities' => 'nullable|array',
            'quantities.*' => 'nullable|numeric|min:0.001',
            'ingredient_quantities' => 'nullable|array',
            'ingredient_quantities.*' => 'nullable|numeric|min:0.001',

            // Fixed Finished Goods / Menu Items (Products)
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
            'product_quantities' => 'nullable|array',
            'product_quantities.*' => 'nullable|numeric|min:0.001',

            // Customer Choice Groups (e.g. 2 Drinks + 1 Dessert)
            'choice_groups' => 'nullable|array',
            'choice_groups.*.name' => 'nullable|string|max:255',
            'choice_groups.*.category_id' => 'nullable|exists:categories,id',
            'choice_groups.*.choice_qty' => 'nullable|integer|min:1',
            'choice_groups.*.product_ids' => 'nullable|array',
            'choice_groups.*.product_ids.*' => 'exists:products,id',

            // Legacy simple flavour choice (backwards compatibility)
            'flavour_ids' => 'nullable|array',
            'flavour_ids.*' => 'exists:products,id',
            'flavour_qty' => 'nullable|integer|min:1',
        ]);
    }

    /**
     * Sync fixed ingredients, fixed products, and choice groups for an offer.
     */
    private function syncOfferRelations(Offer $offer, array $validated, Request $request): void
    {
        // 1. Sync fixed ingredients (raw items e.g. Chicken, Mayonnaise)
        $ingredientSync = [];
        $ingredientQuantities = $request->input('ingredient_quantities', $request->input('quantities', []));
        foreach ($validated['ingredient_ids'] ?? [] as $ingredientId) {
            $qty = $ingredientQuantities[$ingredientId] ?? 1;
            $ingredientSync[$ingredientId] = ['quantity' => max(0.001, (float) $qty)];
        }
        $offer->ingredients()->sync($ingredientSync);

        // 2. Sync fixed products (finished goods / menu dishes e.g. Fried Rice, Fries)
        $productSync = [];
        $productQuantities = $request->input('product_quantities', []);
        foreach ($validated['product_ids'] ?? [] as $productId) {
            $qty = $productQuantities[$productId] ?? 1;
            $productSync[$productId] = ['quantity' => max(0.001, (float) $qty)];
        }
        $offer->products()->sync($productSync);

        // 3. Sync Choice Groups
        // Remove existing choice groups and recreate from form data
        $offer->choiceGroups()->delete();

        if (!empty($validated['choice_groups'])) {
            foreach ($validated['choice_groups'] as $index => $groupData) {
                // Determine group title
                $groupName = trim($groupData['name'] ?? '');
                if (empty($groupName)) {
                    if (!empty($groupData['category_id'])) {
                        $cat = Category::find($groupData['category_id']);
                        $groupName = $cat ? $cat->name : 'Choice Group ' . ($index + 1);
                    } else {
                        $groupName = 'Choice Group ' . ($index + 1);
                    }
                }

                $choiceGroup = $offer->choiceGroups()->create([
                    'name' => $groupName,
                    'category_id' => !empty($groupData['category_id']) ? $groupData['category_id'] : null,
                    'choice_qty' => max(1, (int) ($groupData['choice_qty'] ?? 1)),
                    'sort_order' => $index,
                ]);

                if (!empty($groupData['product_ids'])) {
                    $choiceGroup->products()->sync($groupData['product_ids']);
                }
            }
        } elseif (!empty($validated['flavour_ids'])) {
            // Backwards compatibility for single flavour list
            $choiceGroup = $offer->choiceGroups()->create([
                'name' => 'Flavours',
                'choice_qty' => max(1, (int) ($validated['flavour_qty'] ?? 1)),
                'sort_order' => 0,
            ]);
            $choiceGroup->products()->sync($validated['flavour_ids']);
            $offer->flavours()->sync($validated['flavour_ids']);
        } else {
            $offer->flavours()->sync([]);
        }
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        $offer = Offer::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'image' => $request->hasFile('image') ? $request->file('image')->store('offers', 'public') : null,
            'price' => $validated['price'],
            'is_active' => $request->boolean('is_active'),
            'flavour_qty' => $validated['flavour_qty'] ?? 1,
        ]);

        $this->syncOfferRelations($offer, $validated, $request);

        return redirect()->route('offers.index')->with('success', 'Offer created successfully');
    }

    public function edit(Offer $offer)
    {
        $offer->load(['ingredients', 'products', 'choiceGroups.products', 'choiceGroups.category', 'flavours']);
        $ingredients = Ingredient::where('status', 'active')->orderBy('name')->get();
        $products = Product::where('status', 'active')->with('category')->orderBy('name')->get();
        $categories = Category::where('status', 'active')->orderBy('sort_order')->orderBy('name')->get();
        $modules = $this->currentUser()->role->modules()->get();

        return view('modules.offers-edit', [
            'offer' => $offer,
            'ingredients' => $ingredients,
            'products' => $products,
            'categories' => $categories,
            'modules' => $modules,
        ]);
    }

    public function update(Request $request, Offer $offer)
    {
        $validated = $this->validated($request);

        $image = $offer->image;
        if ($request->hasFile('image')) {
            if ($offer->image && !Str::startsWith($offer->image, ['http://', 'https://'])) {
                Storage::disk('public')->delete($offer->image);
            }
            $image = $request->file('image')->store('offers', 'public');
        }

        $offer->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'image' => $image,
            'price' => $validated['price'],
            'is_active' => $request->boolean('is_active'),
            'flavour_qty' => $validated['flavour_qty'] ?? 1,
        ]);

        $this->syncOfferRelations($offer, $validated, $request);

        return redirect()->route('offers.index')->with('success', 'Offer updated successfully');
    }

    public function destroy(Offer $offer)
    {
        $offer->delete();

        return redirect()->route('offers.index')->with('success', 'Offer deleted successfully');
    }
}
