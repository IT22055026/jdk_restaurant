<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Reports/Dashboard is now the post-login landing page, so it should
        // sort first in the sidebar. The module's `name` column is left
        // untouched (ShiftController gates on it) — only its display order
        // changes; the sidebar shows a friendlier "Dashboard" label for it.
        DB::table('modules')->where('route', 'reports.index')->update(['sort_order' => 0]);
    }

    public function down(): void
    {
        DB::table('modules')->where('route', 'reports.index')->update(['sort_order' => 8]);
    }
};
