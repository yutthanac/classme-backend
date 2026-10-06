<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Student;
use App\Models\Subject;
use App\Models\AttendanceAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AttendanceController extends Controller
{
    /**
     * List attendance sessions with optional filters.
     */
    public function index(Request $request)
    {
        $query = AttendanceSession::with('subject');

        if ($request->filled('classroom')) {
            $query->where('classroom', $request->classroom);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }

        $sessions = $query->orderByDesc('date')->orderByDesc('id')->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $sessions,
        ]);
    }

    /**
     * Submit an attendance check session with student records.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'classroom' => 'required|string',
            'date' => 'required|date',
            'period' => 'required|string',
            'topic' => 'nullable|string',
            'notes' => 'nullable|string',
            'records' => 'required|array',
            'records.*.student_id' => 'required|exists:students,id',
            'records.*.status' => 'required|in:present,late,leave,absent',
            'records.*.remark' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $presentCount = 0;
            $lateCount = 0;
            $leaveCount = 0;
            $absentCount = 0;

            foreach ($validated['records'] as $rec) {
                switch ($rec['status']) {
                    case 'present': $presentCount++; break;
                    case 'late': $lateCount++; break;
                    case 'leave': $leaveCount++; break;
                    case 'absent': $absentCount++; break;
                }
            }

            // Create or update session for this date & subject & classroom
            $session = AttendanceSession::updateOrCreate(
                [
                    'subject_id' => $validated['subject_id'],
                    'classroom' => $validated['classroom'],
                    'date' => $validated['date'],
                ],
                [
                    'period' => $validated['period'],
                    'topic' => $validated['topic'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'total_students' => count($validated['records']),
                    'present_count' => $presentCount,
                    'late_count' => $lateCount,
                    'leave_count' => $leaveCount,
                    'absent_count' => $absentCount,
                ]
            );

            // Upsert attendance records
            foreach ($validated['records'] as $rec) {
                Attendance::updateOrCreate(
                    [
                        'attendance_session_id' => $session->id,
                        'student_id' => $rec['student_id'],
                    ],
                    [
                        'status' => $rec['status'],
                        'remark' => $rec['remark'] ?? null,
                    ]
                );

                // Auto Alert Check
                $student = Student::find($rec['student_id']);
                if ($student) {
                    $totalAbsent = Attendance::where('student_id', $student->id)
                        ->where('status', 'absent')
                        ->count();

                    if ($totalAbsent >= 3) {
                        AttendanceAlert::firstOrCreate(
                            [
                                'student_id' => $student->id,
                                'subject_id' => $validated['subject_id'],
                                'alert_type' => 'risk_drop',
                            ],
                            [
                                'title' => "ขาดเรียนสะสม {$totalAbsent} ครั้ง",
                                'message' => "นักเรียน {$student->full_name} ขาดเรียนสะสม {$totalAbsent} ครั้ง เสี่ยงหมดสิทธิ์สอบ (ติดต่อ: {$student->guardian_phone})",
                                'is_read' => false,
                            ]
                        );
                    }

                    $totalLate = Attendance::where('student_id', $student->id)
                        ->where('status', 'late')
                        ->count();

                    if ($totalLate >= 3) {
                        AttendanceAlert::firstOrCreate(
                            [
                                'student_id' => $student->id,
                                'subject_id' => $validated['subject_id'],
                                'alert_type' => 'frequent_late',
                            ],
                            [
                                'title' => "มาสายเกินเกณฑ์ {$totalLate} ครั้ง",
                                'message' => "นักเรียน {$student->full_name} มาสายสะสม {$totalLate} ครั้ง ควรติดตามพฤติกรรม",
                                'is_read' => false,
                            ]
                        );
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'บันทึกการเช็คชื่อเรียบร้อยแล้ว',
                'data' => $session->load(['subject', 'attendances.student']),
            ], 201);
        });
    }

    /**
     * Get specific session details.
     */
    public function show($id)
    {
        $session = AttendanceSession::with([
            'subject',
            'attendances' => function ($q) {
                $q->join('students', 'attendances.student_id', '=', 'students.id')
                  ->orderBy('students.student_number')
                  ->select('attendances.*');
            },
            'attendances.student'
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $session,
        ]);
    }

    /**
     * Get overall statistics for dashboard.
     */
    public function stats(Request $request)
    {
        $querySession = AttendanceSession::query();
        if ($request->filled('classroom')) {
            $querySession->where('classroom', $request->classroom);
        }
        if ($request->filled('subject_id')) {
            $querySession->where('subject_id', $request->subject_id);
        }

        $sessions = $querySession->get();

        $totalSessions = $sessions->count();
        $totalPresent = $sessions->sum('present_count');
        $totalLate = $sessions->sum('late_count');
        $totalLeave = $sessions->sum('leave_count');
        $totalAbsent = $sessions->sum('absent_count');
        $totalChecked = $totalPresent + $lateCount = $totalLate + $totalLeave + $totalAbsent;

        $overallRate = $totalChecked > 0 ? round((($totalPresent + $totalLate) / $totalChecked) * 100, 1) : 100;

        // Recent 5 sessions
        $recentSessions = AttendanceSession::with('subject')
            ->orderByDesc('date')
            ->take(5)
            ->get();

        // Classroom comparison
        $classroomStats = AttendanceSession::select(
            'classroom',
            DB::raw('SUM(present_count) as present'),
            DB::raw('SUM(late_count) as late'),
            DB::raw('SUM(leave_count) as `leave`'),
            DB::raw('SUM(absent_count) as `absent`'),
            DB::raw('COUNT(id) as sessions')
        )->groupBy('classroom')->get();

        // High risk students (absent >= 2 or late >= 3)
        $atRiskStudents = Student::withCount([
            'attendances as absent_count' => function ($q) {
                $q->where('status', 'absent');
            },
            'attendances as late_count' => function ($q) {
                $q->where('status', 'late');
            }
        ])
        ->having('absent_count', '>=', 2)
        ->orHaving('late_count', '>=', 2)
        ->orderByDesc('absent_count')
        ->take(10)
        ->get();

        // Total active students
        $totalStudents = Student::where('status', 'active')->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_students' => $totalStudents,
                'total_sessions' => $totalSessions,
                'overall_rate' => $overallRate,
                'counts' => [
                    'present' => $totalPresent,
                    'late' => $totalLate,
                    'leave' => $totalLeave,
                    'absent' => $totalAbsent,
                ],
                'recent_sessions' => $recentSessions,
                'classroom_stats' => $classroomStats,
                'at_risk_students' => $atRiskStudents,
            ],
        ]);
    }

    /**
     * History by subject.
     */
    public function subjectHistory($subjectId, Request $request)
    {
        $subject = Subject::findOrFail($subjectId);

        $sessions = AttendanceSession::with(['attendances.student'])
            ->where('subject_id', $subjectId)
            ->when($request->filled('classroom'), function ($q) use ($request) {
                $q->where('classroom', $request->classroom);
            })
            ->orderByDesc('date')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'subject' => $subject,
                'sessions' => $sessions,
            ],
        ]);
    }

    /**
     * Export attendance to Excel (.xlsx).
     */
    public function exportExcel(Request $request)
    {
        $classroom = $request->query('classroom', 'ม.4/1');
        $subjectId = $request->query('subject_id');

        $query = AttendanceSession::with(['subject', 'attendances.student'])
            ->where('classroom', $classroom);

        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }

        $sessions = $query->orderBy('date')->get();
        $students = Student::where('classroom', $classroom)->orderBy('student_number')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("เช็คชื่อ {$classroom}");

        // Title row
        $sheet->setCellValue('A1', "รายงานสรุปการเข้าเรียน ชั้น {$classroom}");
        $sheet->setCellValue('A2', "วันที่ออกรายงาน: " . now()->format('d/m/Y H:i'));
        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A2')->getFont()->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('666666'));

        // Table headers
        $headers = ['ลำดับ', 'รหัสนักเรียน', 'ชื่อ - สกุล', 'มา (ครั้ง)', 'สาย (ครั้ง)', 'ลา (ครั้ง)', 'ขาด (ครั้ง)', 'ร้อยละการเข้าเรียน'];
        
        // Add session dates as columns
        foreach ($sessions as $session) {
            $headers[] = $session->date . ' (' . ($session->subject->code ?? '') . ')';
        }

        $col = 1;
        $headerRow = 4;
        foreach ($headers as $h) {
            $sheet->setCellValueByColumnAndRow($col, $headerRow, $h);
            $col++;
        }

        // Header style
        $sheet->getStyle("A{$headerRow}:" . $sheet->getHighestColumn() . $headerRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E40AF'], // Navy / Blue
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $currentRow = 5;
        foreach ($students as $st) {
            $studentAttendances = Attendance::where('student_id', $st->id)
                ->whereIn('attendance_session_id', $sessions->pluck('id'))
                ->get();

            $p = $studentAttendances->where('status', 'present')->count();
            $l = $studentAttendances->where('status', 'late')->count();
            $lv = $studentAttendances->where('status', 'leave')->count();
            $ab = $studentAttendances->where('status', 'absent')->count();
            $tot = $sessions->count();
            $rate = $tot > 0 ? round((($p + $l) / $tot) * 100, 1) : 100;

            $sheet->setCellValue("A{$currentRow}", $st->student_number);
            $sheet->setCellValue("B{$currentRow}", $st->student_code);
            $sheet->setCellValue("C{$currentRow}", $st->full_name);
            $sheet->setCellValue("D{$currentRow}", $p);
            $sheet->setCellValue("E{$currentRow}", $l);
            $sheet->setCellValue("F{$currentRow}", $lv);
            $sheet->setCellValue("G{$currentRow}", $ab);
            $sheet->setCellValue("H{$currentRow}", $rate . '%');

            // Session values
            $sCol = 9;
            foreach ($sessions as $session) {
                $att = $studentAttendances->firstWhere('attendance_session_id', $session->id);
                $statusText = match($att->status ?? '-') {
                    'present' => '✓',
                    'late' => 'ส',
                    'leave' => 'ล',
                    'absent' => 'ข',
                    default => '-'
                };
                $sheet->setCellValueByColumnAndRow($sCol, $currentRow, $statusText);
                $sheet->getStyleByColumnAndRow($sCol, $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sCol++;
            }

            // Alignments
            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$currentRow}:H{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Conditional fill for high absent
            if ($ab >= 3) {
                $sheet->getStyle("G{$currentRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEE2E2');
            }

            $currentRow++;
        }

        // Auto width
        foreach (range(1, count($headers)) as $c) {
            $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = "attendance_{$classroom}_" . date('Ymd_His') . ".xlsx";

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
