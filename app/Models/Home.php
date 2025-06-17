<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Home extends Model
{
    use HasFactory; 

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
        'listing_type',
        'amenities',
        'check_in_time',
        'check_out_time',

        // Newly added fields
        'furnished',
        'area_sqm',
        'seating_capacity',
        'parking',
        'storage',
        'loading_zone',
        'payment_frequency',
        'power_supply',
        'kitchen',
        'property_purposes',
    ];

    protected $casts = [
        'amenities' => 'array',
        'property_purposes' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'price_per_night' => 'float',
        'rent_per_month' => 'float',
        'sell_price' => 'float',
        'area_sqm' => 'float',
    ];

    public function images()
    {
        return $this->hasMany(HomeImage::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function platformPayments()
    {
        return $this->morphMany(Platformpayment::class, 'item');
    }
}