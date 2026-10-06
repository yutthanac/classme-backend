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
        // Add user_id to subjects if not already exists
        if (!Schema::hasColumn('subjects', 'user_id')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('teacher_name')->constrained('users')->nullOnDelete();
            });
        }

        // Pivot table subject_user (teacher <-> subjects many to many)
        Schema::create('subject_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->unique(['user_id', 'subject_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_user');
    }
};
