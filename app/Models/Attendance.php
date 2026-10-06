<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_session_id',
        'student_id',
        'status', // present, late, leave, absent
        'remark',
    ];

    public function session()
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
