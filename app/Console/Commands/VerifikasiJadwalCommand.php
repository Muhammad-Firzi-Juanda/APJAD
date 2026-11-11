<?php

namespace App\Console\Commands;

use App\Models\Jadwal;
use Illuminate\Console\Command;

class VerifikasiJadwalCommand extends Command
{
    protected $signature = 'jadwal:verifikasi';

    protected $description = 'Cek bentrok ruangan, dosen, dan kelas pada jadwal hasil generate';

    public function handle(): int
    {
        $jadwal = Jadwal::with(['rombel.mataKuliah', 'ruangan', 'dosen'])
            ->where('is_active', 1)
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();

        if ($jadwal->isEmpty()) {
            $this->warn('Tidak ada jadwal. Jalankan: php artisan jadwal:generate');

            return self::SUCCESS;
        }

        $this->info("Total jadwal: {$jadwal->count()}");

        $masalah = [];

        // 1. Bentrok ruangan
        foreach ($this->pasangan($jadwal, fn ($a, $b) => $a->ruangan_id === $b->ruangan_id) as [$a, $b]) {
            $masalah[] = sprintf(
                'BENTROK RUANGAN %s: %s & %s pada %s %s-%s',
                $a->ruangan->kode,
                $a->rombel->kode_rombel,
                $b->rombel->kode_rombel,
                $a->hari,
                $this->jam($a->jam_mulai),
                $this->jam($a->jam_selesai)
            );
        }

        // 2. Bentrok dosen
        foreach ($this->pasangan($jadwal, fn ($a, $b) => $a->dosen_id && $a->dosen_id === $b->dosen_id) as [$a, $b]) {
            $masalah[] = sprintf(
                'BENTROK DOSEN %s: %s & %s pada %s %s-%s',
                $a->dosen->nama,
                $a->rombel->kode_rombel,
                $b->rombel->kode_rombel,
                $a->hari,
                $this->jam($a->jam_mulai),
                $this->jam($a->jam_selesai)
            );
        }

        // 3. Bentrok kelas (satu cohort prodi+semester)
        foreach ($this->pasangan($jadwal, function ($a, $b) {
            $pa = $a->rombel->mataKuliah;
            $pb = $b->rombel->mataKuliah;
            if (! $pa || ! $pb) {
                return false;
            }
            if ($pa->prodi !== $pb->prodi || $pa->semester !== $pb->semester) {
                return false;
            }

            // Kelas paralel dari MK yang sama memang dijadwalkan bersamaan
            // (ruangan & dosen berbeda), jadi bukan bentrok.
            if ($pa->id === $pb->id) {
                return false;
            }

            // MK Pilihan memang sengaja bentrok satu sama lain.
            return $pa->tipe !== 'Pilihan' || $pb->tipe !== 'Pilihan';
        }) as [$a, $b]) {
            $mk = $a->rombel->mataKuliah;
            $masalah[] = sprintf(
                'BENTROK KELAS %s semester %s: %s & %s pada %s %s-%s',
                $mk->prodi,
                $mk->semester,
                $a->rombel->kode_rombel,
                $b->rombel->kode_rombel,
                $a->hari,
                $this->jam($a->jam_mulai),
                $this->jam($a->jam_selesai)
            );
        }

        // 4. Setiap kelas wajib punya dosen pengampu
        foreach ($jadwal as $j) {
            if ($j->dosen_id) {
                continue;
            }

            $mk = $j->rombel?->mataKuliah;
            $masalah[] = sprintf(
                'TANPA DOSEN %s (%s) pada %s %s',
                $j->rombel->kode_rombel,
                $mk?->kode_mk ?? '-',
                $j->hari,
                $this->jam($j->jam_mulai)
            );
        }

        // 5. Dosen yang sama tidak boleh mengampu MK berbeda di slot bersamaan
        $kunci = [];
        foreach ($jadwal as $j) {
            if (! $j->dosen_id) {
                continue;
            }
            $kunci[$j->dosen_id.'|'.$j->hari.'|'.$j->jam_mulai][] = $j;
        }
        foreach ($kunci as $grup) {
            if (count($grup) < 2) {
                continue;
            }

            $mkIds = array_unique(array_map(fn ($j) => $j->rombel?->mataKuliah?->id, $grup));
            if (count($mkIds) < 2) {
                continue;
            } // kelas paralel MK yang sama, wajar

            $nama = $grup[0]->dosen->nama;
            $kode = implode(', ', array_filter(array_map(
                fn ($j) => $j->rombel?->mataKuliah?->kode_mk,
                $grup
            )));
            $masalah[] = sprintf(
                'DOSEN BENTROK-SAMA %s mengampu %s pada %s %s',
                $nama,
                $kode,
                $grup[0]->hari,
                $this->jam($grup[0]->jam_mulai)
            );
        }

        $this->newLine();
        if ($masalah === []) {
            $this->info('OK - tidak ada bentrok ruangan, dosen, maupun kelas.');
        } else {
            $this->error('Ditemukan '.count($masalah).' masalah:');
            foreach ($masalah as $m) {
                $this->line("  - $m");
            }

            return self::FAILURE;
        }

        // Ringkasan per hari
        $this->newLine();
        foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $hari) {
            $rows = $jadwal->where('hari', $hari)->sortBy('jam_mulai');
            $this->line("{$hari}: ".$rows->count().' kelas');
        }

        return self::SUCCESS;
    }

    protected function pasangan($jadwal, callable $filter): array
    {
        $hasil = [];
        $data = $jadwal->values();

        for ($i = 0; $i < $data->count(); $i++) {
            for ($j = $i + 1; $j < $data->count(); $j++) {
                $a = $data[$i];
                $b = $data[$j];

                if ($a->hari !== $b->hari) {
                    continue;
                }
                if (! $filter($a, $b)) {
                    continue;
                }
                if (! $this->tumpang($a, $b)) {
                    continue;
                }

                $hasil[] = [$a, $b];
            }
        }

        return $hasil;
    }

    protected function tumpang($a, $b): bool
    {
        return $this->menit($a->jam_mulai) < $this->menit($b->jam_selesai)
            && $this->menit($a->jam_selesai) > $this->menit($b->jam_mulai);
    }

    protected function menit($w): int
    {
        $w = (string) $w;
        [$j, $m] = array_map('intval', explode(':', $w));

        return $j * 60 + $m;
    }

    protected function jam($w): string
    {
        $w = (string) $w;

        return strlen($w) > 5 ? substr($w, 0, 5) : $w;
    }
}
