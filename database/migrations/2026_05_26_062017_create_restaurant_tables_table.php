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
        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->integer('table_number')->unique();
            $table->string('name')->nullable();
            $table->integer('capacity')->default(4);
            $table->enum('status', ['available', 'occupied', 'reserved', 'cleaning'])->default('available');
            $table->enum('section', ['main', 'vip'])->default('main');
            $table->timestamp('occupied_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurant_tables');
    }
};
