<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = ['user_id', 'car_id', 'rating', 'review_text'];

    public function user()
    {
        return $this->belongsTo(Users::class);
    }

    public function car()
    {
        return $this->belongsTo(Car::class);
    }
}
