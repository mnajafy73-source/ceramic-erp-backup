<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShuttleFiring extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'kiln_type',
        'firing_number',
        'firing_subtype',
        'product_id',
        'output_quantity',
        'is_packaged',
    ];

    protected $casts = [
        'date' => 'date',
        'is_packaged' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}