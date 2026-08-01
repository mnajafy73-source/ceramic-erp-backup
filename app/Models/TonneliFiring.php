<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Morilog\Jalali\Jalalian;
use App\Traits\HasUndo;

class TonneliFiring extends Model
{
    use HasFactory, HasUndo;

    protected $fillable = [
        'date',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(TonneliFiringItem::class);
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