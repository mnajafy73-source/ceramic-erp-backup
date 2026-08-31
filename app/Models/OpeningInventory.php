<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

class OpeningInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
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
            if ($this->date instanceof Carbon) {
                return Jalalian::fromCarbon($this->date)->format('Y/m/d');
            }

            if (is_string($this->date)) {
                $carbon = Carbon::parse($this->date);
                return Jalalian::fromCarbon($carbon)->format('Y/m/d');
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}