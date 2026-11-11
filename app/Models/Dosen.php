<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dosen extends Model
{
    use HasFactory;

    protected $table = 'dosens';

    protected $fillable = ['nidn', 'nip', 'nama', 'inisial', 'jabatan', 'prodi', 'email', 'telepon', 'is_active'];

    /**
     * Mapping inisial dosen resmi berdasarkan data Excel proyek.
     */
    public const MAPPING_INISIAL = [
        'MB' => 'Martaleli Bettiza',
        'NN' => 'Nerfita Nikentari',
        'NR' => 'Nola Ritha',
        'MRR' => 'Muhamad Radzi Rathomi',
        'NH' => 'Nurul Hayaty',
        'FI' => 'Feri Irawan',
        'NE' => 'Nolan Efranda',
        'FM' => 'Fortia Magfira',
        'MP' => 'Marisha Pertiwi',
        'NF' => 'Nurfalinda',
        'RH' => 'Rifaldi Herikson',
        'FC' => 'Ferdi Chahyadi',
        'TM' => 'Tekad Matulatan',
        'HK' => 'Hendra Kurniawan',
        'NFF' => 'Novrizal Fattah Fahmitra',
        'BES' => 'Berta Erwin SLAM',
        'MF' => 'Muhamad Fadli',
        'NW' => 'Nancy Willian',
        'RP' => 'Rozef Pramana',
        'SY' => 'Sepli Yandri',
        'PA' => 'Pipin Armita',
        'SM' => 'Suparman Manjan',
        'ZE' => 'Zulfan Efendi',
        'RBP' => 'Robby Patria',
        'MZ' => 'Muzahar',
        'DN' => 'Deny Nusyirwan',
        'FID' => 'Fadliyah Idris',
        'EP' => 'Eko Prayetno',
        'MFFF' => 'Muhammad Fajar Fajri Fardilah',
        'TK' => 'Tety Kumalasari',
        'TD' => 'Thamrin Dahlan',
        'MM' => 'Muhamad Mujahidin',
        'RAA' => 'Renda Amalia Anggraini',
        'APH' => 'Ariza Purbasari Hadi',
        'BAV' => 'Bavitra',
        'DB' => 'Doli Bonardo',
        'SR' => 'Septia Refly',
        'FL' => 'Falatehan',
        'BIH' => 'Basyaruddin Ismail Harahap',
        'IKB' => 'Ibnu Kahfi Bachtiar',
        'MHSA' => 'M. Hasbi Sidqi Alajuri',
        'HAK' => 'Hollanda Arief Kusuma',
        'RF' => 'Rusfa',
        'TS' => 'Tonny Suhendra',
        'HS' => 'Hafiz Supriyadi',
        'AS' => 'Ahmad Syafiq',
        'SK' => 'Solekhan',
        'AA' => 'Arie Afriadi',
        'SD' => 'Sari Dewi',
    ];

    /**
     * Accessor untuk mendapatkan kode inisial dosen (e.g. PA, SM, MM, NF).
     */
    public function getInisialAttribute(?string $value): string
    {
        if (! empty($value)) {
            return trim($value);
        }

        return self::generateInisialFromName($this->nama);
    }

    /**
     * Generate inisial dari nama dosen atau pencocokan mapping Excel.
     */
    public static function generateInisialFromName(?string $nama): string
    {
        if (empty($nama)) {
            return '-';
        }

        $trimmed = trim($nama);

        // Jika nama sudah berupa kode inisial (2-5 huruf kapital)
        if (preg_match('/^[A-Z]{2,5}$/', $trimmed)) {
            return $trimmed;
        }

        // Cek kecocokan terbalik dari MAPPING_INISIAL
        $namaLower = strtolower($trimmed);
        foreach (self::MAPPING_INISIAL as $code => $knownName) {
            if (str_contains($namaLower, strtolower($knownName))) {
                return $code;
            }
        }

        // Bersihkan gelar depan: Dr., Prof., Ir., Dra., Drs., dll.
        $clean = preg_replace('/^(dr|prof|ir|dra|drs)\b\.?\s*/i', '', $trimmed);

        // Hapus gelar belakang setelah tanda koma (e.g. , S.T., M.T.)
        $clean = preg_split('/,/', $clean)[0] ?? $clean;

        // Ambil karakter huruf saja
        $clean = trim(preg_replace('/[^a-zA-Z\s]/', '', $clean));
        $words = array_values(array_filter(preg_split('/\s+/', $clean)));

        if (empty($words)) {
            return substr(strtoupper(preg_replace('/[^a-zA-Z]/', '', $trimmed)), 0, 3) ?: '-';
        }

        if (count($words) === 1) {
            return strtoupper(substr($words[0], 0, min(3, strlen($words[0]))));
        }

        $initials = '';
        foreach ($words as $w) {
            $initials .= strtoupper($w[0]);
        }

        return $initials;
    }

    public function jadwals(): HasMany
    {
        return $this->hasMany(Jadwal::class);
    }

    public function rombelsKetua(): HasMany
    {
        return $this->hasMany(Rombel::class, 'dosen_pengampu_id')->where('peran_dosen', 'Ketua');
    }

    public function rombelsAnggota(): HasMany
    {
        return $this->hasMany(Rombel::class, 'dosen_pengampu_id')->where('peran_dosen', 'Anggota');
    }

    public function matakuliahKetua(): HasMany
    {
        return $this->hasMany(MataKuliahDetail::class, 'dosen_ketua_id');
    }

    public function matakuliahAnggota(): HasMany
    {
        return $this->hasMany(MataKuliahDetail::class, 'dosen_anggota_id');
    }

    public function isKetua(): bool
    {
        return in_array($this->jabatan, ['Ketua', 'Ketua & Anggota']);
    }

    public function isAnggota(): bool
    {
        return in_array($this->jabatan, ['Anggota', 'Ketua & Anggota']);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
