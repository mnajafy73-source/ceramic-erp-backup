<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialMaking extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'month',
        'day',
        'name',
        'material',
        'quantity',
        'mill_weight',
    ];
}