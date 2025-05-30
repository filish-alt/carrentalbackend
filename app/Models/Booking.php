<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{


    protected $fillable = [
        'user_id',
        'car_id',
        'pickup_date',
        'return_date',
        'total_price',
        'status',
    ];

    // Relationships

    public function user()
    {
        return $this->belongsTo(Users::class);
    }

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function payment()
{
    return $this->hasOne(Payment::class);
}
}
