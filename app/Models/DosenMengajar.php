<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DosenMengajar extends Model
{
    use HasFactory;

    protected $table = 'dosen_mengajar';

    protected $fillable = ['nama_dosen', 'nip', 'kelas_jadwal_id', 'status', 'tanggal_mulai', 'tanggal_selesai'];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function kelasJadwal(): BelongsTo
    {
        return $this->belongsTo(KelasJadwal::class);
    }
}
