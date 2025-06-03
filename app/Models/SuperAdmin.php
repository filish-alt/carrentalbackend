<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class SuperAdmin extends Model implements Authenticatable, AuthorizableContract
{
    use HasApiTokens, HasFactory, HasRoles, AuthenticatableTrait, Authorizable;

    protected $table = 'super_admins';

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

    public function getAuthPassword()
    {
        return $this->hash_password;
    }
}
