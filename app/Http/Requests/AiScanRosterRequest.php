<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AiScanRosterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => 'required|file|mimes:jpeg,png,jpg,webp,pdf|max:10240',
            'classroom' => 'nullable|string',
            'api_key' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'กรุณาอัปโหลดรูปภาพหรือไฟล์เอกสารรายชื่อนักเรียน',
            'image.mimes' => 'รองรับเฉพาะไฟล์รูปภาพ (jpeg, png, jpg, webp) หรือ PDF เท่านั้น',
            'image.max' => 'ขนาดไฟล์ต้องไม่เกิน 10MB',
        ];
    }
}
