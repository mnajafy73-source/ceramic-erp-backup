<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Production extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'operator_id',
        'press_id',
        'product_id',
        'stage',
        'quantity',
        'time_hours',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function press()
    {
        return $this->belongsTo(Press::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stops()
    {
        return $this->hasMany(ProductionStop::class);
    }
}