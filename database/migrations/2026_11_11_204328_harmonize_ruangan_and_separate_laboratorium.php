<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Pastikan tabel laboratories memiliki kolom code jika belum ada
        if (Schema::hasTable('laboratories')) {
            Schema::table('laboratories', function (Blueprint $table) {
                if (! Schema::hasColumn('laboratories', 'code')) {
                    $table->string('code', 50)->nullable()->after('faculty_id');
                }
            });
        }

        // 2. Buat tabel kanonikal 'ruangan' (nama standar Bahasa Indonesia tanpa akhiran jamak 's')
        if (! Schema::hasTable('ruangan')) {
            Schema::create('ruangan', function (Blueprint $table) {
                $table->id();
                $table->foreignId('faculty_id')->nullable()->constrained('fakultas')->onDelete('cascade');
                $table->string('kode', 50)->nullable();
                $table->string('nama', 100);
                $table->string('tipe', 50)->default('Ruang Kelas'); // Khusus Ruang Kelas Teori / Aula / Auditorium (Bukan Lab)
                $table->integer('kapasitas')->default(40);
                $table->string('gedung', 50)->nullable();
                $table->string('lantai', 10)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. Tambahkan laboratory_id ke tabel jadwals untuk alokasi laboratorium praktikum secara terpisah
        if (Schema::hasTable('jadwals')) {
            Schema::table('jadwals', function (Blueprint $table) {
                if (! Schema::hasColumn('jadwals', 'laboratory_id')) {
                    $table->foreignId('laboratory_id')->nullable()->after('ruangan_id')->constrained('laboratories')->onDelete('set null');
                }
            });
        }

        // 4. Migrasi data ruangan dari ruangans dan rooms ke tabel kanonikal 'ruangan'
        // Serta pindahkan semua entitas Lab ke tabel 'laboratories' (Laboratorium terpisah dari Ruangan Kelas)
        if (Schema::hasTable('ruangans')) {
            $defaultFacultyId = DB::table('fakultas')->where('kode', 'FTTK')->value('id')
                ?? DB::table('fakultas')->value('id');

            $ruangans = DB::table('ruangans')->get();
            foreach ($ruangans as $r) {
                if (in_array(strtolower(trim($r->tipe)), ['lab', 'laboratorium'], true) || str_starts_with(strtoupper($r->kode), 'LAB')) {
                    if ($defaultFacultyId) {
                        DB::table('laboratories')->updateOrInsert(
                            ['name' => $r->nama],
                            [
                                'faculty_id' => $defaultFacultyId,
                                'code' => $r->kode,
                                'capacity' => $r->kapasitas,
                                'lab_type' => 'Komputer / Praktikum',
                                'status' => $r->is_active ? 'ACTIVE' : 'INACTIVE',
                                'created_at' => $r->created_at ?? now(),
                                'updated_at' => $r->updated_at ?? now(),
                            ]
                        );
                    }
                } else {
                    DB::table('ruangan')->updateOrInsert(
                        ['id' => $r->id],
                        [
                            'faculty_id' => $defaultFacultyId,
                            'kode' => $r->kode,
                            'nama' => $r->nama,
                            'tipe' => $r->tipe ?: 'Ruang Kelas',
                            'kapasitas' => $r->kapasitas,
                            'gedung' => $r->gedung ?? 'Teknik',
                            'lantai' => (string) ($r->lantai ?? '1'),
                            'is_active' => (bool) $r->is_active,
                            'created_at' => $r->created_at ?? now(),
                            'updated_at' => $r->updated_at ?? now(),
                        ]
                    );
                }
            }
        }

        // Sinkronisasi data dari rooms ke ruangan
        if (Schema::hasTable('rooms')) {
            $rooms = DB::table('rooms')->get();
            foreach ($rooms as $rm) {
                $hasValidFaculty = $rm->faculty_id && DB::table('fakultas')->where('id', $rm->faculty_id)->exists();
                if (in_array(strtolower(trim($rm->type)), ['lab', 'labor', 'laboratory'], true) || str_starts_with(strtoupper($rm->code ?? ''), 'LAB')) {
                    if ($hasValidFaculty) {
                        DB::table('laboratories')->updateOrInsert(
                            ['name' => $rm->name],
                            [
                                'faculty_id' => $rm->faculty_id,
                                'code' => $rm->code,
                                'capacity' => $rm->capacity,
                                'lab_type' => 'Praktikum',
                                'status' => $rm->status ?? 'ACTIVE',
                                'created_at' => $rm->created_at ?? now(),
                                'updated_at' => $rm->updated_at ?? now(),
                            ]
                        );
                    }
                } else {
                    DB::table('ruangan')->updateOrInsert(
                        ['nama' => $rm->name],
                        [
                            'faculty_id' => $hasValidFaculty ? $rm->faculty_id : null,
                            'kode' => $rm->code,
                            'tipe' => 'Ruang Kelas',
                            'kapasitas' => $rm->capacity,
                            'gedung' => 'Teknik',
                            'lantai' => '1',
                            'is_active' => ($rm->status ?? 'ACTIVE') === 'ACTIVE',
                            'created_at' => $rm->created_at ?? now(),
                            'updated_at' => $rm->updated_at ?? now(),
                        ]
                    );
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('jadwals') && Schema::hasColumn('jadwals', 'laboratory_id')) {
            Schema::table('jadwals', function (Blueprint $table) {
                $table->dropForeign(['laboratory_id']);
                $table->dropColumn('laboratory_id');
            });
        }

        Schema::dropIfExists('ruangan');

        if (Schema::hasTable('laboratories') && Schema::hasColumn('laboratories', 'code')) {
            Schema::table('laboratories', function (Blueprint $table) {
                $table->dropColumn('code');
            });
        }
    }
};
