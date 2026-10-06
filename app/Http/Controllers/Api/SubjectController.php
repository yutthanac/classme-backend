<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::with(['schedules', 'teachers', 'teacher'])->withCount(['schedules', 'sessions'])->orderBy('code')->get();

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
            'teacher_name' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
            'credit' => 'required|numeric',
            'color' => 'nullable|string',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:users,id',
        ]);

        if (empty($validated['teacher_name']) && !empty($validated['user_id'])) {
            $user = \App\Models\User::find($validated['user_id']);
            if ($user) {
                $validated['teacher_name'] = $user->display_name_with_prefix;
            }
        }

        if (empty($validated['teacher_name'])) {
            $validated['teacher_name'] = 'ไม่ระบุอาจารย์ผู้สอน';
        }

        $teacherIds = $validated['teacher_ids'] ?? [];
        if (!empty($validated['user_id']) && !in_array($validated['user_id'], $teacherIds)) {
            $teacherIds[] = $validated['user_id'];
        }
        unset($validated['teacher_ids']);

        $subject = Subject::create($validated);

        if (!empty($teacherIds)) {
            $subject->teachers()->sync($teacherIds);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'เพิ่มรายวิชาเรียบร้อยแล้ว',
            'data' => $subject->load(['teachers', 'teacher']),
        ], 201);
    }

    public function show($id)
    {
        $subject = Subject::with(['schedules', 'teachers', 'teacher', 'sessions' => function($q) {
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
            'teacher_name' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
            'credit' => 'required|numeric',
            'color' => 'nullable|string',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:users,id',
        ]);

        if (empty($validated['teacher_name']) && !empty($validated['user_id'])) {
            $user = \App\Models\User::find($validated['user_id']);
            if ($user) {
                $validated['teacher_name'] = $user->display_name_with_prefix;
            }
        }

        $teacherIds = $validated['teacher_ids'] ?? null;
        if (!empty($validated['user_id']) && is_array($teacherIds) && !in_array($validated['user_id'], $teacherIds)) {
            $teacherIds[] = $validated['user_id'];
        }
        unset($validated['teacher_ids']);

        $subject->update($validated);

        if (is_array($teacherIds)) {
            $subject->teachers()->sync($teacherIds);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'แก้ไขข้อมูลรายวิชาเรียบร้อยแล้ว',
            'data' => $subject->load(['teachers', 'teacher']),
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
