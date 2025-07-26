<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Relations\HasOne;


class Sale extends Model
{
     

    protected $fillable = [
        'buyer_id',
        'car_id',
        'price',
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
