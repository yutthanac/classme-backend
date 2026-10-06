<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AiSheetController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PrefixController;
use App\Http\Controllers\Api\AuthController;

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'app' => 'ClassMe API']);
});

// Authentication
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

// Students
Route::apiResource('students', StudentController::class);

// Classrooms
Route::apiResource('classrooms', ClassroomController::class);

// Subjects
Route::apiResource('subjects', SubjectController::class);

// Schedules
Route::get('schedules', [ScheduleController::class, 'index']);
Route::post('schedules', [ScheduleController::class, 'store']);
Route::delete('schedules/{id}', [ScheduleController::class, 'destroy']);

// Attendance
Route::prefix('attendance')->group(function () {
    Route::get('/sessions', [AttendanceController::class, 'index']);
    Route::post('/check', [AttendanceController::class, 'store']);
    Route::get('/sessions/{id}', [AttendanceController::class, 'show']);
    Route::get('/stats', [AttendanceController::class, 'stats']);
    Route::get('/export-excel', [AttendanceController::class, 'exportExcel']);
    Route::get('/subject/{id}', [AttendanceController::class, 'subjectHistory']);
});

// AI Sheet Reader (Vision OCR)
Route::prefix('ai')->group(function () {
    Route::post('/upload-sheet', [AiSheetController::class, 'uploadAndAnalyze']);
    Route::post('/confirm', [AiSheetController::class, 'confirm']);
    Route::get('/history', [AiSheetController::class, 'history']);
});

// Alerts
Route::prefix('alerts')->group(function () {
    Route::get('/', [AlertController::class, 'index']);
    Route::post('/{id}/read', [AlertController::class, 'markAsRead']);
    Route::post('/scan', [AlertController::class, 'scanAlerts']);
});

// Prefixes
Route::get('prefixes', [PrefixController::class, 'index']);
Route::post('prefixes', [PrefixController::class, 'store']);
Route::delete('prefixes/{id}', [PrefixController::class, 'destroy']);

// Roles & Permissions
Route::prefix('roles')->group(function () {
    Route::get('/', [RoleController::class, 'index']);
    Route::get('/permissions', [RoleController::class, 'permissions']);
    Route::post('/{id}/permissions', [RoleController::class, 'updateRolePermissions']);
});
Route::get('/user/profile', [RoleController::class, 'currentUser']);
Route::post('/user/profile', [RoleController::class, 'updateProfile']);
