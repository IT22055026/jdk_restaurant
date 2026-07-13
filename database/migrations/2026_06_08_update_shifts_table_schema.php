<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('shifts', 'opening_balance')) {
                $table->decimal('opening_balance', 10, 2)->default(0)->after('user_id');
            }
            if (!Schema::hasColumn('shifts', 'closing_balance')) {
                $table->decimal('closing_balance', 10, 2)->nullable()->after('opening_balance');
            }
            if (!Schema::hasColumn('shifts', 'expected_total')) {
                $table->decimal('expected_total', 10, 2)->default(0)->after('closing_balance');
            }
            if (!Schema::hasColumn('shifts', 'actual_total')) {
                $table->decimal('actual_total', 10, 2)->nullable()->after('expected_total');
            }
            if (!Schema::hasColumn('shifts', 'variance')) {
                $table->decimal('variance', 10, 2)->nullable()->after('actual_total');
            }
            if (!Schema::hasColumn('shifts', 'status')) {
                $table->string('status')->default('active')->after('is_active');
            }
            if (!Schema::hasColumn('shifts', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
        });

        // Migrate data from opening_amount to opening_balance (if column exists)
        if (Schema::hasColumn('shifts', 'opening_amount')) {
            DB::statement('UPDATE shifts SET opening_balance = opening_amount WHERE opening_amount IS NOT NULL');
        }

        // Migrate data from is_active to status
        if (Schema::hasColumn('shifts', 'is_active')) {
            DB::statement("UPDATE shifts SET status = 'active' WHERE is_active = 1");
            DB::statement("UPDATE shifts SET status = 'closed' WHERE is_active = 0 AND status = 'active'");
        }
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            if (Schema::hasColumn('shifts', 'opening_balance')) {
                $table->dropColumn('opening_balance');
            }
            if (Schema::hasColumn('shifts', 'closing_balance')) {
                $table->dropColumn('closing_balance');
            }
            if (Schema::hasColumn('shifts', 'expected_total')) {
                $table->dropColumn('expected_total');
            }
            if (Schema::hasColumn('shifts', 'actual_total')) {
                $table->dropColumn('actual_total');
            }
            if (Schema::hasColumn('shifts', 'variance')) {
                $table->dropColumn('variance');
            }
            if (Schema::hasColumn('shifts', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('shifts', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
