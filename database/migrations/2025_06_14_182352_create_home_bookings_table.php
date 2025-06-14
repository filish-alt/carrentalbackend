<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHomeBookingsTable extends Migration
{
    public function up()
    {
        Schema::create('home_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('home_id')->constrained()->onDelete('cascade');
            $table->dateTime('check_in_date');
            $table->dateTime('check_out_date');
            $table->unsignedInteger('guests')->nullable();
            $table->decimal('total_price', 10, 2);
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('home_bookings');
    }
}
