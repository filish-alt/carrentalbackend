<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class UserVerification extends Model
{
    protected $fillable = [
        'user_id',
        'id_verified',
        'id_document',
        'phone_verified',
        'email_verified',
        'payment_verified',
        'car_verified',
        'car_document',
    ];

    protected $casts = [
        'id_verified' => 'boolean',
        'phone_verified' => 'boolean',
        'email_verified' => 'boolean',
        'payment_verified' => 'boolean',
        'car_verified' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
}
