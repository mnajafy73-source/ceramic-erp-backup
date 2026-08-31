<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Morilog\Jalali\Jalalian;

class InformalSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'number',
        'date',
        'customer_id',        // ✅ اضافه شد
        'customer_name',
        'total_price',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // تاریخ شمسی
    public function getJalaliDateAttribute()
    {
        if (!$this->date) return null;
        try {
            return Jalalian::fromCarbon($this->date)->format('Y/m/d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function products()
    {
        return $this->hasMany(InformalSaleProduct::class);
    }

    public function getDisplayNumberAttribute()
    {
        return $this->year . '-' . str_pad($this->number, 3, '0', STR_PAD_LEFT);
    }
}