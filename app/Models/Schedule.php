<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'classroom',
        'day_of_week',
        'start_time',
        'end_time',
        'room_number',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
