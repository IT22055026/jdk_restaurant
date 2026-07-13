<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('table_id')->nullable()->constrained('restaurant_tables')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->enum('order_type', ['dine_in', 'takeaway', 'delivery', 'vip_room'])->default('dine_in');
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled', 'hold'])->default('pending');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'mixed'])->nullable();
            $table->decimal('amount_paid', 12, 2)->nullable();
            $table->decimal('change_amount', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('waiter_name')->nullable();
            $table->timestamp('kot_printed_at')->nullable();
            $table->timestamp('bot_printed_at')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
