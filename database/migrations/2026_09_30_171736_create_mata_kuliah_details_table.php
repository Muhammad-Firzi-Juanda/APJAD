<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mata_kuliah_details', function (Blueprint $table) {
            $table->id();
            $table->string('kode_mk', 20)->unique();
            $table->string('nama_mk', 150);
            $table->integer('sks');
            $table->integer('semester'); // 1,3,5,7 (ganjil)
            $table->enum('tipe', ['Wajib', 'Pilihan']);
            $table->string('prodi', 100);
            $table->foreignId('dosen_ketua_id')->nullable()->constrained('dosens')->onDelete('set null');
            $table->foreignId('dosen_anggota_id')->nullable()->constrained('dosens')->onDelete('set null');
            $table->integer('jumlah_kelas')->default(1);
            $table->integer('kapasitas_per_kelas')->default(40);
            $table->boolean('butuh_lab')->default(false);
            $table->text('catatan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mata_kuliah_details');
    }
};
