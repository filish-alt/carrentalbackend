<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'user_id',
        'payment_method',
        'details',
    ];

    protected $casts = [
        'details' => 'array',  // if storing as JSON
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
}
