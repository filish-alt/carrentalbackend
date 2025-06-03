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
        Schema::table('users', function (Blueprint $table) {
            // Rename the column
            $table->renameColumn('driver_liscence', 'driver_licence');
        });

        Schema::table('users', function (Blueprint $table) {
            // Make it nullable
            $table->string('driver_licence')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('users', function (Blueprint $table) {
            // Reverse nullable
            $table->string('driver_licence')->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            // Rename it back
            $table->renameColumn('driver_licence', 'driver_liscence');
        });
    }
};
