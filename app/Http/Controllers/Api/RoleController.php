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
        ]);

        if ($user) {
            $user->update($validated);
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
