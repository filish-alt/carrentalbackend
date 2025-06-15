<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
   use HasFactory;

    protected $fillable = [
        'booking_id',
        'amount',
        'payment_status', 
        'payment_method',   
        'transaction_date',
        'tx_ref',           
    ]; 

    protected $casts = [
        'transaction_date' => 'date',
    ];

     /**
     * Relationship: Payment belongs to a Booking
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function homeBooking()
    {
        return $this->belongsTo(HomeBooking::class);
    }

}

