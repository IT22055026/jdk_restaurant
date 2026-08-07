<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Support\BusinessDay;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with(['category.parent', 'user']);

        if ($request->filled('category_id')) {
            $category = ExpenseCategory::find($request->input('category_id'));
            if ($category) {
                $categoryIds = $category->isMain()
                    ? $category->children()->pluck('id')->push($category->id)
                    : collect([$category->id]);
                $query->whereIn('expense_category_id', $categoryIds);
            }
        }

        if ($request->filled('from')) {
            $query->whereDate('expense_date', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('expense_date', '<=', $request->input('to'));
        }

        $expenses = (clone $query)->latest('expense_date')->latest('id')->paginate(15)->withQueryString();

        $totalExpenses = (clone $query)->sum('amount');

        [$monthStart, $monthEnd] = BusinessDay::monthBoundsFor(BusinessDay::today());
        $thisMonthExpenses = Expense::whereBetween('expense_date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])->sum('amount');
        $todayExpenses = Expense::whereDate('expense_date', BusinessDay::today()->format('Y-m-d'))->sum('amount');

        // Category breakdown for a chosen month — defaults to the current month.
        $summaryMonth = $request->input('summary_month', BusinessDay::today()->format('Y-m'));
        try {
            $summaryDate = Carbon::createFromFormat('Y-m-d', $summaryMonth . '-01');
        } catch (\Exception) {
            $summaryDate = BusinessDay::today();
            $summaryMonth = $summaryDate->format('Y-m');
        }
        [$summaryStart, $summaryEnd] = BusinessDay::monthBoundsFor($summaryDate);

        $leafTotals = Expense::selectRaw('expense_category_id, SUM(amount) as total')
            ->whereBetween('expense_date', [$summaryStart->format('Y-m-d'), $summaryEnd->format('Y-m-d')])
            ->groupBy('expense_category_id')
            ->pluck('total', 'expense_category_id');

        $mainCategories = ExpenseCategory::main()
            ->with(['children' => fn ($q) => $q->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')->orderBy('name')->get();

        $categoryTotals = $mainCategories->map(function (ExpenseCategory $main) use ($leafTotals) {
            $ownTotal = (float) ($leafTotals[$main->id] ?? 0);
            $childTotal = $main->children->sum(fn ($child) => (float) ($leafTotals[$child->id] ?? 0));

            return [
                'category' => $main,
                'total' => $ownTotal + $childTotal,
                'children' => $main->children->map(fn ($child) => [
                    'category' => $child,
                    'total' => (float) ($leafTotals[$child->id] ?? 0),
                ]),
            ];
        })->filter(fn ($row) => $row['total'] > 0 || $row['children']->isNotEmpty())->values();

        $staffBreakdown = optional($mainCategories->firstWhere('name', 'Staff'))->children
            ?->map(fn ($child) => ['category' => $child, 'total' => (float) ($leafTotals[$child->id] ?? 0)]);

        $categories = ExpenseCategory::active()->orderBy('sort_order')->orderBy('name')->get();
        $modules = $this->currentUser()->role->modules()->get();

        return view('modules.expenses.index', compact(
            'expenses',
            'categories',
            'modules',
            'totalExpenses',
            'thisMonthExpenses',
            'todayExpenses',
            'categoryTotals',
            'staffBreakdown',
            'summaryMonth'
        ));
    }

    public function create()
    {
        [$mainCategories, $subcategories] = $this->formOptions();
        $modules = $this->currentUser()->role->modules()->get();

        $selectedCategory = ExpenseCategory::find(old('expense_category_id'));
        $selectedTopId = $selectedCategory ? ($selectedCategory->parent_id ?? $selectedCategory->id) : null;
        $selectedSubId = $selectedCategory && $selectedCategory->parent_id ? $selectedCategory->id : null;

        return view('modules.expenses.create', compact('mainCategories', 'subcategories', 'modules', 'selectedTopId', 'selectedSubId'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateExpense($request);

        $validated['user_id'] = auth()->id();

        Expense::create($validated);

        return redirect()->route('expenses.index')->with('success', 'Expense recorded successfully.');
    }

    public function edit(Expense $expense)
    {
        [$mainCategories, $subcategories] = $this->formOptions();
        $modules = $this->currentUser()->role->modules()->get();

        $selectedCategory = $expense->category;
        $selectedTopId = $selectedCategory->parent_id ?? $selectedCategory->id;
        $selectedSubId = $selectedCategory->parent_id ? $selectedCategory->id : null;

        return view('modules.expenses.edit', compact('expense', 'mainCategories', 'subcategories', 'modules', 'selectedTopId', 'selectedSubId'));
    }

    public function update(Request $request, Expense $expense)
    {
        $validated = $this->validateExpense($request);

        $expense->update($validated);

        return redirect()->route('expenses.index')->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Expense deleted successfully.');
    }

    public function exportWalkthroughPdf()
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.walkthrough-pdf')
            ->setPaper('a4', 'portrait');

        return $pdf->download('Expenses_Module_Walkthrough.pdf');
    }

    private function validateExpense(Request $request): array
    {
        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $category = ExpenseCategory::find($validated['expense_category_id']);
        if ($category && $category->isMain() && $category->children()->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'expense_category_id' => 'Please choose a specific sub-category.',
            ]);
        }

        return $validated;
    }

    /** @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection} */
    private function formOptions(): array
    {
        $mainCategories = ExpenseCategory::main()->active()
            ->with(['children' => fn ($q) => $q->active()->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')->orderBy('name')->get();
        $subcategories = ExpenseCategory::sub()->active()->orderBy('sort_order')->orderBy('name')->get();

        return [$mainCategories, $subcategories];
    }
}
