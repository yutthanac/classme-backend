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
        Schema::create('prefixes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g. นาย, นางสาว, ด.ช., ด.ญ., อาจารย์, ดร.
            $table->enum('type', ['student', 'staff', 'all'])->default('student'); // student, staff, all
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prefixes');
    }
};
