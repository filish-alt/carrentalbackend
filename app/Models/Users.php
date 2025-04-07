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
    'phone',
    'hash_password',
    'digital_id',
    'profile_picture',
    'driver_liscence',
    'role',
    'status',
    'address',            
    'city',
    'birth_date',      
    'otp',
    'otp_expires_at',
    'sso_id',
];

protected $casts = [
    'profile_picture' => 'array',
    'birth_date' => 'date',
    'otp_expires_at' => 'datetime',
];
  /**
     * The attributes that should be hidden for arrays.
     *
     
     */
protected $hidden = [
    'hash_password',
    'otp',
];

     /**
     * Get the URL for the driver's license file.
     *
     * @return string|null
     */
    public function getDriverLicenceUrlAttribute()
    {
        return $this->driver_liscence ? Storage::url($this->driver_liscence) : null;
    }

  /**
     * Get the URL for the digital ID file.
     *
     * @return string|null
     */
    public function getDigitalIdUrlAttribute()
    {
        return $this->digital_id ? Storage::url($this->digital_id) : null;
    }
}
