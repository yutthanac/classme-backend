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
        // Delete any roles other than admin and teacher (strictly only 2 roles)
        Role::whereNotIn('name', ['admin', 'teacher'])->delete();

        // 1. Initial 2 Roles (Admin & Teacher)
        $admin = Role::updateOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => 'ผู้ดูแลระบบ',
                'description' => 'จัดการข้อมูล คำนำหน้า และสิทธิ์การใช้งานทั้งหมด',
            ]
        );

        $teacher = Role::updateOrCreate(
            ['name' => 'teacher'],
            [
                'display_name' => 'ครูผู้สอน',
                'description' => 'เช็คชื่อในชั้นเรียน สแกนใบเช็คชื่อด้วย AI และจัดการข้อมูลนักเรียน',
            ]
        );

        // 2. Seed Prefixes (Student & Teacher)
        $defaultPrefixes = [
            ['name' => 'นาย', 'type' => 'student', 'is_system' => true],
            ['name' => 'นางสาว', 'type' => 'student', 'is_system' => true],
            ['name' => 'ด.ช.', 'type' => 'student', 'is_system' => true],
            ['name' => 'ด.ญ.', 'type' => 'student', 'is_system' => true],
            ['name' => 'เด็กชาย', 'type' => 'student', 'is_system' => false],
            ['name' => 'เด็กหญิง', 'type' => 'student', 'is_system' => false],
            ['name' => 'อาจารย์', 'type' => 'staff', 'is_system' => true],
            ['name' => 'ครู', 'type' => 'staff', 'is_system' => true],
        ];

        foreach ($defaultPrefixes as $p) {
            Prefix::updateOrCreate(['name' => $p['name']], $p);
        }

        // 3. Permissions
        $permissions = [
            ['name' => 'manage_students', 'display_name' => 'จัดการข้อมูลนักเรียน', 'group' => 'ข้อมูลนักเรียน', 'description' => 'เพิ่ม ลบ แก้ไข ข้อมูลนักเรียน'],
            ['name' => 'check_attendance', 'display_name' => 'เช็คชื่อในชั้นเรียน', 'group' => 'การเข้าเรียน', 'description' => 'บันทึกเวลาเรียน มา/สาย/ลา/ขาด'],
            ['name' => 'scan_ai_sheet', 'display_name' => 'สแกนใบเช็คชื่อ AI', 'group' => 'การเข้าเรียน', 'description' => 'อัปโหลดและประมวลผลใบเช็คชื่อ'],
            ['name' => 'manage_classes', 'display_name' => 'จัดการวิชาและห้องเรียน', 'group' => 'หลักสูตร', 'description' => 'จัดการรายวิชาและตารางสอน'],
            ['name' => 'view_reports', 'display_name' => 'ดูสถิติและรายงาน', 'group' => 'รายงาน', 'description' => 'ดูสถิติและประวัติการเข้าเรียน'],
            ['name' => 'export_excel', 'display_name' => 'ส่งออกไฟล์ Excel', 'group' => 'รายงาน', 'description' => 'ดาวน์โหลดไฟล์ .xlsx'],
            ['name' => 'manage_prefixes', 'display_name' => 'จัดการคำนำหน้าชื่อ', 'group' => 'การตั้งค่าระบบ', 'description' => 'จัดการรายการคำนำหน้าชื่อนักเรียน'],
            ['name' => 'manage_roles', 'display_name' => 'จัดการบทบาทและสิทธิ์', 'group' => 'การตั้งค่าระบบ', 'description' => 'กำหนดสิทธิ์ Role & Permission'],
        ];

        $adminPermIds = [];
        $teacherPermIds = [];

        foreach ($permissions as $p) {
            $perm = Permission::updateOrCreate(['name' => $p['name']], $p);
            $adminPermIds[] = $perm->id;

            // Teacher permissions
            if (in_array($p['name'], ['manage_students', 'check_attendance', 'scan_ai_sheet', 'manage_classes', 'view_reports', 'export_excel'])) {
                $teacherPermIds[] = $perm->id;
            }
        }

        // Sync permissions
        $admin->permissions()->sync($adminPermIds);
        $teacher->permissions()->sync($teacherPermIds);

        // 4. Create / Update Initial 2 Users
        // 4.1 Admin User: Name is simply "Admin", no prefix
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@classme.ac.th'],
            [
                'prefix' => '',
                'name' => 'Admin',
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
