<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prefix;
use Illuminate\Http\Request;

class PrefixController extends Controller
{
    public function index(Request $request)
    {
        $query = Prefix::query();

        if ($request->has('type') && in_array($request->type, ['student', 'staff'])) {
            $query->whereIn('type', [$request->type, 'all']);
        }

        $prefixes = $query->orderBy('is_system', 'desc')->orderBy('id', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $prefixes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:prefixes,name',
            'type' => 'required|in:student,staff,all',
        ]);

        $prefix = Prefix::create([
            'name' => trim($validated['name']),
            'type' => $validated['type'],
            'is_system' => false,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'เพิ่มคำนำหน้าชื่อเรียบร้อยแล้ว',
            'data' => $prefix,
        ], 201);
    }

    public function destroy(string $id)
    {
        $prefix = Prefix::findOrFail($id);

        if ($prefix->is_system) {
            return response()->json([
                'status' => 'error',
                'message' => 'ไม่สามารถลบคำนำหน้าเริ่มต้นของระบบได้',
            ], 422);
        }

        $prefix->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'ลบคำนำหน้าชื่อเรียบร้อยแล้ว',
        ]);
    }
}
