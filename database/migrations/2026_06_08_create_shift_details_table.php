<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shift_details')) {
            Schema::create('shift_details', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('shift_id');
                $table->decimal('opening_balance', 12, 2)->default(0);
                $table->decimal('closing_balance', 12, 2)->nullable();
                $table->decimal('expected_total', 12, 2)->nullable();
                $table->decimal('actual_total', 12, 2)->nullable();
                $table->decimal('variance', 12, 2)->nullable();
                $table->text('notes')->nullable();
                $table->dateTime('closed_at')->nullable();
                $table->timestamps();

                $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('cascade');
                $table->index('shift_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_details');
    }
};
