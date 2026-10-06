<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->withCount('users')->get();

        return response()->json([
            'status' => 'success',
            'data' => $roles,
        ]);
    }

    public function permissions()
    {
        $permissions = Permission::all()->groupBy('group');

        return response()->json([
            'status' => 'success',
            'data' => $permissions,
        ]);
    }

    public function updateRolePermissions(Request $request, string $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $role->permissions()->sync($validated['permission_ids']);

        return response()->json([
            'status' => 'success',
            'message' => 'บันทึกการกำหนดสิทธิ์ของบทบาท ' . $role->display_name . ' สำเร็จ',
            'data' => $role->load('permissions'),
        ]);
    }

    private function resolveUser(Request $request)
    {
        $user = $request->user();
        if (!$user && $token = $request->bearerToken()) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if ($accessToken) {
                $user = $accessToken->tokenable;
            }
        }
        return $user ?: User::first();
    }

    public function currentUser(Request $request)
    {
        $user = $this->resolveUser($request);
        if ($user) {
            $user->load(['role.permissions', 'roles.permissions']);
        }

        return response()->json([
            'status' => 'success',
            'data' => $user,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $this->resolveUser($request);
        $validated = $request->validate([
            'prefix' => 'nullable|string',
            'name' => 'required|string',
            'role_id' => 'nullable|exists:roles,id',
            'avatar' => 'nullable',
        ]);

        if ($user) {
            $updateData = [
                'prefix' => $validated['prefix'] ?? '',
                'name' => $validated['name'],
            ];

            if ($request->hasFile('avatar')) {
                if ($user->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar);
                }
                $updateData['avatar'] = $request->file('avatar')->store('avatars', 'public');
            } elseif ($request->has('avatar')) {
                $avatarVal = $request->input('avatar');
                if (empty($avatarVal) || $avatarVal === 'delete' || $avatarVal === 'remove') {
                    if ($user->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar);
                    }
                    $updateData['avatar'] = null;
                } elseif (is_string($avatarVal) && preg_match('/^data:image\/(\w+);base64,/', $avatarVal, $matches)) {
                    $ext = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);
                    $data = substr($avatarVal, strpos($avatarVal, ',') + 1);
                    $decoded = base64_decode($data);
                    if ($decoded !== false) {
                        $filename = 'avatars/' . \Illuminate\Support\Str::random(40) . '.' . $ext;
                        \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $decoded);
                        $updateData['avatar'] = $filename;
                    }
                } elseif (is_string($avatarVal)) {
                    $updateData['avatar'] = $avatarVal;
                }
            }

            $user->update($updateData);
            if (isset($validated['role_id'])) {
                $user->roles()->sync([$validated['role_id']]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'อัปเดตข้อมูลผู้ใช้และคำนำหน้าเรียบร้อยแล้ว',
            'data' => $user->load('role.permissions'),
        ]);
    }
}
