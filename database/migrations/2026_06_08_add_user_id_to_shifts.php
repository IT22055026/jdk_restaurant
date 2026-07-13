<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shifts') && !Schema::hasColumn('shifts', 'user_id')) {
            Schema::table('shifts', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->after('id')->nullable();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->index('user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shifts') && Schema::hasColumn('shifts', 'user_id')) {
            Schema::table('shifts', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }
    }
};
