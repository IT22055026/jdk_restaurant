<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('sort_order')->paginate(10);
        $modules = $this->currentUser()->role->modules()->get();

        return view('modules.categories-list', [
            'categories' => $categories,
            'modules' => $modules,
        ]);
    }

    public function create()
    {
        $modules = $this->currentUser()->role->modules()->get();

        return view('modules.categories-create', [
            'modules' => $modules,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['color'] = '#dc2626';
        $validated['icon'] = 'utensils';
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        Category::create($validated);

        return redirect()->route('categories.index')->with('success', 'Category created successfully');
    }

    public function edit(Category $category)
    {
        $modules = $this->currentUser()->role->modules()->get();

        return view('modules.categories-edit', [
            'category' => $category,
            'modules' => $modules,
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['color'] = $category->color ?: '#dc2626';
        $validated['icon'] = $category->icon ?: 'utensils';
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $category->update($validated);

        return redirect()->route('categories.index')->with('success', 'Category updated successfully');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Category deleted successfully');
    }
}
