<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Enable PostGIS extension if not already enabled
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        // Create GIN indexes for full-text search
        Schema::table('cars', function ($table) {
            DB::statement('CREATE INDEX IF NOT EXISTS cars_make_model_gin_idx ON cars USING gin(to_tsvector(\'english\', make || \' \' || model))');
        });

        Schema::table('homes', function ($table) {
            DB::statement('CREATE INDEX IF NOT EXISTS homes_title_description_gin_idx ON homes USING gin(to_tsvector(\'english\', title || \' \' || COALESCE(description, \'\')))');
        });

        // Create GiST indexes for spatial queries
        Schema::table('cars', function ($table) {
            DB::statement('CREATE INDEX IF NOT EXISTS cars_location_gist_idx ON cars USING gist(ST_MakePoint(location_long, location_lat))');
        });

        Schema::table('homes', function ($table) {
            DB::statement('CREATE INDEX IF NOT EXISTS homes_location_gist_idx ON homes USING gist(ST_MakePoint(longitude, latitude))');
        });

        // Create partial indexes for status columns
        Schema::table('cars', function ($table) {
            DB::statement('CREATE INDEX IF NOT EXISTS cars_available_idx ON cars (id) WHERE status = \'available\'');
        });

        Schema::table('homes', function ($table) {
            DB::statement('CREATE INDEX IF NOT EXISTS homes_available_idx ON homes (id) WHERE status = \'available\'');
        });

        // Create materialized views for complex queries
        DB::statement('
            CREATE MATERIALIZED VIEW IF NOT EXISTS available_cars_view AS
            SELECT 
                c.*,
                u.first_name as owner_first_name,
                u.last_name as owner_last_name
            FROM cars c
            JOIN users u ON c.owner_id = u.id
            WHERE c.status = \'available\'
            WITH DATA
        ');

        DB::statement('
            CREATE MATERIALIZED VIEW IF NOT EXISTS available_homes_view AS
            SELECT 
                h.*,
                u.first_name as owner_first_name,
                u.last_name as owner_last_name
            FROM homes h
            JOIN users u ON h.owner_id = u.id
            WHERE h.status = \'available\'
            WITH DATA
        ');

        // Create indexes on materialized views
        DB::statement('CREATE INDEX IF NOT EXISTS available_cars_price_idx ON available_cars_view (price_per_day)');
        DB::statement('CREATE INDEX IF NOT EXISTS available_homes_price_idx ON available_homes_view (price_per_night)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop materialized views
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS available_cars_view');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS available_homes_view');

        // Drop GIN indexes
        DB::statement('DROP INDEX IF EXISTS cars_make_model_gin_idx');
        DB::statement('DROP INDEX IF EXISTS homes_title_description_gin_idx');

        // Drop GiST indexes
        DB::statement('DROP INDEX IF EXISTS cars_location_gist_idx');
        DB::statement('DROP INDEX IF EXISTS homes_location_gist_idx');

        // Drop partial indexes
        DB::statement('DROP INDEX IF EXISTS cars_available_idx');
        DB::statement('DROP INDEX IF EXISTS homes_available_idx');
    }
}; 