<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHomeBookingIdToPaymentsTable extends Migration
{
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('home_booking_id')
                ->nullable()
                ->constrained('home_bookings')
                ->onDelete('cascade')
                ->after('booking_id'); // Place it after booking_id if it exists
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['home_booking_id']);
            $table->dropColumn('home_booking_id');
        });
    }
}
