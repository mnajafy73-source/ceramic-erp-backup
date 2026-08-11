<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TonneliFiringItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'tonneli_firing_id',
        'product_id',
        'input_quantity',
        'output_quantity',
        'is_packaged',
    ];

    protected $casts = [
        'is_packaged' => 'boolean',
    ];

    public function firing()
    {
        return $this->belongsTo(TonneliFiring::class, 'tonneli_firing_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}