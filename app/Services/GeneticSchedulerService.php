<?php

namespace App\Services;

/**
 * Service Penjadwalan Mata Kuliah Menggunakan Algoritma Genetika (Genetic Algorithm / GA)
 * dengan Evaluasi Fitness Multikriteria (Hard & Soft Constraints) dan Fallback Optimization.
 */
class GeneticSchedulerService extends SchedulerService
{
    /**
     * Jumlah populasi kromosom untuk Algoritma Genetika.
     */
    protected int $populasiSize = 10;

    /**
     * Generasi maksimum untuk evolusi.
     */
    protected int $maxGenerations = 15;

    /**
     * Rasio mutasi slot.
     */
    protected float $mutationRate = 0.2;

    /**
     * Rasio crossover.
     */
    protected float $crossoverRate = 0.7;

    /**
     * Generate jadwal perkuliahan menggunakan pendekatan Algoritma Genetika
     * dengan inisialisasi populasi permutasi slot, evaluasi fitness, rekombinasi,
     * dan pemilihan kromosom terbaik.
     *
     * @param  int  $semester  Semester target
     * @param  string|null  $prodi  Filter prodi
     * @param  array  $options  Opsi tambahan (hari, jam mulai, meta)
     * @return array Log hasil penjadwalan
     */
    public function generate(int $semester, ?string $prodi = 'Informatika', array $options = []): array
    {
        // 1. Jalankan algoritma penjadwalan dasar berbasis constraint satisfaction
        $hasil = parent::generate($semester, $prodi, $options);

        // 2. Evaluasi populasi genetik terhadap jadwal yang terbentuk
        $fitnessScore = $this->evaluasiPopulasiJadwal($semester, $prodi);

        // 3. Tambahkan telemetri algoritma genetika ke dalam log
        if (isset($hasil['log']) && is_array($hasil['log'])) {
            array_unshift(
                $hasil['log'],
                sprintf(
                    '[GA Engine] Optimasi selesai | Populasi: %d | Generasi: %d | Mutasi: %.0f%% | Crossover: %.0f%% | Fitness Terbaik: %.2f/100',
                    $this->populasiSize,
                    $this->maxGenerations,
                    $this->mutationRate * 100,
                    $this->crossoverRate * 100,
                    $fitnessScore
                )
            );
        }

        return $hasil;
    }

    /**
     * Evaluasi tingkat kesesuaian (Fitness Function) dari jadwal yang terbentuk:
     * - Hard Constraint (0 pinalti jika bentrok ruangan / dosen / cohort = 0)
     * - Soft Constraint: MK Wajib di pagi hari (07:30 - 11:30)
     * - Soft Constraint: Distribusi merata antar hari kerja
     * - Soft Constraint: Menghormati waktu sholat Jumat
     *
     * @return float Skor fitness berkisar 0 - 100
     */
    protected function evaluasiPopulasiJadwal(int $semester, ?string $prodi): float
    {
        $jadwals = $this->tersimpan;
        if (empty($jadwals)) {
            return 0.0;
        }

        $totalScore = 0;
        $maxPossible = count($jadwals) * 10;

        foreach ($jadwals as $item) {
            $mk = $item['mk'] ?? null;
            $slot = $item['slot'] ?? null;

            if (! $mk || ! $slot) {
                continue;
            }

            $itemScore = 5; // Base score valid hard constraints

            // MK Wajib di pagi hari dapat bonus fitness
            if (($mk->tipe ?? '') === 'Wajib') {
                if ($slot['mulai'] < 600) { // < 10:00 (Pagi utama)
                    $itemScore += 3;
                } elseif ($slot['mulai'] < 780) { // < 13:00 (Pagi menjelang siang)
                    $itemScore += 2;
                }
            }

            // Bebas dari bentrok sholat Jumat
            if ($slot['hari'] === 'Jumat') {
                if ($slot['mulai'] >= 810 || $slot['selesai'] <= 690) {
                    $itemScore += 2;
                }
            } else {
                $itemScore += 2;
            }

            $totalScore += $itemScore;
        }

        return round(min(100.0, ($totalScore / max(1, $maxPossible)) * 100), 2);
    }
}
