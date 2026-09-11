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

    // ✅ cast حذف شد (چون تاریخ شمسی ذخیره می‌شه، نه میلادی)

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * تبدیل تاریخ ذخیره‌شده به فرمت شمسی Y/m/d
     * این متد هم تاریخ شمسی و هم میلادی رو پشتیبانی می‌کنه
     */
    public function getJalaliDateAttribute()
    {
        if (!$this->date) {
            return null;
        }

        // تبدیل به رشته
        if ($this->date instanceof Carbon) {
            $dateStr = $this->date->format('Y-m-d');
        } else {
            $dateStr = (string) $this->date;
        }

        // حذف بخش ساعت (T... و Z)
        $dateStr = preg_replace('/[T\s].*$/', '', $dateStr);

        // استخراج سال/ماه/روز
        if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $dateStr, $m)) {
            $year  = (int) $m[1];
            $month = (int) $m[2];
            $day   = (int) $m[3];

            // ✅ اگه سال بین ۱۳۰۰ و ۱۵۰۰ باشه، خودش شمسیه
            if ($year >= 1300 && $year <= 1500) {
                return sprintf('%04d/%02d/%02d', $year, $month, $day);
            }

            // وگرنه میلادیه، تبدیل به شمسی کن
            try {
                $carbon = Carbon::create($year, $month, $day);
                return Jalalian::fromCarbon($carbon)->format('Y/m/d');
            } catch (\Exception $e) {
                return null;
            }
        }

        // fallback
        try {
            $carbon = Carbon::parse($dateStr);
            return Jalalian::fromCarbon($carbon)->format('Y/m/d');
        } catch (\Exception $e) {
            return null;
        }
    }
}