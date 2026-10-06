<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Roles table
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g. admin, teacher, staff
            $table->string('display_name'); // e.g. ผู้ดูแลระบบ, ครูผู้สอน
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // 2. Permissions table
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g. manage_students, take_attendance, scan_ai_sheet
            $table->string('display_name'); // e.g. จัดการข้อมูลนักเรียน, เช็คชื่อในชั้นเรียน
            $table->string('group')->default('general'); // e.g. student, attendance, system
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // 3. Permission_Role pivot table
        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
        });

        // 4. Add prefix and role_id to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'prefix')) {
                $table->string('prefix')->nullable()->after('id')->default('อาจารย์'); // คำนำหน้า เช่น อาจารย์, ครู, นาย, นางสาว, ดร.
            }
            if (!Schema::hasColumn('users', 'role_id')) {
                $table->foreignId('role_id')->nullable()->after('password')->constrained('roles')->nullOnDelete();
            }
        });

        // 5. Role_User pivot table (for multi-role support)
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role_id')) {
                $table->dropForeign(['role_id']);
                $table->dropColumn('role_id');
            }
            if (Schema::hasColumn('users', 'prefix')) {
                $table->dropColumn('prefix');
            }
        });
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
