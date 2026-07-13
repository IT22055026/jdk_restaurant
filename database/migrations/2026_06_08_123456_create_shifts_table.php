<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shifts')) {
            Schema::create('shifts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->dateTime('started_at');
                $table->dateTime('ended_at')->nullable();
                $table->decimal('opening_balance', 10, 2)->default(0);
                $table->decimal('closing_balance', 10, 2)->nullable();
                $table->decimal('expected_total', 10, 2)->default(0);
                $table->decimal('actual_total', 10, 2)->nullable();
                $table->decimal('variance', 10, 2)->nullable();
                $table->string('status')->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->index('status');
                $table->index(['user_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
