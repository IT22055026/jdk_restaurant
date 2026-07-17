<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('modules')->where('route', 'discarded-bills.index')->update([
            'name' => 'Bill History',
            'description' => 'View every bill and what happened to it (completed, held, discarded, etc.)',
            'icon' => 'receipt',
            'route' => 'bill-history.index',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('modules')->where('route', 'bill-history.index')->update([
            'name' => 'Discarded Bills',
            'description' => 'Audit trail of cancelled/discarded bills',
            'icon' => 'ban',
            'route' => 'discarded-bills.index',
            'updated_at' => now(),
        ]);
    }
};
