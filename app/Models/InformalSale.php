<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InformalSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'number',
        'date',
        'customer_name',
        'total_price',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function products()
    {
        return $this->hasMany(InformalSaleProduct::class);
    }

    public function getDisplayNumberAttribute()
    {
        return $this->year . '-' . $this->number;
    }
}