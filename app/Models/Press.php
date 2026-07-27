<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUndo;

class Press extends Model
{
    use HasFactory, HasUndo;

    protected $fillable = ['name', 'status'];
    protected $casts = ['status' => 'boolean'];
}