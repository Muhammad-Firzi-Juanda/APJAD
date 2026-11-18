<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jadwal extends Model
{
    use HasFactory;

    protected $table = 'jadwals';

    protected $fillable = [
        'rombel_id', 'ruangan_id', 'laboratory_id', 'dosen_id',
        'dosen_anggota_1_id', 'dosen_anggota_2_id', 'dosen_anggota_3_id',
        'hari', 'jam_mulai', 'jam_selesai', 'minggu_ke', 'catatan', 'is_active', 'is_manual',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_manual' => 'boolean',
    ];

    public function scopeManual($query)
    {
        return $query->where('is_manual', true);
    }

    public function scopeAutomatic($query)
    {
        return $query->where('is_manual', false);
    }

    public function rombel(): BelongsTo
    {
        return $this->belongsTo(Rombel::class);
    }

    public function ruangan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class);
    }

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Laboratory::class, 'laboratory_id');
    }

    public function getLokasiAttribute(): string
    {
        if ($this->laboratory) {
            return '[Lab] '.($this->laboratory->name ?? $this->laboratory->code ?? 'Lab');
        }

        return $this->ruangan?->nama ?? $this->ruangan?->kode ?? '-';
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }

    public function dosenAnggota1(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_anggota_1_id');
    }

    public function dosenAnggota2(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_anggota_2_id');
    }

    public function dosenAnggota3(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_anggota_3_id');
    }

    /**
     * @return array<int, array{role: string, dosen: Dosen}>
     */
    public function getTimDosenAttribute(): array
    {
        $team = [];
        if ($this->dosen) {
            $team[] = ['role' => 'Ketua', 'dosen' => $this->dosen];
        }
        if ($this->dosenAnggota1) {
            $team[] = ['role' => 'Anggota 1', 'dosen' => $this->dosenAnggota1];
        }
        if ($this->dosenAnggota2) {
            $team[] = ['role' => 'Anggota 2', 'dosen' => $this->dosenAnggota2];
        }
        if ($this->dosenAnggota3) {
            $team[] = ['role' => 'Anggota 3', 'dosen' => $this->dosenAnggota3];
        }

        return $team;
    }

    /**
     * Daftar ID semua dosen yang terlibat dalam jadwal ini (Ketua + Anggota).
     *
     * @return array<int, int>
     */
    public function getDosenIdsAttribute(): array
    {
        return array_values(array_filter([
            $this->dosen_id,
            $this->dosen_anggota_1_id,
            $this->dosen_anggota_2_id,
            $this->dosen_anggota_3_id,
        ]));
    }

    public function catatanEvaluasi(): HasMany
    {
        return $this->hasMany(CatatanEvaluasiJadwal::class, 'jadwal_id');
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

        $timeOverlap = $this->jam_mulai < $other->jam_selesai && $this->jam_selesai > $other->jam_mulai;
        if (! $timeOverlap) {
            return false;
        }

        if ($this->ruangan_id && $this->ruangan_id === $other->ruangan_id) {
            return true;
        }
        if ($this->laboratory_id && $this->laboratory_id === $other->laboratory_id) {
            return true;
        }

        // Cek bentrok dosen pada seluruh anggota tim
        $myDosens = $this->dosen_ids;
        $otherDosens = $other->dosen_ids;
        if (! empty(array_intersect($myDosens, $otherDosens))) {
            return true;
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
