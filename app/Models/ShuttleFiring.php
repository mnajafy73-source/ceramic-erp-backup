<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUndo;
use Morilog\Jalali\Jalalian;

class ShuttleFiring extends Model
{
    use HasFactory, HasUndo;   // HasUndo اضافه شد

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

    public function getJalaliDateAttribute()
    {
        if (!$this->date) {
            return null;
        }

        try {
            return Jalalian::fromCarbon($this->date)->format('Y/m/d');
        } catch (\Exception $e) {
            return null;
        }
    }
}