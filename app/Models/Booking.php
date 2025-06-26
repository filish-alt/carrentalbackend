<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class Booking extends Model implements AuditableContract
{

    use Auditable;

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
