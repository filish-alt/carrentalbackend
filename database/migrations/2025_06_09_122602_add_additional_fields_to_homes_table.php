<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalFieldsToHomesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('homes', function (Blueprint $table) {
            $table->string('furnished')->nullable();
            $table->decimal('area_sqm', 10, 2)->nullable();
            $table->integer('seating_capacity')->nullable();
            $table->string('parking')->nullable();
            $table->string('storage')->nullable();
            $table->string('loading_zone')->nullable();
            $table->string('payment_frequency')->nullable();
            $table->string('power_supply')->nullable();
            $table->string('kitchen')->nullable();
            $table->json('property_purposes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('homes', function (Blueprint $table) {
            $table->dropColumn([
                'furnished',
                'area_sqm',
                'seating_capacity',
                'parking',
                'storage',
                'loading_zone',
                'payment_frequency',
                'power_supply',
                'kitchen',
                'property_purposes',
            ]);
        });
    }
}
