<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Classroom;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentService
{
    /**
     * Retrieve students filtered by classroom and search query.
     */
    public function getStudents(?string $classroom = null, ?string $search = null): Collection
    {
        $query = Student::with('classroomRelation');

        if (!empty($classroom) && $classroom !== 'all') {
            $query->where('classroom', $classroom);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('student_code', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('classroom')->orderBy('student_number')->get();
    }

    /**
     * Get single student by ID.
     */
    public function getStudentById(int|string $id): Student
    {
        return Student::with(['classroomRelation', 'alerts'])->findOrFail($id);
    }

    /**
     * Get student with comprehensive attendance statistics and history.
     */
    public function getStudentProfileWithStats(int|string $id): array
    {
        $student = $this->getStudentById($id);

        $attendances = Attendance::with(['session.subject'])
            ->where('student_id', $student->id)
            ->get();

        $total = $attendances->count();
        $present = $attendances->where('status', 'present')->count();
        $late = $attendances->where('status', 'late')->count();
        $leave = $attendances->where('status', 'leave')->count();
        $absent = $attendances->where('status', 'absent')->count();

        $attendanceRate = $total > 0 ? round((($present + $late) / $total) * 100, 1) : 100;

        return [
            'student' => $student,
            'stats' => [
                'total_sessions' => $total,
                'present' => $present,
                'late' => $late,
                'leave' => $leave,
                'absent' => $absent,
                'attendance_rate' => $attendanceRate,
            ],
            'history' => $attendances->sortByDesc(fn($a) => $a->session->date ?? '')->values(),
        ];
    }

    /**
     * Create a new student.
     */
    public function createStudent(array $data): Student
    {
        if (empty($data['classroom_id']) && !empty($data['classroom'])) {
            $classroom = Classroom::where('name', $data['classroom'])->first();
            if ($classroom) {
                $data['classroom_id'] = $classroom->id;
            }
        }

        return Student::create($data);
    }

    /**
     * Update an existing student.
     */
    public function updateStudent(Student $student, array $data): Student
    {
        if (empty($data['classroom_id']) && !empty($data['classroom'])) {
            $classroom = Classroom::where('name', $data['classroom'])->first();
            if ($classroom) {
                $data['classroom_id'] = $classroom->id;
            }
        }

        $student->update($data);
        return $student->fresh(['classroomRelation']);
    }

    /**
     * Delete a student.
     */
    public function deleteStudent(Student $student): bool
    {
        return (bool) $student->delete();
    }

    /**
     * Batch import students into a classroom.
     */
    public function batchImport(string $classroomName, array $studentsData): array
    {
        $classroom = Classroom::where('name', $classroomName)->first();
        $classroomId = $classroom?->id;

        $created = 0;
        $updated = 0;
        $results = [];

        return DB::transaction(function () use ($classroomName, $classroomId, $classroom, $studentsData, &$created, &$updated, &$results) {
            foreach ($studentsData as $item) {
                $title = trim($item['title'] ?? 'นาย');
                $gender = $item['gender'] ?? null;

                if (empty($gender)) {
                    if (in_array($title, ['นางสาว', 'ด.ญ.', 'เด็กหญิง', 'นาง'])) {
                        $gender = 'female';
                    } else {
                        $gender = 'male';
                    }
                }

                $student = Student::updateOrCreate(
                    [
                        'student_code' => trim($item['student_code']),
                    ],
                    [
                        'student_number' => (int) $item['student_number'],
                        'title' => $title,
                        'first_name' => trim($item['first_name']),
                        'last_name' => trim($item['last_name']),
                        'classroom' => $classroomName,
                        'classroom_id' => $classroomId,
                        'gender' => $gender,
                        'guardian_name' => $item['guardian_name'] ?? null,
                        'guardian_phone' => $item['guardian_phone'] ?? null,
                        'status' => 'active',
                    ]
                );

                if ($student->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }

                $results[] = $student;
            }

            if ($classroom && Schema::hasColumn('classrooms', 'students_count')) {
                $classroom->update([
                    'students_count' => Student::where('classroom', $classroomName)->count(),
                ]);
            }

            return [
                'total' => count($results),
                'created' => $created,
                'updated' => $updated,
                'students' => $results,
            ];
        });
    }
}
