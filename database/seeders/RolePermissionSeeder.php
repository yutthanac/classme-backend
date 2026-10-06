<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Models\Prefix;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Initial 2 Roles (Admin & Teacher)
        $admin = Role::updateOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => 'ผู้ดูแลระบบ (Admin)',
                'description' => 'จัดการข้อมูลทั้งหมดในระบบ รวมถึงคำนำหน้า สิทธิ์การใช้งาน และการตั้งค่าระบบ',
            ]
        );

        $teacher = Role::updateOrCreate(
            ['name' => 'teacher'],
            [
                'display_name' => 'ครูผู้สอน (Teacher)',
                'description' => 'เช็คชื่อในชั้นเรียน สแกนใบเช็คชื่อด้วย AI จัดการข้อมูลนักเรียนและวิชาสอน',
            ]
        );

        // 2. Seed Prefixes (Student & Staff/Teacher)
        $defaultPrefixes = [
            ['name' => 'นาย', 'type' => 'student', 'is_system' => true],
            ['name' => 'นางสาว', 'type' => 'student', 'is_system' => true],
            ['name' => 'ด.ช.', 'type' => 'student', 'is_system' => true],
            ['name' => 'ด.ญ.', 'type' => 'student', 'is_system' => true],
            ['name' => 'เด็กชาย', 'type' => 'student', 'is_system' => false],
            ['name' => 'เด็กหญิง', 'type' => 'student', 'is_system' => false],
            ['name' => 'อาจารย์', 'type' => 'staff', 'is_system' => true],
            ['name' => 'ครู', 'type' => 'staff', 'is_system' => true],
            ['name' => 'ดร.', 'type' => 'staff', 'is_system' => true],
            ['name' => 'ผศ.', 'type' => 'staff', 'is_system' => false],
            ['name' => 'รศ.', 'type' => 'staff', 'is_system' => false],
            ['name' => 'ศ.', 'type' => 'staff', 'is_system' => false],
        ];

        foreach ($defaultPrefixes as $p) {
            Prefix::updateOrCreate(['name' => $p['name']], $p);
        }

        // 3. Permissions
        $permissions = [
            ['name' => 'manage_students', 'display_name' => 'จัดการข้อมูลนักเรียน', 'group' => 'ข้อมูลนักเรียน', 'description' => 'เพิ่ม ลบ แก้ไข ข้อมูลนักเรียน'],
            ['name' => 'check_attendance', 'display_name' => 'เช็คชื่อในชั้นเรียน', 'group' => 'การเข้าเรียน', 'description' => 'บันทึกเวลาเรียน มา/สาย/ลา/ขาด'],
            ['name' => 'scan_ai_sheet', 'display_name' => 'สแกนใบเช็คชื่อด้วย AI', 'group' => 'การเข้าเรียน', 'description' => 'อัปโหลดและประมวลผลใบเช็คชื่อ'],
            ['name' => 'manage_classes', 'display_name' => 'จัดการวิชาและห้องเรียน', 'group' => 'หลักสูตร', 'description' => 'จัดการรายวิชา ห้องเรียน ตารางสอน'],
            ['name' => 'view_reports', 'display_name' => 'ดูสถิติและรายงาน', 'group' => 'รายงาน', 'description' => 'ดูสถิติภาพรวมและประวัติการเข้าเรียน'],
            ['name' => 'export_excel', 'display_name' => 'ส่งออกไฟล์ Excel', 'group' => 'รายงาน', 'description' => 'ดาวน์โหลดไฟล์ .xlsx'],
            ['name' => 'manage_alerts', 'display_name' => 'จัดการการแจ้งเตือน', 'group' => 'การแจ้งเตือน', 'description' => 'รับการแจ้งเตือนและติดตามนักเรียนกลุ่มเสี่ยง'],
            ['name' => 'manage_prefixes', 'display_name' => 'จัดการคำนำหน้าชื่อ', 'group' => 'การตั้งค่าระบบ', 'description' => 'เพิ่ม ลบ แก้ไข รายการคำนำหน้าชื่อนักเรียนและบุคลากร'],
            ['name' => 'manage_roles', 'display_name' => 'จัดการบทบาทและสิทธิ์', 'group' => 'การตั้งค่าระบบ', 'description' => 'กำหนดสิทธิ์ Role & Permission ผู้ใช้งาน'],
        ];

        $adminPermIds = [];
        $teacherPermIds = [];

        foreach ($permissions as $p) {
            $perm = Permission::updateOrCreate(['name' => $p['name']], $p);
            $adminPermIds[] = $perm->id;

            // Teacher permissions (does NOT have manage_prefixes or manage_roles)
            if (in_array($p['name'], ['manage_students', 'check_attendance', 'scan_ai_sheet', 'manage_classes', 'view_reports', 'export_excel', 'manage_alerts'])) {
                $teacherPermIds[] = $perm->id;
            }
        }

        // Sync permissions
        $admin->permissions()->sync($adminPermIds);
        $teacher->permissions()->sync($teacherPermIds);

        // 4. Create / Update Initial 2 Users
        // 4.1 Admin User
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@classme.ac.th'],
            [
                'prefix' => 'ดร.',
                'name' => 'สมศักดิ์ บริหารกุล',
                'password' => Hash::make('admin123'),
                'role_id' => $admin->id,
            ]
        );
        $adminUser->roles()->sync([$admin->id]);

        // 4.2 Teacher User
        $teacherUser = User::updateOrCreate(
            ['email' => 'teacher@classme.ac.th'],
            [
                'prefix' => 'อาจารย์',
                'name' => 'ประสิทธิ์ ศรีวิชัย',
                'password' => Hash::make('teacher123'),
                'role_id' => $teacher->id,
            ]
        );
        $teacherUser->roles()->sync([$teacher->id]);
    }
}
