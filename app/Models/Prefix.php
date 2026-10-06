<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prefix extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];
}
