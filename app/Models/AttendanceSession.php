<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'classroom',
        'date',
        'period',
        'topic',
        'total_students',
        'present_count',
        'late_count',
        'leave_count',
        'absent_count',
        'notes',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
