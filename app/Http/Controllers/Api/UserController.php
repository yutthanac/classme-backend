<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('role')->orderBy('id', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $users,
        ]);
    }

    public function show(string $id)
    {
        $user = User::with('role')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $user,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'prefix' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = User::create([
            'prefix' => $validated['prefix'] ?? '',
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
        ]);

        $user->roles()->sync([$validated['role_id']]);

        return response()->json([
            'status' => 'success',
            'message' => 'เพิ่มผู้ใช้งานสำเร็จ',
            'data' => $user->load('role'),
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'prefix' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role_id' => 'required|exists:roles,id',
            'password' => 'nullable|string|min:6',
        ]);

        $updateData = [
            'prefix' => $validated['prefix'] ?? '',
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);
        $user->roles()->sync([$validated['role_id']]);

        return response()->json([
            'status' => 'success',
            'message' => 'แก้ไขข้อมูลผู้ใช้งานสำเร็จ',
            'data' => $user->load('role'),
        ]);
    }

    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        if ($user->email === 'admin@classme.ac.th') {
            return response()->json([
                'status' => 'error',
                'message' => 'ไม่สามารถลบบัญชีผู้ดูแลระบบหลักได้',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'ลบผู้ใช้งานเรียบร้อยแล้ว',
        ]);
    }
}
