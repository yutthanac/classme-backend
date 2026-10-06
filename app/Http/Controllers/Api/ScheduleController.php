<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = Schedule::with('subject');

        if ($request->filled('classroom')) {
            $query->where('classroom', $request->classroom);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        $schedules = $query->orderBy('day_of_week')->orderBy('start_time')->get();

        return response()->json([
            'status' => 'success',
            'data' => $schedules,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'classroom' => 'required|string',
            'day_of_week' => 'required|integer|between:1,7',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'room_number' => 'nullable|string',
        ]);

        $schedule = Schedule::create($validated);
        $schedule->load('subject');

        return response()->json([
            'status' => 'success',
            'message' => 'เพิ่มตารางเรียนเรียบร้อยแล้ว',
            'data' => $schedule,
        ], 201);
    }

    public function destroy($id)
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'ลบตารางเรียนเรียบร้อยแล้ว',
        ]);
    }
}
