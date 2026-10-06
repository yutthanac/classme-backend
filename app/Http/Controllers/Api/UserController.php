<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with(['role', 'subjects'])->orderBy('id', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $users,
        ]);
    }

    public function show(string $id)
    {
        $user = User::with(['role', 'subjects'])->findOrFail($id);

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
            'avatar' => 'nullable',
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        } elseif (!empty($validated['avatar']) && is_string($validated['avatar'])) {
            $avatarVal = $validated['avatar'];
            if (preg_match('/^data:image\/(\w+);base64,/', $avatarVal, $matches)) {
                $ext = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);
                $data = substr($avatarVal, strpos($avatarVal, ',') + 1);
                $decoded = base64_decode($data);
                if ($decoded !== false) {
                    $filename = 'avatars/' . Str::random(40) . '.' . $ext;
                    Storage::disk('public')->put($filename, $decoded);
                    $avatarPath = $filename;
                }
            } else {
                $avatarPath = $avatarVal;
            }
        }

        $user = User::create([
            'prefix' => $validated['prefix'] ?? '',
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
            'avatar' => $avatarPath,
        ]);

        $user->roles()->sync([$validated['role_id']]);

        if ($request->has('subject_ids') && is_array($request->input('subject_ids'))) {
            $user->subjects()->sync($request->input('subject_ids'));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'เพิ่มผู้ใช้งานสำเร็จ',
            'data' => $user->load(['role', 'subjects']),
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
            'avatar' => 'nullable',
            'subject_ids' => 'nullable|array',
            'subject_ids.*' => 'exists:subjects,id',
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

        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $updateData['avatar'] = $request->file('avatar')->store('avatars', 'public');
        } elseif ($request->has('avatar')) {
            $avatarVal = $request->input('avatar');
            if (empty($avatarVal) || $avatarVal === 'delete' || $avatarVal === 'remove') {
                if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }
                $updateData['avatar'] = null;
            } elseif (is_string($avatarVal) && preg_match('/^data:image\/(\w+);base64,/', $avatarVal, $matches)) {
                if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }
                $ext = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);
                $data = substr($avatarVal, strpos($avatarVal, ',') + 1);
                $decoded = base64_decode($data);
                if ($decoded !== false) {
                    $filename = 'avatars/' . Str::random(40) . '.' . $ext;
                    Storage::disk('public')->put($filename, $decoded);
                    $updateData['avatar'] = $filename;
                }
            } elseif (is_string($avatarVal)) {
                $updateData['avatar'] = $avatarVal;
            }
        }

        $user->update($updateData);
        $user->roles()->sync([$validated['role_id']]);

        if ($request->has('subject_ids') && is_array($request->input('subject_ids'))) {
            $user->subjects()->sync($request->input('subject_ids'));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'แก้ไขข้อมูลผู้ใช้งานสำเร็จ',
            'data' => $user->load(['role', 'subjects']),
        ]);
    }

    public function uploadAvatar(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'avatar' => 'required|image|max:5120',
        ]);

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return response()->json([
            'status' => 'success',
            'message' => 'อัปโหลดรูปโปรไฟล์เรียบร้อยแล้ว',
            'data' => $user->load('role'),
        ]);
    }

    public function uploadAvatarStandalone(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|max:5120',
        ]);

        $path = $request->file('avatar')->store('avatars', 'public');

        return response()->json([
            'status' => 'success',
            'message' => 'อัปโหลดรูปภาพสำเร็จ',
            'data' => [
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
            ],
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

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'ลบผู้ใช้งานเรียบร้อยแล้ว',
        ]);
    }
}
