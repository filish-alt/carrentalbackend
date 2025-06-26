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
        // Optimize users table
        Schema::table('users', function (Blueprint $table) {
            // Add indexes for frequently queried columns
            $table->index('email');
            $table->index('phone');
            $table->index('status');
            $table->index('role');
            $table->index('created_at');
        });

        // Optimize cars table
        Schema::table('cars', function (Blueprint $table) {
            // Add indexes for search and filtering
            $table->index('make');
            $table->index('model');
            $table->index('status');
            $table->index('price_per_day');
            $table->index('created_at');
            
            // Add composite index for location-based queries
            $table->index(['location_lat', 'location_long']);
        });

        // Optimize bookings table
        Schema::table('bookings', function (Blueprint $table) {
            // Add indexes for date-based queries
            $table->index('pickup_date');
            $table->index('return_date');
            $table->index('status');
            $table->index('created_at');
            
            // Add composite index for user's booking history
            $table->index(['user_id', 'created_at']);
        });

        // Optimize homes table
        Schema::table('homes', function (Blueprint $table) {
            // Add indexes for search and filtering
            $table->index('city');
            $table->index('status');
            $table->index('price_per_night');
            $table->index('created_at');
            
            // Add composite index for location-based queries
            $table->index(['latitude', 'longitude']);
            
            // Add index for property type filtering
            $table->index('property_type');
        });

        // Optimize payments table
        Schema::table('payments', function (Blueprint $table) {
            // Add indexes for payment tracking
            $table->index('payment_status');
            $table->index('transaction_date');
            $table->index('created_at');
        });

        // Optimize maintenance_records table
        Schema::table('maintenance_records', function (Blueprint $table) {
            // Add indexes for maintenance tracking
            $table->index('maintenance_date');
            $table->index('status');
            $table->index('created_at');
        });

        // Optimize user_verifications table
        Schema::table('user_verifications', function (Blueprint $table) {
            // Add composite index for verification status checks
            $table->index(['id_verified', 'phone_verified', 'email_verified', 'payment_verified', 'car_verified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropIndex(['phone']);
            $table->dropIndex(['status']);
            $table->dropIndex(['role']);
            $table->dropIndex(['created_at']);
        });

        // Remove indexes from cars table
        Schema::table('cars', function (Blueprint $table) {
            $table->dropIndex(['make']);
            $table->dropIndex(['model']);
            $table->dropIndex(['status']);
            $table->dropIndex(['price_per_day']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['location_lat', 'location_long']);
        });

        // Remove indexes from bookings table
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['pickup_date']);
            $table->dropIndex(['return_date']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['user_id', 'created_at']);
        });

        // Remove indexes from homes table
        Schema::table('homes', function (Blueprint $table) {
            $table->dropIndex(['city']);
            $table->dropIndex(['status']);
            $table->dropIndex(['price_per_night']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropIndex(['property_type']);
        });

        // Remove indexes from payments table
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['transaction_date']);
            $table->dropIndex(['created_at']);
        });

        // Remove indexes from maintenance_records table
        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->dropIndex(['maintenance_date']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
        });

        // Remove indexes from user_verifications table
        Schema::table('user_verifications', function (Blueprint $table) {
            $table->dropIndex(['id_verified', 'phone_verified', 'email_verified', 'payment_verified', 'car_verified']);
        });
    }
}; 