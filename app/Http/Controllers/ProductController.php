<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'supplierRecord']);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('name', 'like', '%' . $search . '%');
        }

        $products = $query->paginate(10);
        $modules = $this->currentUser()->role->modules()->get();
        $lowStockCount = Product::where('is_unlimited_stock', false)
            ->whereNotNull('low_stock_threshold')
            ->whereRaw('quantity <= low_stock_threshold')
            ->count();
        return view('modules.products-list', [
            'products' => $products,
            'modules' => $modules,
            'lowStockCount' => $lowStockCount,
            'searchQuery' => $request->search ?? '',
        ]);
    }

    public function dashboard()
    {
        $modules = $this->currentUser()->role->modules()->get();
        $totalProducts = Product::count();
        $activeProducts = Product::where('status', 'active')->count();
        $outOfStock = Product::where('is_unlimited_stock', false)->where('quantity', 0)->count();
        $lowStockProducts = Product::where('is_unlimited_stock', false)
            ->whereNotNull('low_stock_threshold')
            ->whereRaw('quantity <= low_stock_threshold')
            ->where('quantity', '>', 0)
            ->with('category')
            ->orderBy('quantity')
            ->get();
        $recentProducts = Product::with('category')->latest()->take(8)->get();
        return view('modules.inventory', compact(
            'modules', 'totalProducts', 'activeProducts', 'outOfStock', 'lowStockProducts', 'recentProducts'
        ));
    }

    public function create()
    {
        $modules = $this->currentUser()->role->modules()->get();
        $categories = Category::where('status', 'active')->get();
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();
        return view('modules.products-create', [
            'modules' => $modules,
            'categories' => $categories,
            'suppliers' => $suppliers,
        ]);
    }

    public function store(Request $request)
    {
        $isUnlimitedStock = $request->boolean('is_unlimited_stock');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'product_code' => 'nullable|string|unique:products',
            'description' => 'nullable|string|max:1000',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'quantity' => $isUnlimitedStock ? 'nullable|integer|min:0' : 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'is_unlimited_stock' => 'nullable|boolean',
            'barcode' => 'nullable|string|unique:products',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            'supplier' => 'nullable|string|max:255',
            'discount' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['is_unlimited_stock'] = $isUnlimitedStock;
        if ($validated['is_unlimited_stock']) {
            $validated['quantity'] = 0;
        }

        $validated['price'] = $validated['selling_price'] ?? $validated['cost_price'] ?? 0;

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        Product::create($validated);
        return redirect()->route('inventory.index')->with('success', 'Product created successfully');
    }

    public function show(Product $product)
    {
        $modules = $this->currentUser()->role->modules()->get();
        return view('modules.products-show', [
            'product' => $product,
            'modules' => $modules,
        ]);
    }

    public function edit(Product $product)
    {
        $modules = $this->currentUser()->role->modules()->get();
        $categories = Category::where('status', 'active')->get();
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();
        return view('modules.products-edit', [
            'product' => $product,
            'modules' => $modules,
            'categories' => $categories,
            'suppliers' => $suppliers,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $isUnlimitedStock = $request->boolean('is_unlimited_stock');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'product_code' => 'nullable|string|unique:products,product_code,' . $product->id,
            'description' => 'nullable|string|max:1000',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'quantity' => $isUnlimitedStock ? 'nullable|integer|min:0' : 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'is_unlimited_stock' => 'nullable|boolean',
            'barcode' => 'nullable|string|unique:products,barcode,' . $product->id,
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            'supplier' => 'nullable|string|max:255',
            'discount' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['is_unlimited_stock'] = $isUnlimitedStock;
        if ($validated['is_unlimited_stock']) {
            $validated['quantity'] = 0;
        }

        if (! is_null($validated['selling_price'] ?? null)) {
            $validated['price'] = $validated['selling_price'];
        } elseif (! is_null($validated['cost_price'] ?? null)) {
            $validated['price'] = $validated['cost_price'];
        } else {
            $validated['price'] = $product->price;
        }

        if ($request->hasFile('image')) {
            if ($product->image && ! Str::startsWith($product->image, ['http://', 'https://'])) {
                Storage::disk('public')->delete($product->image);
            }

            $validated['image'] = $request->file('image')->store('products', 'public');
        } else {
            unset($validated['image']);
        }

        $product->update($validated);
        return redirect()->route('inventory.index')->with('success', 'Product updated successfully');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('inventory.index')->with('success', 'Product deleted successfully');
    }
}
