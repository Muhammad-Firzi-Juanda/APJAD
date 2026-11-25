<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Ruangan extends Model
{
    use HasFactory;

    protected $table = 'ruangans';

    protected $fillable = ['kode', 'nama', 'tipe', 'kapasitas', 'gedung', 'lantai', 'is_active', 'prodi_id'];

    protected static function booted(): void
    {
        static::saved(function (Ruangan $ruangan) {
            // Auto-sync ke tabel kanonikal 'ruangan' jika ada
            if (Schema::hasTable('ruangan')) {
                $payload = [
                    'nama' => $ruangan->nama,
                    'tipe' => $ruangan->tipe ?: 'Ruang Kelas',
                    'kapasitas' => $ruangan->kapasitas,
                    'gedung' => $ruangan->gedung,
                    'lantai' => (string) ($ruangan->lantai ?? '1'),
                    'is_active' => (bool) $ruangan->is_active,
                    'updated_at' => now(),
                ];

                if (Schema::hasColumn('ruangan', 'prodi_id')) {
                    $payload['prodi_id'] = $ruangan->prodi_id;
                }

                DB::table('ruangan')->updateOrInsert(
                    ['kode' => $ruangan->kode],
                    $payload
                );
            }

            // Jika tipe Lab, sinkronisasikan juga ke tabel laboratories jika ada dan fakultas tersedia
            if ($ruangan->tipe === 'Lab' && Schema::hasTable('laboratories')) {
                $defaultFacultyId = DB::table('fakultas')->where('kode', 'FTTK')->value('id')
                    ?? DB::table('fakultas')->value('id');

                if ($defaultFacultyId) {
                    DB::table('laboratories')->updateOrInsert(
                        ['name' => $ruangan->nama],
                        [
                            'faculty_id' => $defaultFacultyId,
                            'code' => $ruangan->kode,
                            'capacity' => $ruangan->kapasitas,
                            'lab_type' => 'Praktikum',
                            'status' => $ruangan->is_active ? 'ACTIVE' : 'INACTIVE',
                            'updated_at' => now(),
                        ]
                    );

                    $labId = DB::table('laboratories')->where('name', $ruangan->nama)->value('id');
                    if ($labId && $ruangan->prodi_id && Schema::hasTable('study_program_laboratories')) {
                        DB::table('study_program_laboratories')->updateOrInsert(
                            ['laboratory_id' => $labId, 'study_program_id' => $ruangan->prodi_id],
                            ['updated_at' => now()]
                        );
                    }
                }
            }
        });

        static::deleted(function (Ruangan $ruangan) {
            if (Schema::hasTable('ruangan')) {
                DB::table('ruangan')->where('kode', $ruangan->kode)->delete();
            }
        });
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class, 'prodi_id');
    }

    public function jadwals(): HasMany
    {
        return $this->hasMany(Jadwal::class);
    }

    public function rombels(): HasMany
    {
        return $this->hasMany(Rombel::class);
    }

    public function scopeLab($query)
    {
        return $query->where('tipe', 'Lab');
    }

    public function scopeRuangKelas($query)
    {
        return $query->where('tipe', 'Ruang Kelas');
    }

    public const DEFAULT_KODES = [
        'R01-FTTK', 'R02-FTTK', 'R03-FTTK', 'R04-FTTK',
        'R05-FTTK', 'R06-FTTK', 'R07-FTTK', 'R08-FTTK',
        'R09-FTTK', 'R10-FTTK', 'R11-FTTK', 'R12-FTTK',
        'LAB-01-FTTK', 'LAB-02-FTTK', 'LAB-BERSAMA',
        'LAB-TIF-01', 'LAB-TIF-02', 'LAB-TE-01', 'LAB-TE-02',
        'LAB-TP-01', 'LAB-TIND-01', 'LAB-KIM-01', 'LAB-TS-01',
        'LAB-PWK-01', 'LAB-ARS-01',
        'AUDIT-01-FTTK', 'AUDIT', 'AUDITORIUM',
    ];

    /**
     * Mengecek apakah ruangan ini merupakan ruangan bawaan/resmi kampus (12 kelas + lab prodi + auditorium).
     */
    public function isDefault(): bool
    {
        return in_array($this->kode, self::DEFAULT_KODES, true)
            || in_array($this->kode, ['R1', 'R2', 'R3', 'R4', 'R5', 'R6', 'R7', 'R8', 'R9', 'R10', 'R11', 'R12', 'LAB1', 'LAB2', 'AUDIT', 'AUDITORIUM', 'AUDIT-01-FTTK'], true)
            || (bool) preg_match('/^(R(0?[1-9]|1[0-2])|LAB-?0?[1-2]|LAB-(TIF|TE|TP|TIND|KIM|TS|PWK|ARS|BERSAMA)(-?0?[1-2])?|AUDIT(-?0?1)?)(-FTTK)?$/i', (string) $this->kode);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Memeriksa apakah ruangan (khususnya Laboratorium) sesuai dengan program studi tertentu.
     */
    public function matchesProdi(?string $prodiIdentifier, ?int $prodiId = null): bool
    {
        // Jika bukan Lab, ruangan kelas/auditorium bersifat umum
        if ($this->tipe !== 'Lab') {
            return true;
        }

        // Jika ruangan ini lab bersama / umum (prodi_id null dan kode berisi BERSAMA atau UMUM)
        $kodeUpper = strtoupper((string) $this->kode);
        $namaUpper = strtoupper((string) $this->nama);
        if (str_contains($kodeUpper, 'BERSAMA') || str_contains($namaUpper, 'BERSAMA') || str_contains($namaUpper, 'UMUM')) {
            return true;
        }

        // Cek ID Prodi jika tersedia
        if ($this->prodi_id !== null && $prodiId !== null) {
            return (int) $this->prodi_id === (int) $prodiId;
        }

        if (empty($prodiIdentifier) || in_array(strtolower(trim($prodiIdentifier)), ['semua', 'all', 'fakultas', 'universitas'], true)) {
            return true;
        }

        $p = strtolower(trim($prodiIdentifier));

        // Pemetaan Prodi -> Kode / Kata Kunci Lab
        $mapping = [
            'informatika' => ['tif', 'ti', 'informatika', 'komputer'],
            'elektro' => ['te', 'elektro', 'tenaga', 'kendali'],
            'perkapalan' => ['tp', 'perkapalan', 'hidrodinamika', 'maritim'],
            'industri' => ['tind', 'industri', 'ergonomi'],
            'kimia' => ['kim', 'kimia'],
            'sipil' => ['ts', 'sipil', 'struktur'],
            'wilayah' => ['pwk', 'perencanaan', 'spasial', 'sig'],
            'arsitektur' => ['ars', 'arsitektur', 'gambar', 'maket'],
        ];

        foreach ($mapping as $key => $keywords) {
            if (str_contains($p, $key)) {
                foreach ($keywords as $kw) {
                    if (str_contains(strtolower($this->kode), $kw) || str_contains(strtolower($this->nama), $kw)) {
                        return true;
                    }
                }

                // Jika prodi_id ada, cocokan dengan nama prodi relasi
                if ($this->prodi && str_contains(strtolower($this->prodi->nama), $key)) {
                    return true;
                }

                return false;
            }
        }

        return $this->prodi_id === null;
    }
}
