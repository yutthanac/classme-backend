<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'teacher_name',
        'credit',
        'color',
    ];

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function sessions()
    {
        return $this->hasMany(AttendanceSession::class);
    }
}
