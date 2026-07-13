<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->integer('sort_order')->default(99)->after('route');
        });

        $order = [
            'POS & Billing'        => 1,
            'Customer Management'  => 2,
            'Category Management'  => 3,
            'Employee Management'  => 4,
            'Inventory & Products' => 5,
            'Supplier Management'  => 6,
            'Wastage Management'   => 7,
            'Reports'              => 8,
            'Settings'             => 9,
        ];

        foreach ($order as $name => $sort) {
            DB::table('modules')->where('name', $name)->update(['sort_order' => $sort]);
        }
    }

    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
