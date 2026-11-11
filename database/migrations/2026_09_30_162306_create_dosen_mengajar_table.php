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
        Schema::create('dosen_mengajar', function (Blueprint $table) {
            $table->id();
            $table->string('nama_dosen', 100);
            $table->string('nip', 50)->nullable();
            $table->foreignId('kelas_jadwal_id')->constrained('kelas_jadwal')->onDelete('cascade');
            $table->enum('status', ['Aktif', 'Cuti', 'Izin', 'Selesai']);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dosen_mengajar');
    }
};
