<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Press extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'status'];

    protected $casts = [
        'status' => 'boolean',
    ];

    protected static function booted()
    {
        static::deleting(function ($press) {
            \App\Models\Production::where('press_id', $press->id)->update(['press_id' => null]);
        });
    }
}