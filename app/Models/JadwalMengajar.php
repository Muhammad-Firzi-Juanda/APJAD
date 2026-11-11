<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalMengajar extends Model
{
    use HasFactory;

    protected $table = 'jadwal_mengajar';

    protected $fillable = ['tanggal', 'jam_mulai', 'jam_selesai', 'dekan', 'materi', 'tipe', 'level_id', 'keterangan'];

    protected $casts = [
        'tanggal' => 'date',
    ];
}
