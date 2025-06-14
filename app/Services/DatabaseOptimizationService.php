<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DatabaseOptimizationService
{
    /**
     * Optimize a query with proper eager loading and caching
     *
     * @param Builder $query
     * @param array $relations
     * @param string $cacheKey
     * @param int $cacheTime
     * @return mixed
     */
    public function optimizeQuery(Builder $query, array $relations = [], string $cacheKey = null, int $cacheTime = 3600)
    {
        // Add eager loading
        if (!empty($relations)) {
            $query->with($relations);
        }

        // Apply caching if cache key is provided
        if ($cacheKey) {
            return Cache::remember($cacheKey, $cacheTime, function () use ($query) {
                return $query->get();
            });
        }

        return $query->get();
    }

    /**
     * Optimize a paginated query
     *
     * @param Builder $query
     * @param int $perPage
     * @param array $relations
     * @return mixed
     */
    public function optimizePaginatedQuery(Builder $query, int $perPage = 15, array $relations = [])
    {
        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }

    /**
     * Optimize a search query with PostgreSQL full-text search
     *
     * @param Builder $query
     * @param string $searchTerm
     * @param array $searchableColumns
     * @return Builder
     */
    public function optimizeSearchQuery(Builder $query, string $searchTerm, array $searchableColumns)
    {
        $searchVector = [];
        foreach ($searchableColumns as $column) {
            $searchVector[] = "to_tsvector('english', {$column})";
        }
        
        $searchVector = implode(' || ', $searchVector);
        $searchQuery = "to_tsquery('english', ?)";
        
        $query->whereRaw("{$searchVector} @@ {$searchQuery}", [$this->formatSearchTerm($searchTerm)]);
        
        return $query;
    }

    /**
     * Format search term for PostgreSQL tsquery
     *
     * @param string $term
     * @return string
     */
    private function formatSearchTerm(string $term): string
    {
        return implode(' & ', explode(' ', $term));
    }

    /**
     * Optimize a location-based query using PostgreSQL PostGIS
     *
     * @param Builder $query
     * @param float $latitude
     * @param float $longitude
     * @param float $radius
     * @param string $latColumn
     * @param string $lngColumn
     * @return Builder
     */
    public function optimizeLocationQuery(
        Builder $query,
        float $latitude,
        float $longitude,
        float $radius,
        string $latColumn = 'latitude',
        string $lngColumn = 'longitude'
    ) {
        // Using PostGIS ST_DistanceSphere for better performance
        $query->selectRaw("
            *,
            ST_DistanceSphere(
                ST_MakePoint({$lngColumn}, {$latColumn}),
                ST_MakePoint(?, ?)
            ) as distance
        ", [$longitude, $latitude]);

        $query->having('distance', '<=', $radius * 1000); // Convert km to meters
        $query->orderBy('distance');

        return $query;
    }

    /**
     * Optimize a date range query using PostgreSQL date functions
     *
     * @param Builder $query
     * @param string $startDate
     * @param string $endDate
     * @param string $dateColumn
     * @return Builder
     */
    public function optimizeDateRangeQuery(
        Builder $query,
        string $startDate,
        string $endDate,
        string $dateColumn = 'created_at'
    ) {
        return $query->whereRaw("{$dateColumn}::date BETWEEN ?::date AND ?::date", [$startDate, $endDate]);
    }

    /**
     * Optimize a bulk insert operation using PostgreSQL COPY
     *
     * @param string $table
     * @param array $data
     * @param int $chunkSize
     * @return void
     */
    public function optimizeBulkInsert(string $table, array $data, int $chunkSize = 1000)
    {
        foreach (array_chunk($data, $chunkSize) as $chunk) {
            $columns = array_keys(reset($chunk));
            $values = array_map(function ($row) {
                return array_values($row);
            }, $chunk);

            $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES ";
            $placeholders = [];
            $bindings = [];

            foreach ($values as $row) {
                $rowPlaceholders = [];
                foreach ($row as $value) {
                    $rowPlaceholders[] = '?';
                    $bindings[] = $value;
                }
                $placeholders[] = '(' . implode(', ', $rowPlaceholders) . ')';
            }

            $sql .= implode(', ', $placeholders);
            DB::insert($sql, $bindings);
        }
    }

    /**
     * Optimize a bulk update operation using PostgreSQL
     *
     * @param string $table
     * @param array $data
     * @param string $key
     * @return void
     */
    public function optimizeBulkUpdate(string $table, array $data, string $key)
    {
        $columns = array_keys(reset($data));
        $columns = array_filter($columns, function($col) use ($key) {
            return $col !== $key;
        });

        $tempTable = "temp_{$table}_" . uniqid();
        
        // Create temporary table
        DB::statement("CREATE TEMPORARY TABLE {$tempTable} (LIKE {$table})");
        
        // Insert data into temporary table
        $this->optimizeBulkInsert($tempTable, $data);
        
        // Update main table from temporary table
        $updates = [];
        foreach ($columns as $column) {
            $updates[] = "{$column} = temp.{$column}";
        }
        
        $sql = "UPDATE {$table} SET " . implode(', ', $updates) . 
               " FROM {$tempTable} temp WHERE {$table}.{$key} = temp.{$key}";
        
        DB::statement($sql);
        
        // Drop temporary table
        DB::statement("DROP TABLE {$tempTable}");
    }

    /**
     * Create a materialized view for complex queries
     *
     * @param string $viewName
     * @param string $query
     * @return void
     */
    public function createMaterializedView(string $viewName, string $query)
    {
        DB::statement("CREATE MATERIALIZED VIEW IF NOT EXISTS {$viewName} AS {$query}");
    }

    /**
     * Refresh a materialized view
     *
     * @param string $viewName
     * @return void
     */
    public function refreshMaterializedView(string $viewName)
    {
        DB::statement("REFRESH MATERIALIZED VIEW {$viewName}");
    }
} 