<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The "Expense Management" module now covers two tabs — Purchases and
 * Expenses — so it lands on the Purchases tab by default.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('modules')->where('route', 'expenses.index')->update([
            'name' => 'Purchases & Expenses',
            'description' => 'Record supplier purchases and restaurant operating expenses',
            'icon' => 'basket-shopping',
            'route' => 'purchases.index',
            'sort_order' => 8,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('modules')->where('route', 'purchases.index')->update([
            'name' => 'Expense Management',
            'description' => 'Manage restaurant operating expenses and category tracking',
            'icon' => 'receipt',
            'route' => 'expenses.index',
            'sort_order' => 7,
            'updated_at' => now(),
        ]);
    }
};
