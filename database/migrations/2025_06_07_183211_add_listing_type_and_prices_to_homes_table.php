<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddListingTypeAndPricesToHomesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('homes', function (Blueprint $table) {
            $table->enum('listing_type', ['rent', 'sell', 'both'])->default('rent');
            $table->decimal('rent_per_month', 10, 2)->nullable();
            $table->decimal('sell_price', 15, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('homes', function (Blueprint $table) {
            $table->dropColumn(['listing_type', 'rent_per_month', 'sell_price']);
        });
    }
}
