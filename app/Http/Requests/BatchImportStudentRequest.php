<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BatchImportStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'classroom' => 'required|string',
            'students' => 'required|array|min:1',
            'students.*.student_number' => 'required|integer',
            'students.*.student_code' => 'required|string',
            'students.*.title' => 'nullable|string',
            'students.*.first_name' => 'required|string',
            'students.*.last_name' => 'required|string',
            'students.*.gender' => 'nullable|string|in:male,female',
            'students.*.guardian_name' => 'nullable|string',
            'students.*.guardian_phone' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'classroom.required' => 'กรุณาระบุห้องเรียนสำหรับการนำเข้า',
            'students.required' => 'กรุณาระบุข้อมูลนักเรียน',
            'students.min' => 'ต้องมีข้อมูลนักเรียนอย่างน้อย 1 คน',
            'students.*.student_code.required' => 'พบรายการที่ไม่ได้ระบุรหัสนักเรียน',
            'students.*.first_name.required' => 'พบรายการที่ไม่ได้ระบุชื่อ',
            'students.*.last_name.required' => 'พบรายการที่ไม่ได้ระบุนามสกุล',
        ];
    }
}
