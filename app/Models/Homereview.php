<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Homereview extends Model
{
   protected $fillable = ['user_id', 'home_id', 'rating', 'review_text'];

    public function user()
    {
        return $this->belongsTo(Users::class);
    }

    public function home()
    {
        return $this->belongsTo(Home::class);
    }
}
