<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpeningInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity',
        'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}