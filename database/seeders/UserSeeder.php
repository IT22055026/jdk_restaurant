<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRoleId = DB::table('roles')->where('name', 'Admin')->first()->id;
        $managerRoleId = DB::table('roles')->where('name', 'Manager')->first()->id;
        $cashierRoleId = DB::table('roles')->where('name', 'Cashier')->first()->id;

        DB::table('users')->insert([
            [
                'name' => 'Admin User',
                'email' => 'admin@restaurant.local',
                'password' => Hash::make('password'),
                'role_id' => $adminRoleId,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Manager User',
                'email' => 'manager@restaurant.local',
                'password' => Hash::make('password'),
                'role_id' => $managerRoleId,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cashier User',
                'email' => 'cashier@restaurant.local',
                'password' => Hash::make('password'),
                'role_id' => $cashierRoleId,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('employees')->insert([
            [
                'user_id' => 1,
                'phone' => '555-0001',
                'address' => '123 Admin Street',
                'city' => 'Restaurant City',
                'state' => 'RC',
                'postal_code' => '12345',
                'hire_date' => now()->subMonths(12),
                'salary' => 5000.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 2,
                'phone' => '555-0002',
                'address' => '456 Manager Avenue',
                'city' => 'Restaurant City',
                'state' => 'RC',
                'postal_code' => '12345',
                'hire_date' => now()->subMonths(6),
                'salary' => 3500.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 3,
                'phone' => '555-0003',
                'address' => '789 Cashier Road',
                'city' => 'Restaurant City',
                'state' => 'RC',
                'postal_code' => '12345',
                'hire_date' => now()->subMonths(3),
                'salary' => 2500.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
