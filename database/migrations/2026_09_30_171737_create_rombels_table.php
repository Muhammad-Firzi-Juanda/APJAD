<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rombels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mata_kuliah_detail_id')->constrained('mata_kuliah_details')->onDelete('cascade');
            $table->string('kode_rombel', 30); // e.g., TIF401-A, TIF401-B
            $table->integer('nomor_rombel'); // 1, 2, 3...
            $table->integer('kapasitas')->default(40);
            $table->foreignId('ruangan_id')->nullable()->constrained('ruangans')->onDelete('set null');
            $table->enum('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']);
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->foreignId('dosen_pengampu_id')->nullable()->constrained('dosens')->onDelete('set null');
            $table->enum('peran_dosen', ['Ketua', 'Anggota']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['mata_kuliah_detail_id', 'nomor_rombel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rombels');
    }
};
