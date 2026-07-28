<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'number',
        'date',
        'customer_name',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function products()
    {
        return $this->hasMany(InvoiceProduct::class);
    }

    public function getDisplayNumberAttribute()
    {
        return $this->year . '-' . $this->number;
    }
}