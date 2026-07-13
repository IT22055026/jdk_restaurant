<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::with('user')->paginate(10);
        $modules = $this->currentUser()->role->modules()->get();
        return view('modules.employees-list', [
            'employees' => $employees,
            'modules' => $modules,
        ]);
    }

    public function create()
    {
        $roles = Role::all();
        $modules = $this->currentUser()->role->modules()->get();
        return view('modules.employees-create', [
            'roles' => $roles,
            'modules' => $modules,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'hire_date' => 'nullable|date',
            'salary' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'role_id' => 'required|exists:roles,id',
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'status' => $validated['status'],
                'role_id' => $validated['role_id'],
            ]);

            Employee::create([
                'user_id' => $user->id,
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'city' => $validated['city'],
                'state' => $validated['state'],
                'postal_code' => $validated['postal_code'],
                'hire_date' => $validated['hire_date'],
                'salary' => $validated['salary'],
            ]);
        });

        return redirect()->route('employees.index')->with('success', 'Employee created successfully');
    }

    public function show(Employee $employee)
    {
        return redirect()->route('employees.edit', $employee);
    }

    public function edit(Employee $employee)
    {
        $roles = Role::all();
        $modules = $this->currentUser()->role->modules()->get();
        return view('modules.employees-edit', [
            'employee' => $employee->load('user'),
            'roles' => $roles,
            'modules' => $modules,
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $employee->user_id,
            'password' => 'nullable|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'hire_date' => 'nullable|date',
            'salary' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'role_id' => 'required|exists:roles,id',
        ]);

        DB::transaction(function () use ($validated, $employee) {
            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'status' => $validated['status'],
                'role_id' => $validated['role_id'],
            ];

            if (!empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $employee->user->update($updateData);

            $employee->update([
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'city' => $validated['city'],
                'state' => $validated['state'],
                'postal_code' => $validated['postal_code'],
                'hire_date' => $validated['hire_date'],
                'salary' => $validated['salary'],
            ]);
        });

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully');
    }
}
