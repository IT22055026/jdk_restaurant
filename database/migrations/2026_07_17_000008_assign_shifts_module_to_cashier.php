<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $cashierId = DB::table('roles')->where('name', 'Cashier')->first()->id ?? null;
        $shiftsModuleId = DB::table('modules')->where('name', 'Shifts & Till Management')->first()->id ?? null;

        if ($cashierId && $shiftsModuleId) {
            DB::table('role_module')->insertOrIgnore([
                'role_id' => $cashierId,
                'module_id' => $shiftsModuleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $cashierId = DB::table('roles')->where('name', 'Cashier')->first()->id ?? null;
        $shiftsModuleId = DB::table('modules')->where('name', 'Shifts & Till Management')->first()->id ?? null;

        if ($cashierId && $shiftsModuleId) {
            DB::table('role_module')
                ->where('role_id', $cashierId)
                ->where('module_id', $shiftsModuleId)
                ->delete();
        }
    }
};
