<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    public function index()
    {
        $classrooms = Classroom::withCount('students')->with('schedules.subject')->orderBy('name')->get();

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
            'subject_ids' => 'nullable|array',
            'subject_ids.*' => 'exists:subjects,id',
        ]);

        if (empty($validated['room'])) {
            if (preg_match('/\/(\d+)/', $validated['name'], $m)) {
                $validated['room'] = $m[1];
            } else {
                $validated['room'] = '1';
            }
        }

        $subjectIds = $validated['subject_ids'] ?? [];
        unset($validated['subject_ids']);

        $classroom = Classroom::create($validated);

        if (!empty($subjectIds)) {
            foreach ($subjectIds as $subjId) {
                \App\Models\Schedule::firstOrCreate([
                    'classroom' => $classroom->name,
                    'subject_id' => $subjId,
                ], [
                    'day_of_week' => 1,
                    'start_time' => '08:30',
                    'end_time' => '10:10',
                    'room_number' => 'ห้อง ' . $classroom->name,
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'เพิ่มห้องเรียนเรียบร้อยแล้ว',
            'data' => $classroom->load('schedules.subject'),
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
            'subject_ids' => 'nullable|array',
            'subject_ids.*' => 'exists:subjects,id',
        ]);

        if (empty($validated['room'])) {
            if (preg_match('/\/(\d+)/', $validated['name'], $m)) {
                $validated['room'] = $m[1];
            } else {
                $validated['room'] = '1';
            }
        }

        $subjectIds = $validated['subject_ids'] ?? null;
        unset($validated['subject_ids']);

        $classroom->update($validated);

        if ($subjectIds !== null) {
            // Sync schedules for these subjects in this room
            \App\Models\Schedule::where('classroom', $classroom->name)->delete();
            foreach ($subjectIds as $subjId) {
                \App\Models\Schedule::create([
                    'classroom' => $classroom->name,
                    'subject_id' => $subjId,
                    'day_of_week' => 1,
                    'start_time' => '08:30',
                    'end_time' => '10:10',
                    'room_number' => 'ห้อง ' . $classroom->name,
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'แก้ไขข้อมูลห้องเรียนเรียบร้อยแล้ว',
            'data' => $classroom->load('schedules.subject'),
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
