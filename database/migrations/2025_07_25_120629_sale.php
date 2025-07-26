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
        Schema::create('sales', function (Blueprint $table) {
        $table->id();
        $table->foreignId('car_id')->constrained()->onDelete('cascade');
        $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
        $table->decimal('price', 10, 2);
        $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
        $table->timestamps();
    });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
