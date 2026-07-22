<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Morilog\Jalali\Jalalian;

class TonneliFiring extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'product_id',
        'input_quantity',
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

    // Accessor ایمن برای تاریخ شمسی
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