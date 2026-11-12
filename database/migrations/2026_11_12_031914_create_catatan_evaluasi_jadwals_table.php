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
        Schema::create('catatan_evaluasi_jadwals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurusan_id')->constrained('jurusan')->cascadeOnDelete();
            $table->foreignId('prodi_id')->constrained('prodi')->cascadeOnDelete();
            $table->foreignId('jadwal_id')->nullable()->constrained('jadwals')->nullOnDelete();
            $table->foreignId('user_id_pengirim')->constrained('users')->cascadeOnDelete();

            $table->string('kategori', 50);
            $table->string('judul', 150);
            $table->text('catatan_rekomendasi');

            $table->string('status', 30)->default('Menunggu tanggapan');

            $table->text('tanggapan_prodi')->nullable();
            $table->foreignId('user_id_penanggap')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditanggapi_pada')->nullable();
            $table->timestamps();

            $table->index(['jurusan_id', 'status']);
            $table->index(['prodi_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catatan_evaluasi_jadwals');
    }
};
