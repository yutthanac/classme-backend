<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_students_and_filter_by_classroom(): void
    {
        $cr = Classroom::create(['name' => 'ม.4/1', 'level' => 'ม.4', 'room' => '1']);

        Student::create([
            'student_code' => '67001',
            'student_number' => 1,
            'title' => 'นาย',
            'first_name' => 'สมชาย',
            'last_name' => 'ใจดี',
            'classroom' => 'ม.4/1',
            'classroom_id' => $cr->id,
            'gender' => 'male',
        ]);

        Student::create([
            'student_code' => '67002',
            'student_number' => 2,
            'title' => 'นางสาว',
            'first_name' => 'สมหญิง',
            'last_name' => 'รักเรียน',
            'classroom' => 'ม.4/2',
            'gender' => 'female',
        ]);

        $response = $this->getJson('/api/students?classroom=ม.4/1');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('67001', $data[0]['student_code']);
    }

    public function test_can_create_student(): void
    {
        $payload = [
            'student_code' => '67003',
            'student_number' => 3,
            'title' => 'นาย',
            'first_name' => 'ทัศนะ',
            'last_name' => 'สุขเกษม',
            'classroom' => 'ม.4/1',
            'gender' => 'male',
            'status' => 'active',
        ];

        $response = $this->postJson('/api/students', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'บันทึกข้อมูลนักเรียนเรียบร้อยแล้ว',
            ]);

        $this->assertDatabaseHas('students', [
            'student_code' => '67003',
            'first_name' => 'ทัศนะ',
        ]);
    }

    public function test_can_show_student_profile_with_stats(): void
    {
        $student = Student::create([
            'student_code' => '67004',
            'student_number' => 4,
            'title' => 'นาย',
            'first_name' => 'กิตติ',
            'last_name' => 'เจริญ',
            'classroom' => 'ม.4/1',
            'gender' => 'male',
        ]);

        $response = $this->getJson("/api/students/{$student->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'student',
                    'stats' => ['total_sessions', 'present', 'late', 'leave', 'absent', 'attendance_rate'],
                    'history',
                ],
            ]);
    }
}
