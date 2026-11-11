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
        Schema::table('mata_kuliah', function (Blueprint $table) {
            if (! Schema::hasColumn('mata_kuliah', 'kategori')) {
                $table->string('kategori', 50)->nullable()->after('tipe');
            }
            if (! Schema::hasColumn('mata_kuliah', 'pemilik')) {
                $table->string('pemilik', 100)->nullable()->after('kategori');
            }
            if (! Schema::hasColumn('mata_kuliah', 'kurikulum')) {
                $table->string('kurikulum', 20)->default('2024')->nullable()->after('pemilik');
            }
        });

        Schema::table('mata_kuliah_details', function (Blueprint $table) {
            if (! Schema::hasColumn('mata_kuliah_details', 'master_mk_id')) {
                $table->foreignId('master_mk_id')->nullable()->after('id')->constrained('mata_kuliah')->onDelete('set null');
            }
            if (! Schema::hasColumn('mata_kuliah_details', 'kelas')) {
                $table->string('kelas', 10)->nullable()->after('semester');
            }
            if (! Schema::hasColumn('mata_kuliah_details', 'kategori')) {
                $table->string('kategori', 50)->nullable()->after('tipe');
            }
            if (! Schema::hasColumn('mata_kuliah_details', 'status_verifikasi')) {
                $table->string('status_verifikasi', 50)->nullable()->default('dikenali')->after('tanpa_ruangan');
            }
            if (! Schema::hasColumn('mata_kuliah_details', 'status_tawar')) {
                $table->boolean('status_tawar')->default(true)->after('status_verifikasi');
            }
        });

        Schema::table('rombels', function (Blueprint $table) {
            if (! Schema::hasColumn('rombels', 'kelas')) {
                $table->string('kelas', 10)->nullable()->after('nomor_rombel');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rombels', function (Blueprint $table) {
            if (Schema::hasColumn('rombels', 'kelas')) {
                $table->dropColumn('kelas');
            }
        });

        Schema::table('mata_kuliah_details', function (Blueprint $table) {
            $table->dropColumn(['master_mk_id', 'kelas', 'kategori', 'status_verifikasi', 'status_tawar']);
        });

        Schema::table('mata_kuliah', function (Blueprint $table) {
            $table->dropColumn(['kategori', 'pemilik', 'kurikulum']);
        });
    }
};
