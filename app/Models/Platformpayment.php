<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Platformpayment extends Model
{
     use HasFactory;
     protected $table = 'platform_payments';

      protected $fillable = [
        'item_id',
        'amount',
        'payment_status', 
        'payment_method',   
        'transaction_date',
        'tx_ref',           
    ]; 

    protected $casts = [
        'transaction_date' => 'date',
    ];

}
