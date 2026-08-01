<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity',
        'box',
        'pallet',
        'layer',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'box' => 'integer',
        'pallet' => 'integer',
        'layer' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}