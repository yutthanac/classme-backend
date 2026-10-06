<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\Student;
use App\Models\Schedule;
use App\Models\AttendanceSession;
use App\Models\Attendance;
use App\Models\AttendanceAlert;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Default User / Teacher
        User::firstOrCreate(
            ['email' => 'teacher@classme.ac.th'],
            [
                'name' => 'อาจารย์ ประสิทธิ์ ศรีวิชัย',
                'password' => bcrypt('password123'),
            ]
        );

        // 2. Classrooms
        $c1 = Classroom::create([
            'name' => 'ม.4/1',
            'level' => 'มัธยมศึกษาปีที่ 4',
            'room' => '1',
            'academic_year' => '2569',
            'semester' => '1',
            'advisor_name' => 'อ.ประสิทธิ์ ศรีวิชัย',
        ]);

        $c2 = Classroom::create([
            'name' => 'ม.4/2',
            'level' => 'มัธยมศึกษาปีที่ 4',
            'room' => '2',
            'academic_year' => '2569',
            'semester' => '1',
            'advisor_name' => 'อ.วารุณี รักษ์ไทย',
        ]);

        $c3 = Classroom::create([
            'name' => 'ม.5/1',
            'level' => 'มัธยมศึกษาปีที่ 5',
            'room' => '1',
            'academic_year' => '2569',
            'semester' => '1',
            'advisor_name' => 'อ.สุดา ใจดี',
        ]);

        // 3. Subjects
        $s1 = Subject::create([
            'code' => 'ว31101',
            'name' => 'วิทยาศาสตร์กายภาพ (ฟิสิกส์)',
            'teacher_name' => 'อ.ประสิทธิ์ ศรีวิชัย',
            'credit' => 1.5,
            'color' => '#2563eb', // Blue
        ]);

        $s2 = Subject::create([
            'code' => 'ค31101',
            'name' => 'คณิตศาสตร์พื้นฐาน 1',
            'teacher_name' => 'อ.วารุณี รักษ์ไทย',
            'credit' => 1.5,
            'color' => '#059669', // Emerald
        ]);

        $s3 = Subject::create([
            'code' => 'อ31101',
            'name' => 'ภาษาอังกฤษเพื่อการสื่อสาร',
            'teacher_name' => 'Mr. David Miller',
            'credit' => 1.0,
            'color' => '#d97706', // Amber
        ]);

        $s4 = Subject::create([
            'code' => 'ว31181',
            'name' => 'วิทยาการคำนวณและโค้ดดิ้ง',
            'teacher_name' => 'อ.กิตติศักดิ์ พัฒนา',
            'credit' => 1.0,
            'color' => '#7c3aed', // Purple
        ]);

        $s5 = Subject::create([
            'code' => 'ท31101',
            'name' => 'ภาษาไทยวรรณคดี',
            'teacher_name' => 'อ.สุดา ใจดี',
            'credit' => 1.0,
            'color' => '#db2777', // Pink
        ]);

        // 4. Schedules
        Schedule::create([
            'subject_id' => $s1->id,
            'classroom' => 'ม.4/1',
            'day_of_week' => 1, // จันทร์
            'start_time' => '08:30',
            'end_time' => '10:10',
            'room_number' => 'ห้อง 412 (Lab)',
        ]);
        Schedule::create([
            'subject_id' => $s2->id,
            'classroom' => 'ม.4/1',
            'day_of_week' => 2, // อังคาร
            'start_time' => '10:20',
            'end_time' => '12:00',
            'room_number' => 'ห้อง 305',
        ]);
        Schedule::create([
            'subject_id' => $s4->id,
            'classroom' => 'ม.4/1',
            'day_of_week' => 3, // พุธ
            'start_time' => '13:00',
            'end_time' => '14:40',
            'room_number' => 'ห้องคอมพิวเตอร์ 2',
        ]);
        Schedule::create([
            'subject_id' => $s3->id,
            'classroom' => 'ม.4/1',
            'day_of_week' => 4, // พฤหัสบดี
            'start_time' => '08:30',
            'end_time' => '10:10',
            'room_number' => 'ห้อง 201 SoundLab',
        ]);
        Schedule::create([
            'subject_id' => $s1->id,
            'classroom' => 'ม.4/2',
            'day_of_week' => 5, // ศุกร์
            'start_time' => '10:20',
            'end_time' => '12:00',
            'room_number' => 'ห้อง 412 (Lab)',
        ]);

        // 5. Students in ม.4/1
        $studentsData = [
            ['code' => '6701001', 'num' => 1, 'title' => 'นาย', 'first' => 'กิตติพงษ์', 'last' => 'วงษ์สุวรรณ', 'gender' => 'male', 'phone' => '081-234-5671', 'parent' => 'นายสุรชัย วงษ์สุวรรณ'],
            ['code' => '6701002', 'num' => 2, 'title' => 'นาย', 'first' => 'จักริน', 'last' => 'ธนบูรณ์', 'gender' => 'male', 'phone' => '082-345-6782', 'parent' => 'นางพิมพ์ใจ ธนบูรณ์'],
            ['code' => '6701003', 'num' => 3, 'title' => 'นาย', 'first' => 'ชานนท์', 'last' => 'แสงสว่าง', 'gender' => 'male', 'phone' => '083-456-7893', 'parent' => 'นายวิทย์ แสงสว่าง'],
            ['code' => '6701004', 'num' => 4, 'title' => 'นาย', 'first' => 'ณัฐภัทร', 'last' => 'เจริญผล', 'gender' => 'male', 'phone' => '084-567-8904', 'parent' => 'นายมนตรี เจริญผล'],
            ['code' => '6701005', 'num' => 5, 'title' => 'นาย', 'first' => 'ธนากร', 'last' => 'สุขเจริญ', 'gender' => 'male', 'phone' => '085-678-9015', 'parent' => 'นางสมพร สุขเจริญ'],
            ['code' => '6701006', 'num' => 6, 'title' => 'นาย', 'first' => 'ธีรเทพ', 'last' => 'บุญชู', 'gender' => 'male', 'phone' => '086-789-0126', 'parent' => 'นายสมศักดิ์ บุญชู'],
            ['code' => '6701007', 'num' => 7, 'title' => 'นาย', 'first' => 'ปกรณ์', 'last' => 'มณีโชติ', 'gender' => 'male', 'phone' => '087-890-1237', 'parent' => 'นายชัชวาล มณีโชติ'],
            ['code' => '6701008', 'num' => 8, 'title' => 'นาย', 'first' => 'พงศกร', 'last' => 'รัตนพงศ์', 'gender' => 'male', 'phone' => '088-901-2348', 'parent' => 'นางวรรณา รัตนพงศ์'],
            ['code' => '6701009', 'num' => 9, 'title' => 'นาย', 'first' => 'ภานุวัฒน์', 'last' => 'แก้วงาม', 'gender' => 'male', 'phone' => '089-012-3459', 'parent' => 'นายไพบูลย์ แก้วงาม'],
            ['code' => '6701010', 'num' => 10, 'title' => 'นาย', 'first' => 'วรเมธ', 'last' => 'พิริยะกิจ', 'gender' => 'male', 'phone' => '081-123-4560', 'parent' => 'นางมาลี พิริยะกิจ'],
            ['code' => '6701011', 'num' => 11, 'title' => 'นางสาว', 'first' => 'กัญญารัตน์', 'last' => 'ศรีสมุทร', 'gender' => 'female', 'phone' => '082-234-5671', 'parent' => 'นายอุทัย ศรีสมุทร'],
            ['code' => '6701012', 'num' => 12, 'title' => 'นางสาว', 'first' => 'จิดาภา', 'last' => 'คงเกษม', 'gender' => 'female', 'phone' => '083-345-6782', 'parent' => 'นางนิภา คงเกษม'],
            ['code' => '6701013', 'num' => 13, 'title' => 'นางสาว', 'first' => 'ชลธิชา', 'last' => 'พิมลวรรณ', 'gender' => 'female', 'phone' => '084-456-7893', 'parent' => 'นายสมปอง พิมลวรรณ'],
            ['code' => '6701014', 'num' => 14, 'title' => 'นางสาว', 'first' => 'ญาณิศา', 'last' => 'บุษราคัม', 'gender' => 'female', 'phone' => '085-567-8904', 'parent' => 'นางสุพัตรา บุษราคัม'],
            ['code' => '6701015', 'num' => 15, 'title' => 'นางสาว', 'first' => 'ณิชานันท์', 'last' => 'อรุณรัตน์', 'gender' => 'female', 'phone' => '086-678-9015', 'parent' => 'นายประเสริฐ อรุณรัตน์'],
            ['code' => '6701016', 'num' => 16, 'title' => 'นางสาว', 'first' => 'ทิพวรรณ', 'last' => 'ทองมี', 'gender' => 'female', 'phone' => '087-789-0126', 'parent' => 'นางสุชาดา ทองมี'],
            ['code' => '6701017', 'num' => 17, 'title' => 'นางสาว', 'first' => 'ธวัลรัตน์', 'last' => 'เจริญผล', 'gender' => 'female', 'phone' => '088-890-1237', 'parent' => 'นายทศพล เจริญผล'],
            ['code' => '6701018', 'num' => 18, 'title' => 'นางสาว', 'first' => 'นภัสสร', 'last' => 'สุนทรเวช', 'gender' => 'female', 'phone' => '089-901-2348', 'parent' => 'นางชลิตา สุนทรเวช'],
            ['code' => '6701019', 'num' => 19, 'title' => 'นางสาว', 'first' => 'ปิยธิดา', 'last' => 'อัครเดช', 'gender' => 'female', 'phone' => '081-012-3459', 'parent' => 'นายธนา อัครเดช'],
            ['code' => '6701020', 'num' => 20, 'title' => 'นางสาว', 'first' => 'มนัสนันท์', 'last' => 'ศิริโรจน์', 'gender' => 'female', 'phone' => '082-123-4560', 'parent' => 'นางกานดา ศิริโรจน์'],
        ];

        $createdStudents = [];
        foreach ($studentsData as $st) {
            $createdStudents[] = Student::create([
                'student_code' => $st['code'],
                'student_number' => $st['num'],
                'title' => $st['title'],
                'first_name' => $st['first'],
                'last_name' => $st['last'],
                'classroom' => 'ม.4/1',
                'classroom_id' => $c1->id,
                'gender' => $st['gender'],
                'guardian_name' => $st['parent'],
                'guardian_phone' => $st['phone'],
                'status' => 'active',
            ]);
        }

        // Students in ม.4/2
        $students42 = [
            ['code' => '6702001', 'num' => 1, 'title' => 'นาย', 'first' => 'อนุชา', 'last' => 'รุ่งเรือง', 'gender' => 'male', 'phone' => '081-999-0001', 'parent' => 'นายประสพ รุ่งเรือง'],
            ['code' => '6702002', 'num' => 2, 'title' => 'นาย', 'first' => 'เอกชัย', 'last' => 'ทิพย์โอสถ', 'gender' => 'male', 'phone' => '081-999-0002', 'parent' => 'นางละออง ทิพย์โอสถ'],
            ['code' => '6702003', 'num' => 3, 'title' => 'นางสาว', 'first' => 'พิมพ์ชนก', 'last' => 'จรัสแสง', 'gender' => 'female', 'phone' => '081-999-0003', 'parent' => 'นางรัตนา จรัสแสง'],
            ['code' => '6702004', 'num' => 4, 'title' => 'นางสาว', 'first' => 'สุพรรณิการ์', 'last' => 'ดวงมณี', 'gender' => 'female', 'phone' => '081-999-0004', 'parent' => 'นายสมควร ดวงมณี'],
        ];
        foreach ($students42 as $st) {
            Student::create([
                'student_code' => $st['code'],
                'student_number' => $st['num'],
                'title' => $st['title'],
                'first_name' => $st['first'],
                'last_name' => $st['last'],
                'classroom' => 'ม.4/2',
                'classroom_id' => $c2->id,
                'gender' => $st['gender'],
                'guardian_name' => $st['parent'],
                'guardian_phone' => $st['phone'],
                'status' => 'active',
            ]);
        }

        // 6. Generate Past Attendance Sessions & Attendance Records
        $pastDates = [
            '2026-09-15',
            '2026-09-22',
            '2026-09-29',
            '2026-10-05',
        ];

        foreach ($pastDates as $idx => $dateStr) {
            $sess = AttendanceSession::create([
                'subject_id' => $s1->id,
                'classroom' => 'ม.4/1',
                'date' => $dateStr,
                'period' => 'คาบ 1-2',
                'topic' => 'บทที่ ' . ($idx + 1) . ' แรงและการเคลื่อนที่',
                'total_students' => count($createdStudents),
                'present_count' => 0,
                'late_count' => 0,
                'leave_count' => 0,
                'absent_count' => 0,
                'notes' => 'การเรียนการสอนปกติ ครบตามตัวชี้วัด',
            ]);

            $pCount = 0; $lCount = 0; $lvCount = 0; $abCount = 0;

            foreach ($createdStudents as $student) {
                // Determine pattern:
                // Student 3 (ชานนท์) has higher absence (test alert)
                // Student 6 (ธีรเทพ) has frequent late
                $status = 'present';
                $remark = null;

                if ($student->student_number == 3 && $idx >= 1) {
                    $status = 'absent';
                    $remark = 'ไม่แจ้งเหตุผล';
                } elseif ($student->student_number == 6 && $idx % 2 == 1) {
                    $status = 'late';
                    $remark = 'เข้าแถวช้า 15 นาที';
                } elseif ($student->student_number == 12 && $idx == 2) {
                    $status = 'leave';
                    $remark = 'ลาป่วย มีใบรับรองแพทย์';
                } else {
                    $rand = mt_rand(1, 100);
                    if ($rand <= 90) {
                        $status = 'present';
                    } elseif ($rand <= 95) {
                        $status = 'late';
                        $remark = 'รถติด';
                    } else {
                        $status = 'leave';
                        $remark = 'ลากิจ';
                    }
                }

                Attendance::create([
                    'attendance_session_id' => $sess->id,
                    'student_id' => $student->id,
                    'status' => $status,
                    'remark' => $remark,
                ]);

                if ($status == 'present') $pCount++;
                if ($status == 'late') $lCount++;
                if ($status == 'leave') $lvCount++;
                if ($status == 'absent') $abCount++;
            }

            $sess->update([
                'present_count' => $pCount,
                'late_count' => $lCount,
                'leave_count' => $lvCount,
                'absent_count' => $abCount,
            ]);
        }

        // 7. Seed Attendance Alerts
        $chanon = Student::where('student_number', 3)->where('classroom', 'ม.4/1')->first();
        if ($chanon) {
            AttendanceAlert::create([
                'student_id' => $chanon->id,
                'subject_id' => $s1->id,
                'alert_type' => 'risk_drop',
                'title' => 'ขาดเรียนสะสม 3 ครั้ง เสี่ยงหมดสิทธิ์สอบ',
                'message' => 'นักเรียนขาดเรียนวิชา ฟิสิกส์ 1 รวม 3 ครั้ง ติดต่อกัน กรุณาประสานงานผู้ปกครอง (เบอร์: ' . $chanon->guardian_phone . ')',
                'is_read' => false,
            ]);
        }

        $theerapat = Student::where('student_number', 6)->where('classroom', 'ม.4/1')->first();
        if ($theerapat) {
            AttendanceAlert::create([
                'student_id' => $theerapat->id,
                'subject_id' => $s1->id,
                'alert_type' => 'frequent_late',
                'title' => 'มาสายเกินเกณฑ์กำหนด 2 ครั้ง',
                'message' => 'นักเรียนมีสถิติมาสายในคาบเรียนแรก 2 ครั้ง ควรตักเตือนเรื่องการตรงต่อเวลา',
                'is_read' => false,
            ]);
        }
    }
}
