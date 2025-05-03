<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    protected $fillable = [
        'owner_id', 'make', 'model', 'vin', 'seating_capacity', 'license_plate', 'status',
        'price_per_day', 'fuel_type', 'transmission', 'location_lat', 'location_long'
    ];

    public function images()
    {
    return $this->hasMany(CarImage::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function inspections()
    {
        return $this->hasMany(VehicleInspection::class);
    }
    public function maintenanceRecords()
{
    return $this->hasMany(MaintenanceRecord::class);
}

}
