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
        // 1. Classrooms
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g. ม.4/1, ม.4/2
            $table->string('level')->default('มัธยมศึกษาปีที่ 4');
            $table->string('room')->default('1');
            $table->string('academic_year')->default('2569');
            $table->string('semester')->default('1');
            $table->string('advisor_name')->nullable();
            $table->timestamps();
        });

        // 2. Subjects
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g. ว31101, ค31101
            $table->string('name'); // e.g. ฟิสิกส์ 1
            $table->string('teacher_name');
            $table->decimal('credit', 4, 1)->default(1.5);
            $table->string('color')->default('#2563eb');
            $table->timestamps();
        });

        // 3. Students
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_code')->unique(); // e.g. 6701001
            $table->integer('student_number')->default(1);
            $table->string('title')->default('นาย'); // นาย, นางสาว, ด.ช., ด.ญ.
            $table->string('first_name');
            $table->string('last_name');
            $table->string('classroom'); // e.g. ม.4/1
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->string('gender')->default('male');
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone')->nullable();
            $table->string('status')->default('active'); // active, inactive
            $table->string('avatar')->nullable();
            $table->timestamps();
        });

        // 4. Schedules
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('classroom');
            $table->tinyInteger('day_of_week')->default(1); // 1 = จันทร์, 2 = อังคาร, ..., 5 = ศุกร์
            $table->string('start_time')->default('08:30');
            $table->string('end_time')->default('10:10');
            $table->string('room_number')->nullable()->default('ห้อง 412');
            $table->timestamps();
        });

        // 5. Attendance Sessions
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('classroom');
            $table->date('date');
            $table->string('period')->default('คาบ 1-2');
            $table->string('topic')->nullable();
            $table->integer('total_students')->default(0);
            $table->integer('present_count')->default(0);
            $table->integer('late_count')->default(0);
            $table->integer('leave_count')->default(0);
            $table->integer('absent_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 6. Attendances
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id')->constrained('attendance_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('status')->default('present'); // present, late, leave, absent
            $table->string('remark')->nullable();
            $table->timestamps();

            $table->unique(['attendance_session_id', 'student_id']);
        });

        // 7. AI Sheet Uploads (Laravel file storage)
        Schema::create('ai_sheet_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('classroom')->nullable();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->date('date')->nullable();
            $table->json('parsed_data')->nullable();
            $table->string('status')->default('processed'); // uploaded, processed, confirmed
            $table->timestamps();
        });

        // 8. Attendance Alerts
        Schema::create('attendance_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('alert_type')->default('warning_absent'); // warning_absent, frequent_late, risk_drop
            $table->string('title');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_alerts');
        Schema::dropIfExists('ai_sheet_uploads');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('attendance_sessions');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('students');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('classrooms');
    }
};
