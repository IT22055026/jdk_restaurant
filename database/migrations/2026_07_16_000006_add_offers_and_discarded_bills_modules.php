<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $modules = [
            [
                'name' => 'Discounts & Offers',
                'description' => 'Bundle menu items and included items into offers usable in billing',
                'icon' => 'gift',
                'route' => 'offers.index',
                'sort_order' => 11,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Discarded Bills',
                'description' => 'Audit trail of cancelled/discarded bills',
                'icon' => 'ban',
                'route' => 'discarded-bills.index',
                'sort_order' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('modules')->upsert(
            $modules,
            ['name'],
            ['description', 'icon', 'route', 'sort_order', 'updated_at']
        );

        $adminId = DB::table('roles')->where('name', 'Admin')->first()->id ?? null;
        $managerId = DB::table('roles')->where('name', 'Manager')->first()->id ?? null;

        $moduleIds = DB::table('modules')
            ->whereIn('name', ['Discounts & Offers', 'Discarded Bills'])
            ->pluck('id');

        foreach ($moduleIds as $moduleId) {
            foreach (array_filter([$adminId, $managerId]) as $roleId) {
                DB::table('role_module')->insertOrIgnore([
                    'role_id' => $roleId,
                    'module_id' => $moduleId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $moduleIds = DB::table('modules')
            ->whereIn('name', ['Discounts & Offers', 'Discarded Bills'])
            ->pluck('id');

        DB::table('role_module')->whereIn('module_id', $moduleIds)->delete();
        DB::table('modules')->whereIn('id', $moduleIds)->delete();
    }
};
