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
        'dosen_ketua_id', 'dosen_anggota_id', 'dosen_anggota_2_id', 'dosen_anggota_3_id', 'jumlah_kelas',
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
        if ($this->dosenKetua) {
            $team[] = ['role' => 'Ketua', 'dosen' => $this->dosenKetua];
        }
        if ($this->dosenAnggota) {
            $team[] = ['role' => 'Anggota 1', 'dosen' => $this->dosenAnggota];
        }
        if ($this->dosenAnggota2) {
            $team[] = ['role' => 'Anggota 2', 'dosen' => $this->dosenAnggota2];
        }
        if ($this->dosenAnggota3) {
            $team[] = ['role' => 'Anggota 3', 'dosen' => $this->dosenAnggota3];
        }

        return $team;
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
     * Scope untuk menyaring mata kuliah reguler (mengecualikan MK Ulang).
     */
    public function scopeBukanUlang($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('kelas')
                ->orWhereRaw('UPPER(TRIM(kelas)) != ?', ['ULANG']);
        })
            ->where('kode_mk', 'not like', '%-ULANG%')
            ->where('kode_mk', 'not like', '%ULANG%')
            ->where('nama_mk', 'not like', '%(ULANG)%')
            ->where('nama_mk', 'not like', '%[ULANG]%');
    }

    /**
     * Memeriksa apakah mata kuliah ini merupakan kelas mengulang / MK Ulang.
     */
    public function isUlang(): bool
    {
        $kelas = strtoupper(trim((string) ($this->kelas ?? '')));
        $kode = strtoupper(trim((string) ($this->kode_mk ?? '')));
        $nama = strtoupper(trim((string) ($this->nama_mk ?? '')));

        return $kelas === 'ULANG'
            || str_contains($kelas, 'ULANG')
            || str_contains($kode, '-ULANG')
            || str_contains($kode, 'ULANG')
            || (bool) preg_match('/\b(ulang|mengulang)\b/i', $nama)
            || str_contains($nama, '(ULANG)')
            || str_contains($nama, '[ULANG]');
    }

    /**
     * Memeriksa apakah mata kuliah ini adalah PPS (Pra Praktik Survei / Praktik Profesi / dsb).
     * Kategori ini dikecualikan dari proses generate jadwal otomatis.
     */
    public function isPps(): bool
    {
        $nama = strtolower(trim((string) ($this->nama_mk ?? '')));
        $kode = strtolower(trim((string) ($this->kode_mk ?? '')));
        $kategori = strtolower(trim((string) ($this->kategori ?? '')));
        $text = "{$nama} {$kode} {$kategori}";

        return (bool) preg_match('/\b(pps|praktik\s*pengenalan\s*sekolah|pra\s*praktik\s*survei|praktik\s*profesi|seminar\s*proposal|seminar\s*pps)\b/i', $text);
    }

    /**
     * Memeriksa apakah mata kuliah ini sepenuhnya DIKECUALIKAN dari penjadwalan otomatis
     * (MK Ulang, KP, Skripsi/TA, Kukerta/KKN, dan PPS).
     */
    public function isDikecualikanDariGenerate(): bool
    {
        return $this->isUlang()
            || $this->isKp()
            || $this->isSkripsi()
            || $this->isKkn()
            || $this->isPps()
            || $this->isMagang();
    }

    /**
     * Mendapatkan label rincian alasan pengecualian dari generate.
     */
    public function getAlasanDikecualikan(): ?string
    {
        if ($this->isUlang()) {
            return 'Ulang';
        }
        if ($this->isKp()) {
            return 'KP';
        }
        if ($this->isSkripsi()) {
            return 'Skripsi';
        }
        if ($this->isKkn()) {
            return 'Kukerta/KKN';
        }
        if ($this->isPps()) {
            return 'PPS';
        }
        if ($this->isMagang()) {
            return 'Magang/MBKM';
        }

        return null;
    }

    /**
     * Memeriksa apakah mata kuliah ini adalah KKN / Kukerta.
     * Kategori ini SEPENUHNYA dikecualikan dari proses generate jadwal (Senin-Jumat maupun Sabtu).
     */
    public function isKkn(): bool
    {
        $nama = strtolower(trim((string) ($this->nama_mk ?? '')));
        $kode = strtolower(trim((string) ($this->kode_mk ?? '')));
        $kategori = strtolower(trim((string) ($this->kategori ?? '')));
        $tipe = strtolower(trim((string) ($this->tipe ?? '')));

        return (bool) preg_match('/\b(kkn|kukerta|kuliah\s*kerja\s*nyata)\b/i', "{$nama} {$kode} {$kategori} {$tipe}");
    }

    /**
     * Mendapatkan jenis Kegiatan Akademik Khusus ('KP', 'Magang', 'Skripsi') atau null jika bukan.
     */
    public function getJenisKegiatanKhusus(): ?string
    {
        if ($this->isKkn()) {
            return null;
        }

        $nama = strtolower(trim((string) ($this->nama_mk ?? '')));
        $kode = strtolower(trim((string) ($this->kode_mk ?? '')));
        $kategori = strtolower(trim((string) ($this->kategori ?? '')));
        $text = "{$nama} {$kode} {$kategori}";

        if (preg_match('/\b(skripsi|tugas\s*akhir|thesis)\b/i', $text)) {
            return 'Skripsi';
        }

        if (preg_match('/\b(kerja\s*prakt[ie]k|kp|pkl|prakt[ie]k\s*kerja\s*lapangan)\b/i', $text)) {
            return 'KP';
        }

        if (preg_match('/\b(magang|mbkm|studi\s*independen|internship)\b/i', $text)) {
            return 'Magang';
        }

        return null;
    }

    /**
     * Memeriksa apakah mata kuliah merupakan Kegiatan Akademik Khusus (KP, Magang, Skripsi).
     * Kegiatan ini dijadwalkan secara khusus pada hari Sabtu.
     */
    public function isKegiatanKhusus(): bool
    {
        return $this->getJenisKegiatanKhusus() !== null;
    }

    public function isKp(): bool
    {
        return $this->getJenisKegiatanKhusus() === 'KP';
    }

    public function isMagang(): bool
    {
        return $this->getJenisKegiatanKhusus() === 'Magang';
    }

    public function isSkripsi(): bool
    {
        return $this->getJenisKegiatanKhusus() === 'Skripsi';
    }

    /**
     * Memeriksa apakah data mata kuliah tidak dapat dikenali / ambigu / memerlukan verifikasi.
     */
    public function isPerluDiperiksa(): bool
    {
        if (empty(trim((string) $this->kode_mk)) || empty(trim((string) $this->nama_mk))) {
            return true;
        }

        if (! is_numeric($this->sks) || (int) $this->sks <= 0) {
            return true;
        }

        if (! is_numeric($this->semester) || (int) $this->semester < 1 || (int) $this->semester > 8) {
            return true;
        }

        return false;
    }

    /**
     * Memeriksa apakah mata kuliah merupakan perkuliahan reguler (Senin - Jumat).
     */
    public function isReguler(): bool
    {
        return ! $this->isDikecualikanDariGenerate()
            && ! $this->isPerluDiperiksa();
    }

    /**
     * Menentukan kategori penjadwalan data:
     * - 'reguler' : Mata kuliah reguler (Senin - Jumat)
     * - 'kkn'     : KKN/Kukerta
     * - 'ulang'   : Mata kuliah mengulang
     * - 'kp'      : Kerja Praktik
     * - 'skripsi' : Skripsi / Tugas Akhir
     * - 'pps'     : Praktik Profesi / PPS
     * - 'magang'  : Magang / MBKM
     * - 'perlu_diperiksa' : Data ambigu / tidak lengkap
     */
    public function getKategoriPenjadwalan(): string
    {
        if ($this->isPerluDiperiksa()) {
            return 'perlu_diperiksa';
        }
        if ($this->isUlang()) {
            return 'ulang';
        }
        if ($this->isKkn()) {
            return 'kkn';
        }
        if ($this->isPps()) {
            return 'pps';
        }
        if ($this->isKp()) {
            return 'kp';
        }
        if ($this->isSkripsi()) {
            return 'skripsi';
        }
        if ($this->isMagang()) {
            return 'magang';
        }

        return 'reguler';
    }

    /**
     * Memeriksa apakah mata kuliah ini merupakan kegiatan non-tatap-muka.
     */
    public function isNonTatapMuka(): bool
    {
        return $this->isDikecualikanDariGenerate();
    }

    /**
     * Memeriksa apakah mata kuliah ini memerlukan alokasi jadwal kuliah tatap muka reguler.
     */
    public function isPerluJadwalTatapMuka(): bool
    {
        return $this->isReguler();
    }

    /**
     * Scope untuk menyaring mata kuliah yang memerlukan penjadwalan tatap muka reguler.
     */
    public function scopePerluJadwalTatapMuka($query)
    {
        return $this->scopeReguler($query);
    }

    /**
     * Scope untuk menyaring mata kuliah reguler (Senin - Jumat).
     */
    public function scopeReguler($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('kelas')
                ->orWhereRaw('UPPER(TRIM(kelas)) != ?', ['ULANG']);
        })
            ->where('kode_mk', 'not like', '%-ULANG%')
            ->where('kode_mk', 'not like', '%ULANG%')
            ->where('nama_mk', 'not like', '%(ULANG)%')
            ->where('nama_mk', 'not like', '%[ULANG]%')
            ->where('nama_mk', 'not like', '%skripsi%')
            ->where('nama_mk', 'not like', '%tugas akhir%')
            ->where('nama_mk', 'not like', '%thesis%')
            ->where('nama_mk', 'not like', '%kukerta%')
            ->where('nama_mk', 'not like', '%kkn%')
            ->where('nama_mk', 'not like', '%kerja praktek%')
            ->where('nama_mk', 'not like', '%kerja praktik%')
            ->where('nama_mk', 'not like', '%magang%')
            ->where('nama_mk', 'not like', '%pkl%')
            ->where('nama_mk', 'not like', '%mbkm%')
            ->where('nama_mk', 'not like', '%pps%')
            ->where('kode_mk', 'not like', '%pps%');
    }

    /**
     * Scope untuk menyaring mata kuliah yang dikecualikan dari generate otomatis
     * (Ulang, KP, Skripsi, Kukerta/KKN, PPS, Magang).
     */
    public function scopeDikecualikanDariGenerate($query)
    {
        return $query->where(function ($q) {
            $q->whereRaw('UPPER(TRIM(COALESCE(kelas, ""))) = ?', ['ULANG'])
                ->orWhere('kode_mk', 'like', '%-ULANG%')
                ->orWhere('kode_mk', 'like', '%ULANG%')
                ->orWhere('nama_mk', 'like', '%(ULANG)%')
                ->orWhere('nama_mk', 'like', '%[ULANG]%')
                ->orWhere('nama_mk', 'like', '%skripsi%')
                ->orWhere('nama_mk', 'like', '%tugas akhir%')
                ->orWhere('nama_mk', 'like', '%thesis%')
                ->orWhere('nama_mk', 'like', '%kukerta%')
                ->orWhere('nama_mk', 'like', '%kkn%')
                ->orWhere('nama_mk', 'like', '%kerja praktek%')
                ->orWhere('nama_mk', 'like', '%kerja praktik%')
                ->orWhere('nama_mk', 'like', '%magang%')
                ->orWhere('nama_mk', 'like', '%pkl%')
                ->orWhere('nama_mk', 'like', '%mbkm%')
                ->orWhere('nama_mk', 'like', '%pps%')
                ->orWhere('kode_mk', 'like', '%pps%');
        });
    }

    /**
     * Scope untuk menyaring Kegiatan Akademik Khusus (KP, Magang, Skripsi).
     */
    public function scopeKegiatanKhusus($query, ?string $jenis = null)
    {
        return $query->where(function ($q) use ($jenis) {
            if ($jenis === 'Skripsi') {
                $q->where('nama_mk', 'like', '%skripsi%')
                    ->orWhere('nama_mk', 'like', '%tugas akhir%')
                    ->orWhere('nama_mk', 'like', '%thesis%');
            } elseif ($jenis === 'KP') {
                $q->where('nama_mk', 'like', '%kerja praktek%')
                    ->orWhere('nama_mk', 'like', '%kerja praktik%')
                    ->orWhere('nama_mk', 'like', '%pkl%');
            } elseif ($jenis === 'Magang') {
                $q->where('nama_mk', 'like', '%magang%')
                    ->orWhere('nama_mk', 'like', '%mbkm%');
            } else {
                $q->where('nama_mk', 'like', '%skripsi%')
                    ->orWhere('nama_mk', 'like', '%tugas akhir%')
                    ->orWhere('nama_mk', 'like', '%thesis%')
                    ->orWhere('nama_mk', 'like', '%kerja praktek%')
                    ->orWhere('nama_mk', 'like', '%kerja praktik%')
                    ->orWhere('nama_mk', 'like', '%pkl%')
                    ->orWhere('nama_mk', 'like', '%magang%')
                    ->orWhere('nama_mk', 'like', '%mbkm%');
            }
        });
    }

    /**
     * Scope untuk menyaring KKN / Kukerta.
     */
    public function scopeKkn($query)
    {
        return $query->where(function ($q) {
            $q->where('nama_mk', 'like', '%kukerta%')
                ->orWhere('nama_mk', 'like', '%kkn%')
                ->orWhere('nama_mk', 'like', '%kuliah kerja nyata%')
                ->orWhere('kategori', 'like', '%kkn%')
                ->orWhere('kategori', 'like', '%kukerta%');
        });
    }

    /**
     * Scope untuk menyaring mata kuliah non-tatap-muka (Skripsi, KP, Magang, Kukerta/KKN).
     */
    public function scopeNonTatapMuka($query)
    {
        return $query->where(function ($q) {
            $q->where('tanpa_ruangan', true)
                ->orWhere('nama_mk', 'like', '%skripsi%')
                ->orWhere('nama_mk', 'like', '%tugas akhir%')
                ->orWhere('nama_mk', 'like', '%kukerta%')
                ->orWhere('nama_mk', 'like', '%kkn%')
                ->orWhere('nama_mk', 'like', '%kerja praktek%')
                ->orWhere('nama_mk', 'like', '%kerja praktik%')
                ->orWhere('nama_mk', 'like', '%magang%')
                ->orWhere('nama_mk', 'like', '%pkl%')
                ->orWhere('nama_mk', 'like', '%mbkm%');
        });
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
