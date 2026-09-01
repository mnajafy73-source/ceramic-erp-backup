<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShoulderRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'month',
        'day',
        'name',
        'product_name',
        'carton_count',
        'per_carton',
        'total',
        'shoulder',
    ];
}