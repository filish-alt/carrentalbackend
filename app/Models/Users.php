<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;

class Users extends Authenticatable
{
    use HasApiTokens, Notifiable;
   /**
 * The attributes that are mass assignable.
 *
 * @var array<int, string>
 */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'hash_password',
        'phone_number',
        'address',
        'city',
        'birth_date',
        'sso_id',
        'digtal_id',
        'license'

    ];

       /**
     * The attributes that should be hidden for arrays.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

       /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
   
     /**
     * Get the URL for the scanning ID file.
     *
     * @return string|null
     */
    public function getlicesncseUrlAttribute()
    {
        return $this->licesncse_id ? Storage::url($this->license) : null;
    }

    /**
     * Get the URL for the scanning ID file.
     *
     * @return string|null
     */
    public function getDigtalIdUrlAttribute()
    {
        return $this->digtal_id ? Storage::url($this->digtal_id) : null;
    }
}
