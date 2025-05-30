<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class SuperAdmin extends Model implements Authenticatable
{
     use HasApiTokens;
    use HasFactory, AuthenticatableTrait;

    protected $table = 'super_admins'; // Ensure correct table name

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'hash_password',
        'role',
        'status',
        'email_verified_at',
    ];

    protected $hidden = [
        'hash_password',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Override default password field for Auth
    public function getAuthPassword()
    {
        return $this->hash_password;
    }
}
