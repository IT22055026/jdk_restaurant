<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::main()
            ->with(['children' => fn ($q) => $q->orderBy('sort_order')->orderBy('name')])
            ->withCount('expenses')
            ->orderBy('sort_order')->orderBy('name')
            ->get();
        $modules = $this->currentUser()->role->modules()->get();

        return view('modules.expenses.categories', compact('categories', 'modules'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:main,sub',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'parent_id' => 'nullable|required_if:type,sub|exists:expense_categories,id',
        ]);

        $parentId = $validated['type'] === 'sub' ? $validated['parent_id'] : null;

        $duplicate = ExpenseCategory::where('parent_id', $parentId)
            ->whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['name' => 'A category with this name already exists in that group.'])->withInput();
        }

        $nextSortOrder = 1 + (int) ExpenseCategory::where('parent_id', $parentId)->max('sort_order');

        ExpenseCategory::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'parent_id' => $parentId,
            'is_active' => true,
            'sort_order' => $nextSortOrder,
        ]);

        return redirect()->route('expense-categories.index')->with('success', 'Category added successfully.');
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $duplicate = ExpenseCategory::where('parent_id', $expenseCategory->parent_id)
            ->where('id', '!=', $expenseCategory->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['name' => 'A category with this name already exists in that group.'])->withInput();
        }

        $expenseCategory->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('expense-categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        if ($expenseCategory->children()->exists()) {
            return back()->with('error', 'Cannot delete a main category that still has sub-categories. Delete or reassign them first.');
        }

        if ($expenseCategory->expenses()->exists()) {
            return back()->with('error', 'Cannot delete a category that already has expense records. Deactivate it instead.');
        }

        $expenseCategory->delete();

        return redirect()->route('expense-categories.index')->with('success', 'Category deleted successfully.');
    }
}
