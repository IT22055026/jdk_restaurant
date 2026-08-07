<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $moduleId = DB::table('modules')->insertGetId([
            'name' => 'Expense Management',
            'description' => 'Manage restaurant operating expenses and category tracking',
            'icon' => 'receipt',
            'route' => 'expenses.index',
            'sort_order' => 7,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adminRole = DB::table('roles')->where('name', 'Admin')->first();
        $managerRole = DB::table('roles')->where('name', 'Manager')->first();

        if ($adminRole) {
            DB::table('role_module')->insertOrIgnore([
                'role_id' => $adminRole->id,
                'module_id' => $moduleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($managerRole) {
            DB::table('role_module')->insertOrIgnore([
                'role_id' => $managerRole->id,
                'module_id' => $moduleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $module = DB::table('modules')->where('name', 'Expense Management')->first();
        if ($module) {
            DB::table('role_module')->where('module_id', $module->id)->delete();
            DB::table('modules')->where('id', $module->id)->delete();
        }
    }
};
