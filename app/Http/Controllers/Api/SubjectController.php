<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::withCount(['schedules', 'sessions'])->orderBy('code')->get();

        return response()->json([
            'status' => 'success',
            'data' => $subjects,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:subjects,code',
            'name' => 'required|string',
            'teacher_name' => 'required|string',
            'credit' => 'required|numeric',
            'color' => 'nullable|string',
        ]);

        $subject = Subject::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'เพิ่มรายวิชาเรียบร้อยแล้ว',
            'data' => $subject,
        ], 201);
    }

    public function show($id)
    {
        $subject = Subject::with(['schedules', 'sessions' => function($q) {
            $q->orderByDesc('date');
        }])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $subject,
        ]);
    }

    public function update(Request $request, $id)
    {
        $subject = Subject::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|unique:subjects,code,' . $id,
            'name' => 'required|string',
            'teacher_name' => 'required|string',
            'credit' => 'required|numeric',
            'color' => 'nullable|string',
        ]);

        $subject->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'แก้ไขข้อมูลรายวิชาเรียบร้อยแล้ว',
            'data' => $subject,
        ]);
    }

    public function destroy($id)
    {
        $subject = Subject::findOrFail($id);
        $subject->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'ลบรายวิชาเรียบร้อยแล้ว',
        ]);
    }
}
