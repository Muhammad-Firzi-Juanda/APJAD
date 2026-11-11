<?php

namespace App\Console\Commands;

use App\Models\Jadwal;
use App\Models\Rombel;
use App\Services\SchedulerService;
use Illuminate\Console\Command;

class GenerateJadwalCommand extends Command
{
    protected $signature = 'jadwal:generate
                            {--semester= : Semester ganjil yang diproses. Kosong = semua}
                            {--prodi=Informatika : Filter prodi}
                            {--reset : Hapus seluruh jadwal & rombel sebelum generate}';

    protected $description = 'Generate jadwal kuliah otomatis dengan aturan anti-bentrok';

    public function handle(SchedulerService $scheduler): int
    {
        if ($this->option('reset')) {
            Jadwal::query()->delete();
            Rombel::query()->delete();
            $this->info('Jadwal lama dihapus.');
        }

        $semester = $this->option('semester');
        $semesters = $semester !== null && $semester !== ''
            ? [(int) $semester]
            : [1, 3, 5, 7];

        foreach ($semesters as $sem) {
            $hasil = $scheduler->generate($sem, $this->option('prodi'));

            $this->newLine();
            $this->info("=== SEMESTER {$sem} => {$hasil['jumlah_jadwal']} jadwal ===");

            if ($hasil['catatan']) {
                $this->warn($hasil['catatan']);
            }

            foreach ($hasil['log'] as $baris) {
                $warna = str_starts_with($baris, 'GAGAL') ? 'error' : 'line';
                $this->getOutput()->writeln("<{$warna}>  {$baris}</>");
            }

            if ($hasil['slot_pilihan']) {
                $this->comment("  Kuota MK Pilihan: {$hasil['kuota_pilihan']} (ambil 1 per kelompok)");
                foreach ($hasil['slot_pilihan'] as $slot) {
                    $this->comment('    '.$slot['label']);
                }
            }

            foreach ($hasil['peringatan'] as $peringatan) {
                $this->warn('  - '.$peringatan);
            }
        }

        return self::SUCCESS;
    }
}
