<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSellFieldsToCarsTable extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->enum('listing_type', ['rent', 'sell', 'both'])->default('rent');
            $table->decimal('sell_price', 12, 2)->nullable();
            $table->boolean('is_negotiable')->default(false);
            $table->integer('mileage')->nullable();
            $table->integer('year')->nullable();
            $table->string('condition')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn([
                'listing_type',
                'sell_price',
                'is_negotiable',
                'mileage',
                'year',
                'condition',
            ]);
        });
    }
}
