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
    Schema::create('listing_fees', function (Blueprint $table) {
    $table->id();
    $table->enum('listing_type', ['rent', 'sell', 'both']);
    $table->enum('item_type', ['car', 'home']);
    $table->decimal('fee', 10, 2);
    $table->string('currency')->default('ETB');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
