<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Ruangan extends Model
{
    use HasFactory;

    protected $table = 'ruangans';

    protected $fillable = ['kode', 'nama', 'tipe', 'kapasitas', 'gedung', 'lantai', 'is_active'];

    protected static function booted(): void
    {
        static::saved(function (Ruangan $ruangan) {
            // Auto-sync ke tabel kanonikal 'ruangan' jika ada
            if (Schema::hasTable('ruangan')) {
                DB::table('ruangan')->updateOrInsert(
                    ['kode' => $ruangan->kode],
                    [
                        'nama' => $ruangan->nama,
                        'tipe' => $ruangan->tipe ?: 'Ruang Kelas',
                        'kapasitas' => $ruangan->kapasitas,
                        'gedung' => $ruangan->gedung,
                        'lantai' => (string) ($ruangan->lantai ?? '1'),
                        'is_active' => (bool) $ruangan->is_active,
                        'updated_at' => now(),
                    ]
                );
            }
        });

        static::deleted(function (Ruangan $ruangan) {
            if (Schema::hasTable('ruangan')) {
                DB::table('ruangan')->where('kode', $ruangan->kode)->delete();
            }
        });
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
        'LAB-01-FTTK', 'LAB-02-FTTK',
        'AUDIT-01-FTTK', 'AUDIT', 'AUDITORIUM',
    ];

    /**
     * Mengecek apakah ruangan ini merupakan ruangan bawaan/resmi kampus (12 kelas + 2 lab + 1 auditorium).
     */
    public function isDefault(): bool
    {
        return in_array($this->kode, self::DEFAULT_KODES, true)
            || in_array($this->kode, ['R1', 'R2', 'R3', 'R4', 'R5', 'R6', 'R7', 'R8', 'R9', 'R10', 'R11', 'R12', 'LAB1', 'LAB2', 'AUDIT', 'AUDITORIUM', 'AUDIT-01-FTTK'], true)
            || (bool) preg_match('/^(R(0?[1-9]|1[0-2])|LAB-?0?[1-2]|AUDIT(-?0?1)?)(-FTTK)?$/i', (string) $this->kode);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
