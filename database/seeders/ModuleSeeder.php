<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            [
                'name' => 'POS & Billing',
                'description' => 'Point of sale and billing management',
                'icon' => 'cash-register',
                'route' => 'pos.index',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Customer Management',
                'description' => 'Manage customers and customer information',
                'icon' => 'users',
                'route' => 'customers.index',
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Category Management',
                'description' => 'Manage product categories',
                'icon' => 'tags',
                'route' => 'categories.index',
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Employee Management',
                'description' => 'Manage employees and staff',
                'icon' => 'user-tie',
                'route' => 'employees.index',
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Inventory & Products',
                'description' => 'Manage products and inventory',
                'icon' => 'boxes',
                'route' => 'inventory.index',
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Supplier Management',
                'description' => 'Manage suppliers and purchases',
                'icon' => 'truck',
                'route' => 'suppliers.index',
                'sort_order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Wastage Management',
                'description' => 'Track product wastage and losses',
                'icon' => 'trash-alt',
                'route' => 'wastage.index',
                'sort_order' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Reports',
                'description' => 'Generate and view reports',
                'icon' => 'chart-bar',
                'route' => 'reports.index',
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Settings',
                'description' => 'System settings and configuration',
                'icon' => 'cog',
                'route' => 'settings.index',
                'sort_order' => 9,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Included Items',
                'description' => 'Manage raw included items and recipes (bill of materials)',
                'icon' => 'carrot',
                'route' => 'ingredients.index',
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
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
                'name' => 'Sales Report',
                'description' => 'Every sale and what happened to it, with revoke for completed bills',
                'icon' => 'chart-line',
                'route' => 'sales-report.index',
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

        $adminId = DB::table('roles')->where('name', 'Admin')->first()->id;
        $managerId = DB::table('roles')->where('name', 'Manager')->first()->id;
        $cashierId = DB::table('roles')->where('name', 'Cashier')->first()->id;

        $allModules = DB::table('modules')->get();
        $posModuleId = DB::table('modules')->where('name', 'POS & Billing')->first()->id;
        $shiftsModuleId = DB::table('modules')->where('name', 'Shifts & Till Management')->first()->id ?? null;
        $salesReportModuleId = DB::table('modules')->where('name', 'Sales Report')->first()->id ?? null;

        // Cashiers only ever touch the till at the start/end of their shift and
        // need to check how their own sales landed, so besides POS & Billing
        // they also get Shifts & Till Management and Sales Report — everything
        // else (customers, inventory, employees, settings, etc.) stays
        // admin/manager-only.
        $cashierModuleIds = array_filter([$posModuleId, $shiftsModuleId, $salesReportModuleId]);

        foreach ($allModules as $module) {
            DB::table('role_module')->insertOrIgnore([
                'role_id' => $adminId,
                'module_id' => $module->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($module->id !== $posModuleId) {
                DB::table('role_module')->insertOrIgnore([
                    'role_id' => $managerId,
                    'module_id' => $module->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (in_array($module->id, $cashierModuleIds)) {
                DB::table('role_module')->insertOrIgnore([
                    'role_id' => $cashierId,
                    'module_id' => $module->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
