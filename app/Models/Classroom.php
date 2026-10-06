<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'level',
        'room',
        'academic_year',
        'semester',
        'advisor_name',
    ];

    public function students()
    {
        return $this->hasMany(Student::class, 'classroom_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'classroom', 'name');
    }
}
