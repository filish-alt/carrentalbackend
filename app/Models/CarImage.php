<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Support\Facades\Storage;

class CarImage extends Model
{

use HasFactory;

protected $fillable = [
    'car_id',
    'image_path',
 ];

 protected $appends = ['image_url'];

 public function car() 
  {
    return $this->belongsTo(Car::class);
  }

  public function getImageUrlAttribute()
  {
      return Storage::url($this->image_path);
  }

}
