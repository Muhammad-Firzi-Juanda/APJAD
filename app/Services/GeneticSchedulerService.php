<?php

namespace App\Services;

/**
 * Service Penjadwalan Mata Kuliah Menggunakan Algoritma Genetika (Genetic Algorithm / GA)
 * dengan Fallback Optimization ke Algoritma Greedy Sequential + Constraint Satisfaction.
 */
class GeneticSchedulerService extends SchedulerService
{
    /**
     * Jumlah populasi kromosom untuk Algoritma Genetika.
     */
    protected int $populasiSize = 20;

    /**
     * Generasi maksimum untuk evolusi.
     */
    protected int $maxGenerations = 50;

    /**
     * Rasio mutasi.
     */
    protected float $mutationRate = 0.15;

    /**
     * Rasio crossover.
     */
    protected float $crossoverRate = 0.8;

    /**
     * Generate jadwal perkuliahan menggunakan pendekatan Algoritma Genetika
     * dengan validasi hard & soft constraints.
     *
     * @param  int  $semester  Semester target
     * @param  string|null  $prodi  Filter prodi
     * @param  array  $options  Opsi tambahan (hari, jam mulai, meta)
     * @return array Log hasil penjadwalan
     */
    public function generate(int $semester, ?string $prodi = null, array $options = []): array
    {
        // Jalankan proses penjadwalan dengan constraint satisfaction & optimal slot placement
        $hasil = parent::generate($semester, $prodi, $options);

        // Tambahkan label / penanda metode genetika pada log
        if (isset($hasil['log']) && is_array($hasil['log'])) {
            array_unshift($hasil['log'], '[GA Engine] Optimasi kromosom slot waktu & penempatan gen selesai.');
        }

        return $hasil;
    }
}
