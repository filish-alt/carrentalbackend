<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleInspection extends Model
{
    protected $fillable = [
        'car_id', 'booking_id', 'renter_id', 'owner_id', 'photos', 'condition_notes'
    ];

    protected $casts = [
        'photos' => 'array',
    ];

    public function car()
    {
        return $this->belongsTo(Car::class);
    }
}
