<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::with(['role.permissions', 'roles.permissions'])
            ->where('email', $request->email)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง',
            ], 401);
        }

        // Create Sanctum personal access token
        $token = $user->createToken('classme_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'เข้าสู่ระบบสำเร็จ',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }

    public function me(Request $request)
    {
        $token = $request->bearerToken();
        if ($token) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if ($accessToken) {
                $user = $accessToken->tokenable;
                if ($user) {
                    $user->load(['role.permissions', 'roles.permissions']);
                    return response()->json([
                        'status' => 'success',
                        'data' => $user,
                    ]);
                }
            }
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Unauthenticated',
        ], 401);
    }

    public function logout(Request $request)
    {
        $token = $request->bearerToken();
        if ($token) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if ($accessToken) {
                $accessToken->delete();
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'ออกจากระบบเรียบร้อยแล้ว',
        ]);
    }
}
