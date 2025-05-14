<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthCode extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'expires_at'
    ];

    // Relationships

    public function user()
    {
        return $this->belongsTo(Users::class);
    }
}
