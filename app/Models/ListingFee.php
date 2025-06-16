<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListingFee extends Model
{
    protected $fillable = [
        'listing_type',
        'item_type',
        'fee',
        'currency',
        'is_active',
        
    ];

    protected $casts = [
    'is_active' => 'boolean',
   
];

}
