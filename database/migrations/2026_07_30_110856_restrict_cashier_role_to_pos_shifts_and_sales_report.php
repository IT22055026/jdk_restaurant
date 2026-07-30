<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Cashier's canonical module set is exactly: POS & Billing (their job),
     * Shifts & Till Management (open/close their own till), and Sales
     * Report (check how their own sales landed). Earlier seed/migration
     * history left some environments with a broader grant than the code
     * currently intends — this reconciles any already-migrated database to
     * that set, on top of granting Sales Report which Cashier never had.
     */
    public function up(): void
    {
        $cashierId = DB::table('roles')->where('name', 'Cashier')->first()->id ?? null;
        if (!$cashierId) {
            return;
        }

        $keepModuleIds = DB::table('modules')
            ->whereIn('name', ['POS & Billing', 'Shifts & Till Management', 'Sales Report'])
            ->pluck('id');

        foreach ($keepModuleIds as $moduleId) {
            DB::table('role_module')->insertOrIgnore([
                'role_id' => $cashierId,
                'module_id' => $moduleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('role_module')
            ->where('role_id', $cashierId)
            ->whereNotIn('module_id', $keepModuleIds)
            ->delete();
    }

    /**
     * Not meaningfully reversible — we don't know what a since-removed grant
     * originally was. Down just re-grants everything, matching the state
     * before this file existed (any role gets every module).
     */
    public function down(): void
    {
        $cashierId = DB::table('roles')->where('name', 'Cashier')->first()->id ?? null;
        if (!$cashierId) {
            return;
        }

        $allModuleIds = DB::table('modules')->pluck('id');

        foreach ($allModuleIds as $moduleId) {
            DB::table('role_module')->insertOrIgnore([
                'role_id' => $cashierId,
                'module_id' => $moduleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
