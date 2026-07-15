<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('modules')->where('route', 'ingredients.index')->update(['name' => 'Included Items']);
    }

    public function down(): void
    {
        DB::table('modules')->where('route', 'ingredients.index')->update(['name' => 'Ingredients']);
    }
};
