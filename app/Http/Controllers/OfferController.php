<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OfferController extends Controller
{
    public function index()
    {
        $offers = Offer::with('ingredients')->orderBy('name')->paginate(10);
        $modules = $this->currentUser()->role->modules()->get();

        return view('modules.offers-list', [
            'offers' => $offers,
            'modules' => $modules,
        ]);
    }

    public function create()
    {
        $ingredients = Ingredient::orderBy('name')->get();
        $modules = $this->currentUser()->role->modules()->get();

        return view('modules.offers-create', [
            'ingredients' => $ingredients,
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
            'ingredient_ids' => 'nullable|array',
            'ingredient_ids.*' => 'exists:ingredients,id',
            'quantities' => 'nullable|array',
            'quantities.*' => 'nullable|numeric|min:0.001',
        ]);
    }

    /**
     * Build the sync payload {ingredient_id: [quantity => x]} from the checked
     * ingredient_ids plus their matching quantity input.
     */
    private function ingredientSyncData(array $validated): array
    {
        $sync = [];
        foreach ($validated['ingredient_ids'] ?? [] as $ingredientId) {
            $sync[$ingredientId] = ['quantity' => $validated['quantities'][$ingredientId] ?? 1];
        }
        return $sync;
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
        ]);

        $offer->ingredients()->sync($this->ingredientSyncData($validated));

        return redirect()->route('offers.index')->with('success', 'Offer created successfully');
    }

    public function edit(Offer $offer)
    {
        $offer->load('ingredients');
        $ingredients = Ingredient::orderBy('name')->get();
        $modules = $this->currentUser()->role->modules()->get();

        return view('modules.offers-edit', [
            'offer' => $offer,
            'ingredients' => $ingredients,
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
        ]);

        $offer->ingredients()->sync($this->ingredientSyncData($validated));

        return redirect()->route('offers.index')->with('success', 'Offer updated successfully');
    }

    public function destroy(Offer $offer)
    {
        $offer->delete();

        return redirect()->route('offers.index')->with('success', 'Offer deleted successfully');
    }
}
