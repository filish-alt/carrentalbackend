<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBookingTypeAndPurchaseDateToHomeBookingsTable extends Migration
{
    public function up()
    {
        Schema::table('home_bookings', function (Blueprint $table) {
            $table->enum('booking_type', ['rent', 'buy'])->default('rent')->after('home_id');
            $table->dateTime('purchase_date')->nullable()->after('check_out_date');
        });
    }

    public function down()
    {
        Schema::table('home_bookings', function (Blueprint $table) {
            $table->dropColumn(['booking_type', 'purchase_date']);
        });
    }
}
