<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUndo;
use Morilog\Jalali\Jalalian;

class Production extends Model
{
    use HasFactory, HasUndo;

    protected $fillable = [
        'date',
        'operator_id',
        'press_id',
        'product_id',
        'product_weight',
        'stage',
        'quantity',
        'time_hours',
        'notes',
    ];

    private static $stageMap = [
        'تولید' => 'production',
        'پرداخت' => 'payment',
        'بسته‌بندی' => 'packaging',
    ];

    public function setStageAttribute($value)
    {
        $this->attributes['stage'] = self::$stageMap[$value] ?? $value;
    }

    public function getStageAttribute($value)
    {
        $reverseMap = array_flip(self::$stageMap);
        return $reverseMap[$value] ?? $value;
    }

    public function getJalaliDateAttribute()
    {
        return $this->date;
    }

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