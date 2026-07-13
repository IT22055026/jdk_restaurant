<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->insert([
            [
                'name' => 'Admin',
                'description' => 'Administrator - Full system access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Manager',
                'description' => 'Manager - Can manage inventory, employees, and reports',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cashier',
                'description' => 'Cashier - Can process POS transactions',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
