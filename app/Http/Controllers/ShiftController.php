<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\ShiftTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = Shift::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $activeShift = Shift::where('user_id', auth()->id())
            ->where('status', 'active')
            ->first();

        $modules = $this->currentUser()->role->modules()->get();

        return view('modules.shifts', [
            'shifts' => $shifts,
            'activeShift' => $activeShift,
            'modules' => $modules,
        ]);
    }

    public function startShift(Request $request)
    {
        $validated = $request->validate([
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        // Check if user already has active shift
        $existingShift = Shift::where('user_id', auth()->id())
            ->where('status', 'active')
            ->first();

        if ($existingShift) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active shift. Please close it first.',
            ], 422);
        }

        $shift = Shift::create([
            'user_id' => auth()->id(),
            'status' => 'active',
            'started_at' => now(),
            'opening_balance' => $validated['opening_balance'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'shift_id' => $shift->id,
            'message' => 'Shift started successfully',
        ]);
    }

    public function getActiveShift()
    {
        $shift = Shift::where('user_id', auth()->id())
            ->where('status', 'active')
            ->first();

        if (!$shift) {
            return response()->json([
                'active' => false,
                'shift' => null,
            ]);
        }

        $openingBalance = $shift->opening_balance ?? 0;

        // Calculate sales from orders created during this shift
        $totalSales = DB::table('orders')
            ->where('user_id', auth()->id())
            ->where('created_at', '>=', $shift->started_at ?? $shift->created_at)
            ->where('status', 'completed')
            ->sum('total');

        return response()->json([
            'active' => true,
            'shift' => [
                'id' => $shift->id,
                'started_at' => $shift->started_at ? $shift->started_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
                'opening_balance' => (float) $openingBalance,
                'total_sales' => (float) $totalSales,
                'current_total' => (float) ($openingBalance + $totalSales),
            ],
        ]);
    }

    public function closeShift(Request $request)
    {
        $validated = $request->validate([
            'shift_id' => 'required|exists:shifts,id',
            'actual_total' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $shift = Shift::findOrFail($validated['shift_id']);

        if ($shift->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($shift->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'This shift is not active',
            ], 422);
        }

        $openingBalance = $shift->opening_balance ?? 0;

        // Calculate total sales during shift
        $totalSales = DB::table('orders')
            ->where('user_id', auth()->id())
            ->where('created_at', '>=', $shift->started_at ?? $shift->created_at)
            ->where('status', 'completed')
            ->sum('total');

        $expectedTotal = $openingBalance + $totalSales;
        $variance = $validated['actual_total'] - $expectedTotal;

        // Update shift with closing details
        $shift->update([
            'status' => 'closed',
            'ended_at' => now(),
            'closing_balance' => $validated['actual_total'],
            'expected_total' => $expectedTotal,
            'actual_total' => $validated['actual_total'],
            'variance' => $variance,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Also update shift_details for detailed records
        DB::table('shift_details')->updateOrInsert(
            ['shift_id' => $shift->id],
            [
                'opening_balance' => $openingBalance,
                'closing_balance' => $validated['actual_total'],
                'expected_total' => $expectedTotal,
                'actual_total' => $validated['actual_total'],
                'variance' => $variance,
                'notes' => $validated['notes'] ?? null,
                'closed_at' => now(),
                'updated_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Shift closed successfully',
        ]);
    }

    public function getShiftDetails(Shift $shift)
    {
        if ($shift->user_id !== auth()->id() && !auth()->user()->role->modules()->where('name', 'Reports')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Get sales
        $totalSales = DB::table('orders')
            ->where('user_id', $shift->user_id)
            ->where('created_at', '>=', $shift->started_at ?? $shift->created_at)
            ->where('status', 'completed')
            ->sum('total');

        // Get discounts
        $totalDiscounts = DB::table('orders')
            ->where('user_id', $shift->user_id)
            ->where('created_at', '>=', $shift->started_at ?? $shift->created_at)
            ->where('status', 'completed')
            ->sum(DB::raw('COALESCE(discount_amount, 0)'));

        // Get tax
        $totalTax = DB::table('orders')
            ->where('user_id', $shift->user_id)
            ->where('created_at', '>=', $shift->started_at ?? $shift->created_at)
            ->where('status', 'completed')
            ->sum(DB::raw('COALESCE(tax_amount, 0)'));

        $openingBalance = $shift->opening_balance ?? 0;

        return response()->json([
            'shift' => [
                'id' => $shift->id,
                'user_name' => $shift->user->name ?? 'Unknown',
                'started_at' => $shift->started_at ? $shift->started_at->format('Y-m-d H:i:s') : $shift->created_at->format('Y-m-d H:i:s'),
                'ended_at' => $shift->ended_at ? $shift->ended_at->format('Y-m-d H:i:s') : null,
                'opening_balance' => (float) $openingBalance,
                'closing_balance' => (float) $shift->closing_balance,
                'expected_total' => (float) $shift->expected_total,
                'actual_total' => (float) $shift->actual_total,
                'variance' => (float) $shift->variance,
                'notes' => $shift->notes,
                'total_sales' => (float) $totalSales,
                'total_discounts' => (float) $totalDiscounts,
                'total_tax' => (float) $totalTax,
                'status' => $shift->status === 'active' ? 'Active' : 'Closed',
            ],
        ]);
    }

    public function recordTransaction(Request $request, Shift $shift)
    {
        if ($shift->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'order_id' => 'nullable|exists:orders,id',
            'transaction_type' => 'required|in:sale,discount,refund,adjustment',
            'amount' => 'required|numeric',
            'payment_method' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $transaction = ShiftTransaction::create([
            'shift_id' => $shift->id,
            'order_id' => $validated['order_id'] ?? null,
            'transaction_type' => $validated['transaction_type'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'transaction' => $transaction,
        ]);
    }
}
