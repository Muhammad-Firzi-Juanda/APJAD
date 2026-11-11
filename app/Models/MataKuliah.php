<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MataKuliah extends Model
{
    use HasFactory;

    protected $table = 'mata_kuliah';

    protected $fillable = [
        'nama',
        'kode',
        'sks',
        'semester',
        'tipe',
        'kategori',
        'pemilik',
        'kurikulum',
        'level_id',
        'deskripsi',
    ];

    public function kelasJadwal(): HasMany
    {
        return $this->hasMany(KelasJadwal::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(MataKuliahDetail::class, 'master_mk_id');
    }
}
