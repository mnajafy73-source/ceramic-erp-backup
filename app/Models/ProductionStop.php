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

    // ========== تبدیل type به فارسی/انگلیسی ==========
    private static $stopTypeMap = [
        'خرابی ماشین' => 'machine_failure',
        'تعویض قالب'  => 'mold_change_repair',
    ];

    // Mutator: قبل از ذخیره، فارسی را به انگلیسی تبدیل کن
    public function setTypeAttribute($value)
    {
        $this->attributes['type'] = self::$stopTypeMap[$value] ?? $value;
    }

    // Accessor: هنگام خواندن، انگلیسی را به فارسی تبدیل کن
    public function getTypeAttribute($value)
    {
        $reverseMap = array_flip(self::$stopTypeMap);
        return $reverseMap[$value] ?? $value;
    }

    public function production()
    {
        return $this->belongsTo(Production::class);
    }
}