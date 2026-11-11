<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Jadwal extends Model
{
    use HasFactory;

    protected $table = 'jadwals';

    protected $fillable = [
        'rombel_id', 'ruangan_id', 'dosen_id', 'hari',
        'jam_mulai', 'jam_selesai', 'minggu_ke', 'catatan', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function rombel(): BelongsTo
    {
        return $this->belongsTo(Rombel::class);
    }

    public function ruangan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class);
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }

    public function scopeHari($query, $hari)
    {
        return $query->where('hari', $hari);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getDurasiMenitAttribute(): int
    {
        $mulai = Carbon::parse($this->jam_mulai);
        $selesai = Carbon::parse($this->jam_selesai);

        return $mulai->diffInMinutes($selesai);
    }

    // Check conflict with another jadwal
    public function bentrokDengan(Jadwal $other): bool
    {
        if ($this->hari !== $other->hari) {
            return false;
        }
        if ($this->ruangan_id === $other->ruangan_id) {
            return $this->jam_mulai < $other->jam_selesai && $this->jam_selesai > $other->jam_mulai;
        }
        if ($this->dosen_id === $other->dosen_id) {
            return $this->jam_mulai < $other->jam_selesai && $this->jam_selesai > $other->jam_mulai;
        }

        return false;
    }

    /**
     * Kode mata kuliah murni tanpa imbuhan kelas.
     */
    public function getCleanKodeMkAttribute(): string
    {
        return $this->rombel?->clean_kode_mk ?? '';
    }

    /**
     * Label kelas saja (A, B, C, D, ULANG) sesuai data.
     */
    public function getKelasLabelAttribute(): string
    {
        return $this->rombel?->kelas_label ?? 'A';
    }

    /**
     * Kode inisial dosen (e.g. PA, SM, MM) sesuai format Excel.
     */
    public function getDosenInisialAttribute(): string
    {
        return $this->dosen?->inisial ?? '-';
    }
}
