<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $studentId = $this->route('student') ?? $this->route('id');

        return [
            'student_code' => 'required|string|unique:students,student_code,' . $studentId,
            'student_number' => 'required|integer',
            'title' => 'required|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'classroom' => 'required|string',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'gender' => 'required|string|in:male,female',
            'guardian_name' => 'nullable|string',
            'guardian_phone' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
        ];
    }

    public function messages(): array
    {
        return [
            'student_code.required' => 'กรุณาระบุรหัสนักเรียน',
            'student_code.unique' => 'รหัสนักเรียนนี้มีอยู่ในระบบแล้ว',
            'student_number.required' => 'กรุณาระบุเลขที่',
            'first_name.required' => 'กรุณาระบุชื่อ',
            'last_name.required' => 'กรุณาระบุนามสกุล',
            'classroom.required' => 'กรุณาระบุห้องเรียน',
            'gender.in' => 'เพศต้องเป็น male หรือ female',
        ];
    }
}
