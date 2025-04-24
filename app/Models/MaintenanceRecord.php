<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceRecord extends Model
{
       /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'car_id',
        'maintenance_date',
        'note',
        'status',
        'cost',
    ];

      /**
     * Get the vehicle for this maintenance record.
     */
    public function vehicle()
    {
        return $this->belongsTo(Car::class);
    }
}
