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
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade'); 
            $table->string('make');
            $table->string('model'); 
            $table->string('vin')->unique()->nullable();
            $table->string('license_plate')->unique()->nullable();
            $table->enum('status', ['pending', 'available', 'rented', 'pending_payment'])->default('pending');
            $table->decimal('price_per_day', 10, 2);
            $table->integer('seating_capacity');
            $table->enum('fuel_type', ['petrol', 'diesel', 'electric', 'hybrid']);
            $table->enum('transmission', ['manual', 'automatic']);
            $table->decimal('location_lat', 9, 6)->nullable();
            $table->decimal('location_long', 9, 6)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
