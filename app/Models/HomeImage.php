<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HomeImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'home_id',
        'image_path',
    ];

    protected $appends = ['image_url'];

    public function home()
    {
        return $this->belongsTo(Home::class);
    }

    public function getImageUrlAttribute()
    {
        return url($this->image_path);
    }
}
