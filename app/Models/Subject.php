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
        'user_id',
        'credit',
        'color',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function teachers()
    {
        return $this->belongsToMany(User::class, 'subject_user')->withTimestamps();
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function sessions()
    {
        return $this->hasMany(AttendanceSession::class);
    }
}
