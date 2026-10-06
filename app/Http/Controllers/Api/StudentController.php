<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\Attendance;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with('classroomRelation');

        if ($request->filled('classroom')) {
            $query->where('classroom', $request->classroom);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('student_code', 'like', "%{$s}%")
                  ->orWhere('first_name', 'like', "%{$s}%")
                  ->orWhere('last_name', 'like', "%{$s}%");
            });
        }

        $students = $query->orderBy('classroom')->orderBy('student_number')->get();

        return response()->json([
            'status' => 'success',
            'data' => $students,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_code' => 'required|string|unique:students,student_code',
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
        ]);

        if (empty($validated['classroom_id'])) {
            $cr = Classroom::where('name', $validated['classroom'])->first();
            if ($cr) $validated['classroom_id'] = $cr->id;
        }

        $student = Student::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'บันทึกข้อมูลนักเรียนเรียบร้อยแล้ว',
            'data' => $student,
        ], 201);
    }

    public function show($id)
    {
        $student = Student::with(['classroomRelation', 'alerts'])->findOrFail($id);

        // Fetch attendance stats for this student
        $attendances = Attendance::with(['session.subject'])
            ->where('student_id', $student->id)
            ->get();

        $total = $attendances->count();
        $present = $attendances->where('status', 'present')->count();
        $late = $attendances->where('status', 'late')->count();
        $leave = $attendances->where('status', 'leave')->count();
        $absent = $attendances->where('status', 'absent')->count();

        $attendanceRate = $total > 0 ? round((($present + $late) / $total) * 100, 1) : 100;

        return response()->json([
            'status' => 'success',
            'data' => [
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
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'student_code' => 'required|string|unique:students,student_code,' . $id,
            'student_number' => 'required|integer',
            'title' => 'required|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'classroom' => 'required|string',
            'gender' => 'required|string|in:male,female',
            'guardian_name' => 'nullable|string',
            'guardian_phone' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $student->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'แก้ไขข้อมูลนักเรียนเรียบร้อยแล้ว',
            'data' => $student,
        ]);
    }

    public function destroy($id)
    {
        $student = Student::findOrFail($id);
        $student->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'ลบข้อมูลนักเรียนเรียบร้อยแล้ว',
        ]);
    }
}
