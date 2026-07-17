<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('discard_reason')->nullable()->after('notes');
            $table->foreignId('discarded_by')->nullable()->after('discard_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('discarded_at')->nullable()->after('discarded_by');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discarded_by');
            $table->dropColumn(['discard_reason', 'discarded_at']);
        });
    }
};
