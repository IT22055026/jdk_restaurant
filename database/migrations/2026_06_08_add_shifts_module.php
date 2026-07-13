<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $existingModule = DB::table('modules')->where('name', 'Shifts & Till Management')->first();

        if (!$existingModule) {
            DB::table('modules')->insert([
                'name' => 'Shifts & Till Management',
                'description' => 'Manage shifts, track till movements, and reconcile accounts',
                'icon' => 'clock',
                'route' => 'shifts.index',
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('modules')->where('name', 'Shifts & Till Management')->delete();
    }
};
