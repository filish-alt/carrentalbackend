<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('general_infos', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // e.g., 'about_us', 'header_tagline'
            $table->text('value');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_infos');
    }
};
