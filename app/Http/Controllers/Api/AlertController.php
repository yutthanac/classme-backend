<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceAlert;
use App\Models\Student;
use App\Models\Attendance;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index()
    {
        $alerts = AttendanceAlert::with(['student.classroomRelation', 'subject'])
            ->orderBy('is_read')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $alerts,
        ]);
    }

    public function markAsRead($id)
    {
        $alert = AttendanceAlert::findOrFail($id);
        $alert->update(['is_read' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'ทำเครื่องหมายรับทราบแล้ว',
            'data' => $alert,
        ]);
    }

    public function scanAlerts()
    {
        // Scan all students and generate any missing alerts
        $students = Student::where('status', 'active')->get();
        $generated = 0;

        foreach ($students as $student) {
            $absentCount = Attendance::where('student_id', $student->id)->where('status', 'absent')->count();
            if ($absentCount >= 3) {
                $exists = AttendanceAlert::where('student_id', $student->id)
                    ->where('alert_type', 'risk_drop')
                    ->exists();

                if (!$exists) {
                    AttendanceAlert::create([
                        'student_id' => $student->id,
                        'alert_type' => 'risk_drop',
                        'title' => "ขาดเรียนสะสม {$absentCount} ครั้ง เสี่ยงหมดสิทธิ์สอบ",
                        'message' => "นักเรียน {$student->full_name} ขาดเรียนสะสม {$absentCount} ครั้ง กรุณาติดตามและติดต่อผู้ปกครอง ({$student->guardian_phone})",
                        'is_read' => false,
                    ]);
                    $generated++;
                }
            }

            $lateCount = Attendance::where('student_id', $student->id)->where('status', 'late')->count();
            if ($lateCount >= 3) {
                $exists = AttendanceAlert::where('student_id', $student->id)
                    ->where('alert_type', 'frequent_late')
                    ->exists();

                if (!$exists) {
                    AttendanceAlert::create([
                        'student_id' => $student->id,
                        'alert_type' => 'frequent_late',
                        'title' => "มาสายบ่อยครั้ง สะสม {$lateCount} ครั้ง",
                        'message' => "นักเรียน {$student->full_name} มีสถิติมาสาย {$lateCount} ครั้ง",
                        'is_read' => false,
                    ]);
                    $generated++;
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "ตรวจสอบการแจ้งเตือนเรียบร้อย (พบใหม่ {$generated} รายการ)",
        ]);
    }
}
