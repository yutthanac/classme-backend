<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiScanRosterRequest;
use App\Http\Requests\BatchImportStudentRequest;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Services\GeminiRosterService;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct(
        protected StudentService $studentService,
        protected GeminiRosterService $geminiRosterService
    ) {}

    /**
     * List all students with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $students = $this->studentService->getStudents(
            $request->input('classroom'),
            $request->input('search')
        );

        return response()->json([
            'status' => 'success',
            'data' => $students,
        ]);
    }

    /**
     * Create a new student record.
     */
    public function store(StoreStudentRequest $request): JsonResponse
    {
        $student = $this->studentService->createStudent($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'บันทึกข้อมูลนักเรียนเรียบร้อยแล้ว',
            'data' => $student,
        ], 201);
    }

    /**
     * Display student details and attendance statistics.
     */
    public function show(int|string $id): JsonResponse
    {
        $profile = $this->studentService->getStudentProfileWithStats($id);

        return response()->json([
            'status' => 'success',
            'data' => $profile,
        ]);
    }

    /**
     * Update an existing student record.
     */
    public function update(UpdateStudentRequest $request, int|string $id): JsonResponse
    {
        $student = $this->studentService->getStudentById($id);
        $updated = $this->studentService->updateStudent($student, $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'แก้ไขข้อมูลนักเรียนเรียบร้อยแล้ว',
            'data' => $updated,
        ]);
    }

    /**
     * Delete a student record.
     */
    public function destroy(int|string $id): JsonResponse
    {
        $student = $this->studentService->getStudentById($id);
        $this->studentService->deleteStudent($student);

        return response()->json([
            'status' => 'success',
            'message' => 'ลบข้อมูลนักเรียนเรียบร้อยแล้ว',
        ]);
    }

    /**
     * Batch import student roster for a classroom.
     */
    public function batchImport(BatchImportStudentRequest $request): JsonResponse
    {
        try {
            $result = $this->studentService->batchImport(
                $request->input('classroom'),
                $request->input('students')
            );

            return response()->json([
                'status' => 'success',
                'message' => "นำเข้าข้อมูลสำเร็จ (สร้างใหม่ {$result['created']} คน, อัปเดต {$result['updated']} คน)",
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการนำเข้า: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Scan roster document image using AI OCR.
     */
    public function aiScanRoster(AiScanRosterRequest $request): JsonResponse
    {
        $result = $this->geminiRosterService->scanRosterImage($request->file('image'), $request->input('api_key'));

        return response()->json([
            'status' => 'success',
            'message' => 'AI สแกนและตรวจพบรายชื่อนักเรียนเรียบร้อยแล้ว',
            'data' => $result,
        ]);
    }
}
