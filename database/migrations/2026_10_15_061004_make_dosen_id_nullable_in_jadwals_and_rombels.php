<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwals', function (Blueprint $table) {
            // Lepas foreign key dulu sebelum mengubah kolom
            $table->dropForeign(['dosen_id']);
            $table->foreignId('dosen_id')->nullable()->change();
            $table->foreign('dosen_id')->references('id')->on('dosens')->onDelete('set null');
        });

        Schema::table('rombels', function (Blueprint $table) {
            // peran_dosen menjadi nullable supaya rombel tanpa dosen bisa disimpan
            $table->enum('peran_dosen', ['Ketua', 'Anggota'])->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('jadwals', function (Blueprint $table) {
            $table->dropForeign(['dosen_id']);
            $table->foreignId('dosen_id')->nullable(false)->change();
            $table->foreign('dosen_id')->references('id')->on('dosens')->onDelete('cascade');
        });

        Schema::table('rombels', function (Blueprint $table) {
            $table->enum('peran_dosen', ['Ketua', 'Anggota'])->nullable(false)->change();
        });
    }
};
