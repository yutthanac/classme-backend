<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AiSheetUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_path',
        'file_name',
        'classroom',
        'subject_id',
        'date',
        'parsed_data',
        'status',
    ];

    protected $casts = [
        'parsed_data' => 'array',
    ];

    protected $appends = ['file_url'];

    public function getFileUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
