<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    public function index()
    {
        $classrooms = Classroom::withCount('students')->orderBy('name')->get();

        return response()->json([
            'status' => 'success',
            'data' => $classrooms,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:classrooms,name',
            'level' => 'nullable|string',
            'room' => 'nullable|string',
            'academic_year' => 'nullable|string',
            'semester' => 'nullable|string',
            'advisor_name' => 'nullable|string',
        ]);

        $classroom = Classroom::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'เพิ่มห้องเรียนเรียบร้อยแล้ว',
            'data' => $classroom,
        ], 201);
    }

    public function show($id)
    {
        $classroom = Classroom::with(['students' => function($q) {
            $q->orderBy('student_number');
        }, 'schedules.subject'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $classroom,
        ]);
    }

    public function update(Request $request, $id)
    {
        $classroom = Classroom::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|unique:classrooms,name,' . $id,
            'level' => 'nullable|string',
            'room' => 'nullable|string',
            'academic_year' => 'nullable|string',
            'semester' => 'nullable|string',
            'advisor_name' => 'nullable|string',
        ]);

        $classroom->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'แก้ไขข้อมูลห้องเรียนเรียบร้อยแล้ว',
            'data' => $classroom,
        ]);
    }

    public function destroy($id)
    {
        $classroom = Classroom::findOrFail($id);
        $classroom->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'ลบห้องเรียนเรียบร้อยแล้ว',
        ]);
    }
}
