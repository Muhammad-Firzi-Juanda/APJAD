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
        // 1. Course Categories (MKWU / UNV, MKWF / FST)
        Schema::create('course_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->string('scope', 20)->default('FACULTY'); // UNIVERSITY, FACULTY
            $table->foreignId('faculty_id')->nullable()->constrained('fakultas')->onDelete('cascade');
            $table->timestamps();
        });

        // 2. Courses (Master Course Fakultas: MKWU, MKWF)
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->string('name', 150);
            $table->unsignedTinyInteger('sks')->default(2);
            $table->unsignedTinyInteger('semester')->default(1);
            $table->string('type', 20)->default('THEORY'); // THEORY, PRACTICUM
            $table->foreignId('faculty_id')->constrained('fakultas')->onDelete('cascade');
            $table->foreignId('course_category_id')->constrained('course_categories')->onDelete('cascade');
            $table->string('status', 20)->default('ACTIVE'); // ACTIVE, INACTIVE
            $table->timestamps();

            $table->unique(['faculty_id', 'course_category_id', 'code'], 'uq_faculty_category_course_code');
        });

        // 3. Rooms
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('fakultas')->onDelete('cascade');
            $table->string('code', 50)->nullable();
            $table->string('name', 100);
            $table->unsignedSmallInteger('capacity')->default(40);
            $table->string('type', 50)->default('Kelas');
            $table->string('status', 20)->default('ACTIVE'); // ACTIVE, INACTIVE
            $table->timestamps();
        });

        // 4. Laboratories
        Schema::create('laboratories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('fakultas')->onDelete('cascade');
            $table->string('name', 100);
            $table->unsignedSmallInteger('capacity')->default(35);
            $table->string('lab_type', 50)->default('Komputer');
            $table->string('status', 20)->default('ACTIVE'); // ACTIVE, INACTIVE
            $table->timestamps();
        });

        // 5. Study Program Rooms (Allocation)
        Schema::create('study_program_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->foreignId('study_program_id')->constrained('prodi')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['room_id', 'study_program_id'], 'uq_room_study_program');
        });

        // 6. Study Program Laboratories (Allocation)
        Schema::create('study_program_laboratories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratory_id')->constrained('laboratories')->onDelete('cascade');
            $table->foreignId('study_program_id')->constrained('prodi')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['laboratory_id', 'study_program_id'], 'uq_lab_study_program');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_program_laboratories');
        Schema::dropIfExists('study_program_rooms');
        Schema::dropIfExists('laboratories');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('course_categories');
    }
};
