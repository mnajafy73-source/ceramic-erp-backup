<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionStop extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_id',
        'type',
        'hours',
    ];

    public function production()
    {
        return $this->belongsTo(Production::class);
    }
}