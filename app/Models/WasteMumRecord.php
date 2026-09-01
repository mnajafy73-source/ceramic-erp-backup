<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WasteMumRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'month',
        'day',
        'product_name',
        'amount',
    ];
}