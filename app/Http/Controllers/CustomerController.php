<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::paginate(10);
        $modules = $this->currentUser()->role->modules()->get();
        return view('modules.customers-list', [
            'customers' => $customers,
            'modules' => $modules,
        ]);
    }

    public function create()
    {
        $modules = $this->currentUser()->role->modules()->get();
        return view('modules.customers-create', ['modules' => $modules]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20|unique:customers,phone_number',
            'email' => 'nullable|email|max:255|unique:customers,email',
            'address' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
        ]);

        Customer::create($validated);
        return redirect()->route('customers.index')->with('success', 'Customer created successfully');
    }

    public function search(Request $request)
    {
        $phone = $request->query('phone', '');
        if (strlen($phone) < 3) {
            return response()->json([]);
        }
        $customers = Customer::where('phone_number', 'like', $phone . '%')
            ->limit(5)
            ->get(['id', 'name', 'phone_number', 'email', 'address']);
        return response()->json($customers);
    }

    public function show(Customer $customer)
    {
        $modules = $this->currentUser()->role->modules()->get();
        return view('modules.customers-show', [
            'customer' => $customer,
            'modules' => $modules,
        ]);
    }

    public function edit(Customer $customer)
    {
        $modules = $this->currentUser()->role->modules()->get();
        return view('modules.customers-edit', [
            'customer' => $customer,
            'modules' => $modules,
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20|unique:customers,phone_number,' . $customer->id,
            'email' => 'nullable|email|max:255|unique:customers,email,' . $customer->id,
            'address' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
        ]);

        $customer->update($validated);
        return redirect()->route('customers.index')->with('success', 'Customer updated successfully');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully');
    }
}
