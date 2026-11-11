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
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'fakultas_id')) {
                $table->foreignId('fakultas_id')->nullable()->after('role')->constrained('fakultas')->onDelete('set null');
            }
            if (! Schema::hasColumn('users', 'jurusan_id')) {
                $table->foreignId('jurusan_id')->nullable()->after('fakultas_id')->constrained('jurusan')->onDelete('set null');
            }
            if (! Schema::hasColumn('users', 'status')) {
                $table->string('status', 20)->default('aktif')->after('prodi_id')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'fakultas_id')) {
                $table->dropForeign(['fakultas_id']);
                $table->dropColumn('fakultas_id');
            }
            if (Schema::hasColumn('users', 'jurusan_id')) {
                $table->dropForeign(['jurusan_id']);
                $table->dropColumn('jurusan_id');
            }
            if (Schema::hasColumn('users', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
