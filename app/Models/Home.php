<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Home extends Model
{
    protected $table = 'homes';

    protected $fillable = [
        'owner_id',
        'title',
        'description',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'latitude',
        'longitude',
        'price_per_night',
        'rent_per_month',
        'sell_price',
        'bedrooms',
        'bathrooms',
        'max_guests',
        'property_type',
        'status',
        'amenities',
        'check_in_time',
        'check_out_time',
        'listing_type', // added field
    ];

    protected $casts = [
        'amenities' => 'array',
        'rent_per_month' => 'float',
        'sell_price' => 'float',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function images()
    {
        return $this->hasMany(HomeImage::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews()
    {
        return $this->hasMany(HomeReview::class);
    }
}
