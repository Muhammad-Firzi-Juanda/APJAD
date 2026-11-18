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
        Schema::table('mata_kuliah_details', function (Blueprint $table) {
            $table->foreignId('dosen_anggota_2_id')->nullable()->after('dosen_anggota_id')->constrained('dosens')->nullOnDelete();
            $table->foreignId('dosen_anggota_3_id')->nullable()->after('dosen_anggota_2_id')->constrained('dosens')->nullOnDelete();
        });

        Schema::table('jadwals', function (Blueprint $table) {
            $table->foreignId('dosen_anggota_1_id')->nullable()->after('dosen_id')->constrained('dosens')->nullOnDelete();
            $table->foreignId('dosen_anggota_2_id')->nullable()->after('dosen_anggota_1_id')->constrained('dosens')->nullOnDelete();
            $table->foreignId('dosen_anggota_3_id')->nullable()->after('dosen_anggota_2_id')->constrained('dosens')->nullOnDelete();
        });

        Schema::table('rombels', function (Blueprint $table) {
            $table->foreignId('dosen_anggota_1_id')->nullable()->after('dosen_pengampu_id')->constrained('dosens')->nullOnDelete();
            $table->foreignId('dosen_anggota_2_id')->nullable()->after('dosen_anggota_1_id')->constrained('dosens')->nullOnDelete();
            $table->foreignId('dosen_anggota_3_id')->nullable()->after('dosen_anggota_2_id')->constrained('dosens')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mata_kuliah_details', function (Blueprint $table) {
            $table->dropForeign(['dosen_anggota_2_id']);
            $table->dropForeign(['dosen_anggota_3_id']);
            $table->dropColumn(['dosen_anggota_2_id', 'dosen_anggota_3_id']);
        });

        Schema::table('jadwals', function (Blueprint $table) {
            $table->dropForeign(['dosen_anggota_1_id']);
            $table->dropForeign(['dosen_anggota_2_id']);
            $table->dropForeign(['dosen_anggota_3_id']);
            $table->dropColumn(['dosen_anggota_1_id', 'dosen_anggota_2_id', 'dosen_anggota_3_id']);
        });

        Schema::table('rombels', function (Blueprint $table) {
            $table->dropForeign(['dosen_anggota_1_id']);
            $table->dropForeign(['dosen_anggota_2_id']);
            $table->dropForeign(['dosen_anggota_3_id']);
            $table->dropColumn(['dosen_anggota_1_id', 'dosen_anggota_2_id', 'dosen_anggota_3_id']);
        });
    }
};
