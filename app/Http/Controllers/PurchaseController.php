<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseCategory;
use App\Models\Supplier;
use App\Support\BusinessDay;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Purchase::with(['category.parent', 'supplier']);

        if ($request->filled('category_id')) {
            $category = PurchaseCategory::find($request->input('category_id'));
            if ($category) {
                $categoryIds = $category->isMain()
                    ? $category->children()->pluck('id')->push($category->id)
                    : collect([$category->id]);
                $query->whereIn('purchase_category_id', $categoryIds);
            }
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('purchase_date', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('purchase_date', '<=', $request->input('to'));
        }

        $purchases = (clone $query)->latest('purchase_date')->latest('id')->paginate(15)->withQueryString();
        $totalPurchases = (clone $query)->sum('amount');

        [$monthStart, $monthEnd] = BusinessDay::monthBoundsFor(BusinessDay::today());
        $thisMonthPurchases = Purchase::whereBetween('purchase_date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])->sum('amount');
        $todayPurchases = Purchase::whereDate('purchase_date', BusinessDay::today()->format('Y-m-d'))->sum('amount');

        // Supplier totals for a chosen month — defaults to the current month.
        $summaryMonth = $request->input('summary_month', BusinessDay::today()->format('Y-m'));
        try {
            $summaryDate = Carbon::createFromFormat('Y-m-d', $summaryMonth . '-01');
        } catch (\Exception) {
            $summaryDate = BusinessDay::today();
            $summaryMonth = $summaryDate->format('Y-m');
        }
        [$summaryStart, $summaryEnd] = BusinessDay::monthBoundsFor($summaryDate);

        $supplierTotals = Purchase::selectRaw('supplier_id, SUM(amount) as total, COUNT(*) as purchase_count')
            ->whereNotNull('supplier_id')
            ->whereBetween('purchase_date', [$summaryStart->format('Y-m-d'), $summaryEnd->format('Y-m-d')])
            ->groupBy('supplier_id')
            ->with('supplier')
            ->orderByDesc('total')
            ->get();

        $mainCategories = PurchaseCategory::main()->active()
            ->with(['children' => fn ($q) => $q->active()])
            ->orderBy('sort_order')->orderBy('name')->get();
        $subcategories = PurchaseCategory::sub()->active()->orderBy('sort_order')->orderBy('name')->get();
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();
        $modules = $this->currentUser()->role->modules()->get();

        return view('modules.purchases.index', compact(
            'purchases',
            'mainCategories',
            'subcategories',
            'suppliers',
            'modules',
            'totalPurchases',
            'thisMonthPurchases',
            'todayPurchases',
            'supplierTotals',
            'summaryMonth'
        ));
    }

    public function create()
    {
        [$mainCategories, $subcategories, $suppliers] = $this->formOptions();
        $modules = $this->currentUser()->role->modules()->get();
        $units = Purchase::UNITS;

        return view('modules.purchases.create', compact('mainCategories', 'subcategories', 'suppliers', 'modules', 'units'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePurchase($request);

        $validated['user_id'] = auth()->id();

        Purchase::create($validated);

        return redirect()->route('purchases.index')->with('success', 'Purchase recorded successfully.');
    }

    public function edit(Purchase $purchase)
    {
        [$mainCategories, $subcategories, $suppliers] = $this->formOptions();
        $modules = $this->currentUser()->role->modules()->get();
        $units = Purchase::UNITS;

        return view('modules.purchases.edit', compact('purchase', 'mainCategories', 'subcategories', 'suppliers', 'modules', 'units'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $validated = $this->validatePurchase($request);

        $purchase->update($validated);

        return redirect()->route('purchases.index')->with('success', 'Purchase updated successfully.');
    }

    public function destroy(Purchase $purchase)
    {
        $purchase->delete();

        return redirect()->route('purchases.index')->with('success', 'Purchase deleted successfully.');
    }

    private function validatePurchase(Request $request): array
    {
        $validated = $request->validate([
            'purchase_category_id' => 'required|exists:purchase_categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'item_name' => 'nullable|string|max:255',
            'quantity' => 'required|numeric|min:0.001',
            'unit' => 'required|in:' . implode(',', array_keys(Purchase::UNITS)),
            'unit_price' => 'nullable|numeric|min:0',
            'amount' => 'required|numeric|min:0.01',
            'purchase_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $category = PurchaseCategory::find($validated['purchase_category_id']);
        if ($category && $category->isMain() && $category->children()->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'purchase_category_id' => 'Please choose a specific sub-category, not a main category.',
            ]);
        }

        return $validated;
    }

    /** @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection, 2: \Illuminate\Support\Collection} */
    private function formOptions(): array
    {
        $mainCategories = PurchaseCategory::main()->active()
            ->with(['children' => fn ($q) => $q->active()->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')->orderBy('name')->get();
        $subcategories = PurchaseCategory::sub()->active()->orderBy('sort_order')->orderBy('name')->get();
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();

        return [$mainCategories, $subcategories, $suppliers];
    }
}
