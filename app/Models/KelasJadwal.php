<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KelasJadwal extends Model
{
    use HasFactory;

    protected $table = 'kelas_jadwal';

    protected $fillable = ['mata_kuliah_id', 'ruang', 'kapasitas', 'hari', 'jam_mulai', 'jam_selesai', 'kode_kelas'];

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class);
    }

    public function dosenMengajar(): HasMany
    {
        return $this->hasMany(DosenMengajar::class);
    }
}
