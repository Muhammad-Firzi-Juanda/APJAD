<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatatanEvaluasiJadwal extends Model
{
    use HasFactory;

    public const STATUS_MENUNGGU = 'Menunggu tanggapan';

    public const STATUS_DITINJAU = 'Sedang ditinjau';

    public const STATUS_DITANGGAPI = 'Ditanggapi';

    public const STATUS_SELESAI = 'Selesai';

    public const KATEGORI_BENTROK_DOSEN = 'Dugaan bentrok dosen';

    public const KATEGORI_KETIDAKSESUAIAN_JADWAL = 'Ketidaksesuaian jadwal';

    public const KATEGORI_KETIDAKSESUAIAN_RUANGAN = 'Ketidaksesuaian ruangan';

    public const KATEGORI_KETIDAKSESUAIAN_ROMBEL = 'Ketidaksesuaian rombel';

    public const KATEGORI_PERMINTAAN_PEMERIKSAAN = 'Permintaan pemeriksaan ulang';

    public const KATEGORI_LAINNYA = 'Catatan lainnya';

    protected $table = 'catatan_evaluasi_jadwals';

    protected $fillable = [
        'jurusan_id',
        'prodi_id',
        'jadwal_id',
        'user_id_pengirim',
        'kategori',
        'judul',
        'catatan_rekomendasi',
        'status',
        'tanggapan_prodi',
        'user_id_penanggap',
        'ditanggapi_pada',
    ];

    protected $casts = [
        'ditanggapi_pada' => 'datetime',
    ];

    /**
     * Daftar seluruh kategori resmi catatan evaluasi.
     *
     * @return list<string>
     */
    public static function listKategori(): array
    {
        return [
            self::KATEGORI_BENTROK_DOSEN,
            self::KATEGORI_KETIDAKSESUAIAN_JADWAL,
            self::KATEGORI_KETIDAKSESUAIAN_RUANGAN,
            self::KATEGORI_KETIDAKSESUAIAN_ROMBEL,
            self::KATEGORI_PERMINTAAN_PEMERIKSAAN,
            self::KATEGORI_LAINNYA,
        ];
    }

    /**
     * Daftar seluruh status resmi tindak lanjut catatan evaluasi.
     *
     * @return list<string>
     */
    public static function listStatus(): array
    {
        return [
            self::STATUS_MENUNGGU,
            self::STATUS_DITINJAU,
            self::STATUS_DITANGGAPI,
            self::STATUS_SELESAI,
        ];
    }

    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class, 'jurusan_id');
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class, 'prodi_id');
    }

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(Jadwal::class, 'jadwal_id');
    }

    public function pengirim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id_pengirim');
    }

    public function penanggap(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id_penanggap');
    }

    public function scopeJurusan(Builder $query, int $jurusanId): Builder
    {
        return $query->where('jurusan_id', $jurusanId);
    }

    public function scopeProdi(Builder $query, int $prodiId): Builder
    {
        return $query->where('prodi_id', $prodiId);
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function isSelesai(): bool
    {
        return $this->status === self::STATUS_SELESAI;
    }

    public function isMenunggu(): bool
    {
        return $this->status === self::STATUS_MENUNGGU;
    }
}
