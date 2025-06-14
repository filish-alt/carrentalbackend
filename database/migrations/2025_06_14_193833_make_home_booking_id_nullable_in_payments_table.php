<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeHomeBookingIdNullableInPaymentsTable extends Migration
{
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['home_booking_id']);
            $table->unsignedBigInteger('home_booking_id')->nullable()->change();
            $table->foreign('home_booking_id')->references('id')->on('home_bookings')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['home_booking_id']);
            $table->unsignedBigInteger('home_booking_id')->nullable(false)->change();
            $table->foreign('home_booking_id')->references('id')->on('home_bookings')->onDelete('cascade');
        });
    }
}
