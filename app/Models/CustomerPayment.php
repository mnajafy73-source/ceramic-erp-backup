<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Morilog\Jalali\Jalalian;

class CustomerPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'customer_name',
        'amount',
        'payment_date',
        'year',
        'month',
        'day',
        'payment_method',
        'description',
        'is_imported',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'date',
        'is_imported'  => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function getJalaliDateAttribute(): ?string
    {
        if (!$this->payment_date) return null;
        try {
            return Jalalian::fromCarbon($this->payment_date)->format('Y/m/d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getUserNameAttribute(): string
    {
        return $this->customer?->name ?? $this->customer_name;
    }

    public static function getTotalPaidForCustomer($customerName, $upToDate = null)
    {
        $query = self::where(function ($q) use ($customerName) {
            $q->where('customer_name', $customerName)
              ->orWhereHas('customer', function ($sub) use ($customerName) {
                  $sub->where('name', $customerName);
              });
        });

        if ($upToDate) {
            $query->where(function ($q) use ($upToDate) {
                $q->where('payment_date', '<=', $upToDate)
                  ->orWhereNull('payment_date');
            });
        }

        return (float) $query->sum('amount');
    }
}