<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHomeImagesTable extends Migration
{
    public function up()
    {
        Schema::create('home_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('home_id');
            $table->string('image_path');
            $table->timestamps();

            $table->foreign('home_id')->references('id')->on('homes')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('home_images');
    }
}
