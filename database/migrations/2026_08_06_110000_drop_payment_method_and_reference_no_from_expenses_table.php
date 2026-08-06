<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
            if (Schema::hasColumn('expenses', 'reference_no')) {
                $table->dropColumn('reference_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('payment_method')->default('cash')->after('expense_date');
            $table->string('reference_no')->nullable()->after('payment_method');
        });
    }
};
