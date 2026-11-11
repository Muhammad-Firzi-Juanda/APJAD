<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MataKuliahDetail extends Model
{
    use HasFactory;

    protected $table = 'mata_kuliah_details';

    protected $fillable = [
        'master_mk_id', 'kode_mk', 'nama_mk', 'sks', 'semester', 'kelas', 'tipe', 'kategori', 'prodi',
        'dosen_ketua_id', 'dosen_anggota_id', 'jumlah_kelas',
        'kapasitas_per_kelas', 'butuh_lab', 'tanpa_ruangan', 'catatan', 'is_active',
        'status_verifikasi', 'status_tawar',
    ];

    protected $casts = [
        'butuh_lab' => 'boolean',
        'tanpa_ruangan' => 'boolean',
        'is_active' => 'boolean',
        'status_tawar' => 'boolean',
    ];

    public function masterMk(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class, 'master_mk_id');
    }

    public function dosenKetua(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_ketua_id');
    }

    public function dosenAnggota(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_anggota_id');
    }

    public function rombels(): HasMany
    {
        return $this->hasMany(Rombel::class);
    }

    public function scopeSemester($query, $semester)
    {
        return $query->where('semester', $semester);
    }

    public function scopeWajib($query)
    {
        return $query->where('tipe', 'Wajib');
    }

    public function scopePilihan($query)
    {
        return $query->where('tipe', 'Pilihan');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeProdi($query, $prodi)
    {
        return $query->where('prodi', $prodi);
    }

    /**
     * Kode mata kuliah murni tanpa imbuhan kelas (-A, -B, dll).
     */
    public function getCleanKodeMkAttribute(): string
    {
        return preg_replace('/-[A-Za-z0-9]+$/', '', $this->kode_mk ?? '');
    }

    /**
     * Label kelas saja (A, B, C, D, ULANG) sesuai data.
     */
    public function getKelasLabelAttribute(): string
    {
        if (! empty($this->kelas)) {
            return strtoupper(trim($this->kelas));
        }

        if (preg_match('/-([A-Za-z0-9]+)$/', $this->kode_mk ?? '', $matches)) {
            return strtoupper($matches[1]);
        }

        return 'A';
    }
}
