<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('modules')->where('route', 'bill-history.index')->update([
            'name' => 'Sales Report',
            'description' => 'Every sale and what happened to it, with revoke for completed bills',
            'icon' => 'chart-line',
            'route' => 'sales-report.index',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('modules')->where('route', 'sales-report.index')->update([
            'name' => 'Bill History',
            'description' => 'View every bill and what happened to it (completed, held, discarded, etc.)',
            'icon' => 'receipt',
            'route' => 'bill-history.index',
            'updated_at' => now(),
        ]);
    }
};
