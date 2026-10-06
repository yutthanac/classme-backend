<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_code',
        'student_number',
        'title',
        'first_name',
        'last_name',
        'classroom',
        'classroom_id',
        'gender',
        'guardian_name',
        'guardian_phone',
        'status',
        'avatar',
    ];

    protected $appends = ['full_name'];

    public function getFullNameAttribute(): string
    {
        return "{$this->title}{$this->first_name} {$this->last_name}";
    }

    public function classroomRelation()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function alerts()
    {
        return $this->hasMany(AttendanceAlert::class);
    }
}
