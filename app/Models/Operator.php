<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operator extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'status'];

    protected $casts = [
        'status' => 'boolean',
    ];

    protected static function booted()
    {
        static::deleting(function ($operator) {
            \App\Models\Production::where('operator_id', $operator->id)->update(['operator_id' => null]);
        });
    }
}