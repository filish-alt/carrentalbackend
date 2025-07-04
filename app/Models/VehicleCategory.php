<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="VehicleCategory",
 *     type="object",
 *     required={"id", "category_name"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="category_name", type="string", example="SUV"),
 *     @OA\Property(property="description", type="string", example="Sport Utility Vehicle"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class VehicleCategory extends Model
{
    protected $table = 'vehicle_catagories'; // Note: table name matches the migration with typo
    protected $fillable = ['category_name', 'description'];
}
