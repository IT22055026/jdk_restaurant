<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::paginate(10);
        $modules = $this->currentUser()->role->modules()->get();

        return view('modules.suppliers-list', [
            'suppliers' => $suppliers,
            'modules' => $modules,
        ]);
    }

    public function create()
    {
        $modules = $this->currentUser()->role->modules()->get();

        return view('modules.suppliers-create', [
            'modules' => $modules,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:suppliers,name',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string|max:1000',
        ]);

        Supplier::create($validated);

        return redirect()->route('suppliers.index')->with('success', 'Supplier created successfully');
    }

    public function edit(Supplier $supplier)
    {
        $modules = $this->currentUser()->role->modules()->get();

        return view('modules.suppliers-edit', [
            'supplier' => $supplier,
            'modules' => $modules,
        ]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:suppliers,name,' . $supplier->id,
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string|max:1000',
        ]);

        $supplier->update($validated);

        return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted successfully');
    }
}
