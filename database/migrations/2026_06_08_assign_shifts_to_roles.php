<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $shiftsModule = DB::table('modules')->where('name', 'Shifts & Till Management')->first();

        if ($shiftsModule) {
            $roles = DB::table('roles')->get();

            foreach ($roles as $role) {
                // Check if assignment already exists
                $exists = DB::table('role_module')
                    ->where('role_id', $role->id)
                    ->where('module_id', $shiftsModule->id)
                    ->exists();

                if (!$exists) {
                    DB::table('role_module')->insert([
                        'role_id' => $role->id,
                        'module_id' => $shiftsModule->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $shiftsModule = DB::table('modules')->where('name', 'Shifts & Till Management')->first();

        if ($shiftsModule) {
            DB::table('role_module')->where('module_id', $shiftsModule->id)->delete();
        }
    }
};
