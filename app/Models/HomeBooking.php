<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;


class HomeBooking extends Model implements AuditableContract
{
    use HasFactory;
    use Auditable;
    protected $fillable = [
        'user_id',
        'home_id',
        'booking_type',       // new
        'check_in_date',
        'check_out_date',
        'purchase_date',      // new
        'guests',
        'total_price',
        'status',
    ];

    protected $casts = [
        'check_in_date' => 'datetime',
        'check_out_date' => 'datetime',
        'purchase_date' => 'datetime',
        'total_price' => 'float',
        'guests' => 'integer',
    ];

    // Relationships

    public function user()
    {
        return $this->belongsTo(Users::class);
    }

    public function home()
    {
        return $this->belongsTo(Home::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    // Accessor: number of nights (only for rent)
    public function getNightsAttribute()
    {
        if ($this->booking_type === 'rent' && $this->check_in_date && $this->check_out_date) {
            return $this->check_in_date->diffInDays($this->check_out_date);
        }

        return 0;
    }

    // Scopes for filtering by type
    public function scopeRented($query)
    {
        return $query->where('booking_type', 'rent');
    }

    public function scopePurchased($query)
    {
        return $query->where('booking_type', 'buy');
    }
}
