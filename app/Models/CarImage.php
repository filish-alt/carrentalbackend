<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CarImage extends Model
{

use HasFactory;

protected $fillable = [
    'car_id',
    'image_path',
 ];



 public function car() 
  {
    return $this->belongsTo(Car::class);
  }

}
