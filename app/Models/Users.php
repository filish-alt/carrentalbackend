<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;

class Users extends Authenticatable
{ 
    use HasApiTokens, Notifiable;
    use SoftDeletes;
 protected $guard_name = 'web'; 
   /**
 * The attributes that are mass assignable.
 *
 * @var array<int, string>
 */
protected $fillable = [
    'first_name',
    'middle_name',
    'last_name',
    'email',
    'phone',
    'hash_password',
    'digital_id',
    'passport',
    'profile_picture',
    'driver_licence',
    'role',
    'status',
    'address',
    'city',
    'birth_date',
    'otp',
    'otp_expires_at',
    'sso_id',
    'two_factor_enabled',
    'email_verification_token',
    'email_verified_at'
];

protected $casts = [
    'profile_picture' => 'array',
    'birth_date' => 'date',
    'otp_expires_at' => 'datetime',
    'notification_preferences' => 'array',
    'email_verified_at' => 'datetime'
];
  /**
     * The attributes that should be hidden for arrays.
     *
     
     */
protected $hidden = [
    'hash_password',
    'otp',
];
protected $dates = ['two_factor_expires_at'];


     /**
     * Get the URL for the driver's license file.
     *
     * @return string|null
     */
    public function getDriverLicenceUrlAttribute()
    {
       // return $this->driver_liscence ? Storage::url($this->driver_liscence) : null;
       return url( $this->driver_liscence);
    }

     /**
     * Get the URL for the driver's license file.
     *
     * @return string|null
     */
    public function getProfilePictureUrlAttribute()
    {
       // return $this->driver_liscence ? Storage::url($this->driver_liscence) : null;
       return url( $this->profile_picture);
    }

  /**
     * Get the URL for the digital ID file.
     *
     * @return string|null
     */
    public function getDigitalIdUrlAttribute()
    {
       // return $this->digital_id ? Storage::url($this->digital_id) : null;
        return url( $this->digital_id);
    }

     /**
     * Get the URL for the digital ID file.
     *
     * @return string|null
     */
  public function getPassportUrlAttribute()
    {
       // return $this->digital_id ? Storage::url($this->digital_id) : null;
        return url( $this->passport);
    }
    
    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class, 'user_id');
    }
    // Notification relationship
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }
    
    public function cars()
    {
        return $this->hasMany(Car::class, 'owner_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'user_id');
    }
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isOwner()
    {
        return $this->cars()->exists();
    }

    public function isRenter()
    {
        return $this->bookings()->exists();
    }



}
