<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shift_transactions')) {
            Schema::create('shift_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('shift_id');
                $table->unsignedBigInteger('order_id')->nullable();
                $table->string('transaction_type');
                $table->decimal('amount', 10, 2);
                $table->string('payment_method')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();

                $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('cascade');
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
                $table->index('shift_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_transactions');
    }
};
