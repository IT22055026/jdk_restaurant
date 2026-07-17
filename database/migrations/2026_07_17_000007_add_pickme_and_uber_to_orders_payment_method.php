<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * payment_method was a rigid DB-level ENUM, which needs a schema migration
     * every time a new payment channel (PickMe, Uber, ...) is added. Widened to
     * a plain string here — the actual set of accepted values is enforced by
     * validation in PosController::payOrder(), which is the only writer.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method', 30)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'mixed'])->nullable()->change();
        });
    }
};
