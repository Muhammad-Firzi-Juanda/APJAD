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
        if (! Schema::hasTable('jurusan')) {
            Schema::create('jurusan', function (Blueprint $table) {
                $table->id();
                $table->string('nama', 100);
                $table->string('kode', 20)->nullable();
                $table->foreignId('fakultas_id')->constrained('fakultas')->onDelete('cascade');
                $table->string('kajur', 100)->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('prodi') && ! Schema::hasColumn('prodi', 'jurusan_id')) {
            Schema::table('prodi', function (Blueprint $table) {
                $table->foreignId('jurusan_id')->nullable()->after('fakultas_id')->constrained('jurusan')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('prodi') && Schema::hasColumn('prodi', 'jurusan_id')) {
            Schema::table('prodi', function (Blueprint $table) {
                $table->dropForeign(['jurusan_id']);
                $table->dropColumn('jurusan_id');
            });
        }

        Schema::dropIfExists('jurusan');
    }
};
