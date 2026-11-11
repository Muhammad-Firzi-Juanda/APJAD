<?php

namespace App\Services;

use App\Models\Dosen;
use App\Models\MataKuliah;
use App\Models\MataKuliahDetail;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
<<<<<<< HEAD
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
=======
>>>>>>> d1c29ef9954c3c6e9b48137a65ff2e4deacab6d0
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MataKuliahImportService
{
    public const TARGET_FIELDS = [
        'kode_mk' => ['label' => 'Kode MK', 'required' => true],
        'nama_mk' => ['label' => 'Nama Mata Kuliah', 'required' => true],
        'sks' => ['label' => 'SKS', 'required' => true],
        'semester' => ['label' => 'Semester', 'required' => true],
        'kelas' => ['label' => 'Kelas (A, B, C)', 'required' => false],
        'tipe' => ['label' => 'Tipe (Wajib / Pilihan)', 'required' => false],
        'prodi' => ['label' => 'Program Studi', 'required' => false],
        'dosen_ketua' => ['label' => 'Dosen Ketua / Pengampu Utama', 'required' => false],
        'dosen_anggota' => ['label' => 'Dosen Anggota / Pengampu 2', 'required' => false],
        'jumlah_kelas' => ['label' => 'Jumlah Kelas', 'required' => false],
        'kapasitas_per_kelas' => ['label' => 'Kapasitas per Kelas', 'required' => false],
        'butuh_lab' => ['label' => 'Kebutuhan Laboratorium', 'required' => false],
    ];

    public const HEADERS = [
        'kode_mk',
        'nama_mk',
        'sks',
        'semester',
        'kelas',
        'tipe',
        'prodi',
        'dosen_ketua',
        'dosen_anggota',
        'jumlah_kelas',
        'kapasitas_per_kelas',
        'butuh_lab',
    ];

    protected array $aliases = [
        'kode_mk' => [
            'kode_mk', 'kode mk', 'kode', 'code', 'kd_mk', 'kd mk', 'kode matkul',
            'kode matakuliah', 'kode mata kuliah', 'kd matkul', 'kd matakuliah',
            'kd mata kuliah', 'course code', 'subject code', 'kdmk', 'kd_mkk',
            'kode_matakuliah', 'kodemk', 'kd mk.', 'kode mk.', 'no mk', 'nomor mk',
            'id mk', 'id matkul', 'kd_mata_kuliah', 'kode_mata_kuliah', 'course_code',
        ],
        'nama_mk' => [
            'nama_mk', 'nama mk', 'nama', 'nama matkul', 'nama matakuliah', 'matkul',
            'mata kuliah', 'mata_kuliah', 'matakuliah', 'name', 'course name', 'course',
            'subject', 'nama mata pelajaran', 'course title', 'nama mata kuliah',
            'nama_mata_kuliah', 'judul', 'judul mk', 'nama kursus', 'mata_pelajaran',
        ],
        'sks' => [
            'sks', 'bobot', 'kredit', 'bobot sks', 'bobot_sks', 'credits', 'credit',
            'sks mk', 'jml sks', 'jumlah sks', 'sks teori', 'sks praktikum', 'sks_mk',
            'total sks', 'sks k', 'bobot kredit',
        ],
        'semester' => [
            'semester', 'smt', 'sem', 'tingkat', 'term', 'smstr', 'semester ke',
            'smt ke', 'sem ke', 'thn smt', 'sem.', 'smt.', 'semest',
        ],
        'kelas' => [
            'kelas', 'class', 'rombel', 'group', 'grup', 'kel', 'kls',
            'kelas a', 'kelas b', 'kelas c', 'kelas d', 'kelas e',
        ],
        'tipe' => [
            'tipe', 'type', 'jenis', 'kategori', 'sifat', 'status', 'wajib pilihan',
            'wajib/pilihan', 'sifat mk', 'sifat mata kuliah', 'kelompok', 'kurikulum',
            'w/p', 'w p', 'w_p', 'sifat_mk',
        ],
        'prodi' => [
            'prodi', 'program studi', 'program_studi', 'jurusan', 'study program',
            'departemen', 'dept', 'major', 'nama prodi', 'prodi/jurusan',
        ],
        'dosen_ketua' => [
            'dosen_ketua', 'dosen ketua', 'ketua', 'dosen ketua pengampu', 'pengampu ketua',
            'dosen utama', 'dosen1', 'dosen 1', 'dosen pengampu', 'dosen', 'pengampu',
            'lecturer', 'pengajar', 'nama dosen', 'dosen penanggung jawab', 'dosen pengampu 1',
            'pengampu 1', 'dosen pj', 'pj mk', 'penanggung jawab',
        ],
        'dosen_anggota' => [
            'dosen_anggota', 'dosen anggota', 'anggota', 'dosen pendamping',
            'pengampu anggota', 'dosen kedua', 'dosen2', 'dosen 2', 'pengampu 2',
            'team teaching', 'dosen partner', 'anggota pengampu', 'dosen pengampu 2',
        ],
        'jumlah_kelas' => [
            'jumlah_kelas', 'jumlah kelas', 'jml kelas', 'kelas', 'jml_kls', 'rombel',
            'jumlah rombel', 'jml rombel', 'paralel', 'jumlah paralel', 'kelas paralel',
            'kls', 'jml kls',
        ],
        'kapasitas_per_kelas' => [
            'kapasitas_per_kelas', 'kapasitas per kelas', 'kapasitas', 'kapasitas kelas',
            'kuota', 'daya tampung', 'target', 'seat', 'max siswa', 'kapasitas rombel',
            'kapasitas rombel/kelas',
        ],
        'butuh_lab' => [
            'butuh_lab', 'butuh lab', 'lab', 'laboratorium', 'perlu lab', 'need_lab',
            'praktikum', 'ada praktikum', 'butuh praktikum', 'lab.',
        ],
    ];

    public function templatePath(): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('mata_kuliah');

        $headers = self::HEADERS;
        $sheet->fromArray([$headers], null, 'A1');

        $sheet->fromArray([
<<<<<<< HEAD
            ['UNV101', 'Pendidikan Agama Islam', 2, 1, 'A', 'Wajib', 'Universitas', 'Dr. H. Ahmad Fauzi, M.Ag', '', 2, 40, 'Tidak'],
            ['UNV102', 'Pendidikan Pancasila', 2, 1, 'A', 'Wajib', 'Universitas', 'Drs. Bambang Wijaya, M.Pd', '', 2, 40, 'Tidak'],
            ['FST101', 'Kalkulus I', 3, 1, 'A', 'Wajib', 'Fakultas', 'Dr. Ir. Hendra Gunawan, M.Si', '', 2, 40, 'Tidak'],
            ['TIF101', 'Algoritma dan Pemrograman', 3, 1, 'A', 'Wajib', 'Teknik Informatika', 'Budi Santoso, M.Kom', 'Siti Aminah, M.Kom', 2, 35, 'Ya'],
            ['TIF102', 'Kecerdasan Buatan', 3, 5, 'A', 'Pilihan', 'Teknik Informatika', 'Budi Santoso, M.Kom', '', 1, 30, 'Tidak'],
            ['EEL101', 'Dasar Rangkaian Listrik', 3, 1, 'A', 'Wajib', 'Teknik Elektro', 'Ir. Dedi Supriyadi, M.T', '', 2, 35, 'Ya'],
        ], null, 'A2');

        // Style header row
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0860BE'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A1:L1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(24);

        foreach (range('A', 'L') as $col) {
=======
            ['TIF101', 'Algoritma dan Pemrograman', 3, 1, 'Wajib', 'Informatika', 'Budi Santoso', 'Siti Aminah', 2, 40, 'Tidak'],
            ['TIF102', 'Kecerdasan Buatan', 3, 5, 'Pilihan', 'Informatika', 'Budi Santoso', '', 1, 30, 'Ya'],
        ], null, 'A2');

        foreach (range('A', 'K') as $col) {
>>>>>>> d1c29ef9954c3c6e9b48137a65ff2e4deacab6d0
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $path = tempnam(sys_get_temp_dir(), 'mk_template_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    /**
     * Memuat spreadsheet dengan deteksi delimiter CSV yang akurat (koma, titik koma, atau tab).
     */
    public function loadSpreadsheet(string $path): Spreadsheet
    {
        $reader = IOFactory::createReaderForFile($path);
        if ($reader instanceof Csv) {
            $sample = file_get_contents($path, false, null, 0, 8192) ?: '';
            $commaCount = substr_count($sample, ',');
            $semicolonCount = substr_count($sample, ';');
            $tabCount = substr_count($sample, "\t");

            if ($semicolonCount > $commaCount && $semicolonCount > $tabCount) {
                $reader->setDelimiter(';');
            } elseif ($tabCount > $commaCount && $tabCount > $semicolonCount) {
                $reader->setDelimiter("\t");
            } else {
                $reader->setDelimiter(',');
            }
        }

        return $reader->load($path);
    }

    /**
     * Memilih sheet dengan data terbanyak dari spreadsheet.
     */
    public function getBestSheetData(Spreadsheet $spreadsheet): array
    {
        $bestRows = [];
        $maxScore = -1;

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $rows = $sheet->toArray(null, true, true, false);
            if (empty($rows)) {
                continue;
            }

            $score = 0;
            $nonEmptyCount = 0;
            foreach ($rows as $row) {
                if (! $this->isEmptyRow($row)) {
                    $nonEmptyCount++;
                    foreach ($row as $cell) {
                        if ($this->matchField((string) $cell) !== null) {
                            $score += 5;
                        }
                    }
                }
            }

            $totalScore = $score + $nonEmptyCount;
            if ($totalScore > $maxScore) {
                $maxScore = $totalScore;
                $bestRows = $rows;
            }
        }

        return ! empty($bestRows) ? $bestRows : $spreadsheet->getSheet(0)->toArray(null, true, true, false);
    }

    /**
     * Cari baris header pada data spreadsheet (memindai hingga 30 baris).
     */
    public function findHeaderRow(array $rows): int
    {
        $bestRowIndex = 0;
        $bestMatchCount = 0;
        $maxScan = min(30, count($rows));

        for ($i = 0; $i < $maxScan; $i++) {
            $row = $rows[$i] ?? [];
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $matches = 0;
            foreach ($row as $cell) {
                if ($this->matchField((string) $cell) !== null) {
                    $matches++;
                }
            }

            if ($matches > $bestMatchCount) {
                $bestMatchCount = $matches;
                $bestRowIndex = $i;
            }
        }

        return $bestRowIndex;
    }

    /**
     * Cocokkan teks header sel dengan target field.
     */
    public function matchField(string $cellValue): ?string
    {
        $normalized = $this->normalize($cellValue);
        if ($normalized === '') {
            return null;
        }

        // 1. Exact alias matching
        foreach ($this->aliases as $field => $names) {
            foreach ($names as $name) {
                if ($normalized === $this->normalize($name)) {
                    return $field;
                }
            }
        }

        // 2. Substring matching: jika header mengandung kata kunci alias
        // Urutkan kata kunci dari yang paling spesifik
        foreach ($this->aliases as $field => $names) {
            foreach ($names as $name) {
                $normAlias = $this->normalize($name);
                if (strlen($normAlias) >= 4 && str_contains($normalized, $normAlias)) {
                    return $field;
                }
            }
        }

        return null;
    }

    /**
     * Deteksi kolom secara cerdas berdasarkan nama header maupun inferensi isi konten (nilai data).
     */
    public function autoDetectHeaderMap(array $rows, int $headerRowIndex): array
    {
        $headerRow = $rows[$headerRowIndex] ?? [];
        $headerMap = [];
        $usedCols = [];

        // 1. Pemetaan dari baris header
        foreach (self::TARGET_FIELDS as $field => $info) {
            foreach ($headerRow as $col => $value) {
                if (in_array($col, $usedCols, true)) {
                    continue;
                }

                $matched = $this->matchField((string) $value);
                if ($matched === $field) {
                    $headerMap[$field] = $col;
                    $usedCols[] = $col;
                    break;
                }
            }
        }

        // Cek baris berikutnya jika header menggunakan merged cells (misal baris 2 & 3)
        if (isset($rows[$headerRowIndex + 1]) && ! empty(array_diff(['kode_mk', 'nama_mk', 'sks', 'semester'], array_keys($headerMap)))) {
            $secondHeaderRow = $rows[$headerRowIndex + 1];
            foreach (self::TARGET_FIELDS as $field => $info) {
                if (isset($headerMap[$field])) {
                    continue;
                }
                foreach ($secondHeaderRow as $col => $value) {
                    if (in_array($col, $usedCols, true)) {
                        continue;
                    }
                    $matched = $this->matchField((string) $value);
                    if ($matched === $field) {
                        $headerMap[$field] = $col;
                        $usedCols[] = $col;
                        break;
                    }
                }
            }
        }

        // 2. Analisis Inferensi Isi Konten (Value-Based Pattern Analysis)
        // Jika ada kolom wajib yang belum terdeteksi dari header:
        $dataRows = array_slice($rows, $headerRowIndex + 1, 20);
        $totalCols = ! empty($headerRow) ? count($headerRow) : 0;
        if ($totalCols === 0 && ! empty($dataRows)) {
            $totalCols = count($dataRows[0]);
        }

        $scores = [];
        for ($col = 0; $col < $totalCols; $col++) {
            if (in_array($col, $usedCols, true)) {
                continue;
            }

            $colScores = [
                'kode_mk' => 0,
                'nama_mk' => 0,
                'sks' => 0,
                'semester' => 0,
                'dosen_ketua' => 0,
                'tipe' => 0,
            ];

            $samples = [];
            foreach ($dataRows as $r) {
                $val = trim((string) ($r[$col] ?? ''));
                if ($val !== '') {
                    $samples[] = $val;
                }
            }

            if (empty($samples)) {
                continue;
            }

            foreach ($samples as $val) {
                $cleanDigits = preg_replace('/[^0-9]/', '', $val);

                // Pola Kode MK: 4-15 karakter alphanumeric, umumnya ada huruf dan angka
                if (preg_match('/^[A-Za-z]{2,6}[0-9]{2,6}$/i', $val) || preg_match('/^[A-Za-z0-9\-_]{4,15}$/i', $val)) {
                    if (! is_numeric($val) && strlen($val) <= 12) {
                        $colScores['kode_mk'] += 4;
                    }
                }

                // Pola SKS: angka 1-6 atau "3 SKS", "2 (1-1)"
                if ((is_numeric($val) && (int) $val >= 1 && (int) $val <= 4) || preg_match('/^[1-4]\s*(sks|kredit)?$/i', $val)) {
                    $colScores['sks'] += 3;
                }

                // Pola Semester: angka 1-8 atau "Smt 1", "Semester 3"
                if ((is_numeric($val) && (int) $val >= 1 && (int) $val <= 8) || preg_match('/^(smt|semester|sem)\s*[1-8]$/i', $val)) {
                    $colScores['semester'] += 3;
                }

                // Pola Nama MK: teks multi-kata atau string huruf > 6 karakter
                if (strlen($val) >= 6 && ! is_numeric($val) && preg_match('/[a-zA-Z]/', $val)) {
                    $colScores['nama_mk'] += 2;
                }

                // Pola Gelar Dosen
                if (preg_match('/(s\.kom|m\.kom|dr\.|prof\.|m\.t\.|s\.t\.|m\.sc|ph\.d)/i', $val)) {
                    $colScores['dosen_ketua'] += 5;
                }

                // Pola Tipe: Wajib / Pilihan / W / P
                if (in_array(strtolower($val), ['w', 'p', 'wajib', 'pilihan'], true)) {
                    $colScores['tipe'] += 4;
                }
            }

            $scores[$col] = $colScores;
        }

        // Tentukan kolom wajib yang masih belum ada
        foreach (['kode_mk', 'nama_mk', 'sks', 'semester', 'dosen_ketua', 'tipe'] as $neededField) {
            if (isset($headerMap[$neededField])) {
                continue;
            }

            $bestCol = null;
            $bestScore = 0;
            foreach ($scores as $col => $colScores) {
                if (in_array($col, $usedCols, true)) {
                    continue;
                }
                if ($colScores[$neededField] > $bestScore && $colScores[$neededField] >= 2) {
                    $bestScore = $colScores[$neededField];
                    $bestCol = $col;
                }
            }

            if ($bestCol !== null) {
                $headerMap[$neededField] = $bestCol;
                $usedCols[] = $bestCol;
            }
        }

        // Fallback cerdas: jika kode_mk atau nama_mk masih belum ketemu, ambil kolom teks pertama dan kedua
        if (! isset($headerMap['kode_mk']) || ! isset($headerMap['nama_mk'])) {
            $availableTextCols = [];
            for ($col = 0; $col < $totalCols; $col++) {
                if (! in_array($col, $usedCols, true)) {
                    $availableTextCols[] = $col;
                }
            }

            if (! isset($headerMap['kode_mk']) && count($availableTextCols) > 0) {
                $headerMap['kode_mk'] = array_shift($availableTextCols);
            }
            if (! isset($headerMap['nama_mk']) && count($availableTextCols) > 0) {
                $headerMap['nama_mk'] = array_shift($availableTextCols);
            }
        }

        return $headerMap;
    }

    /**
     * Membaca kolom, sampel data, dan analisis lengkap dari spreadsheet.
     */
    public function preview(string $path, array $defaults = []): array
    {
        $spreadsheet = $this->loadSpreadsheet($path);
        $rows = $this->getBestSheetData($spreadsheet);

        if (empty($rows)) {
            throw new \RuntimeException('File kosong.');
        }

        $headerRowIndex = $this->findHeaderRow($rows);
        $headerRow = $rows[$headerRowIndex] ?? [];
        $suggestedMapping = $this->autoDetectHeaderMap($rows, $headerRowIndex);

        $defaultProdi = trim((string) ($defaults['prodi'] ?? 'Informatika')) ?: 'Informatika';
        $defaultTipe = in_array($defaults['tipe'] ?? '', ['Wajib', 'Pilihan'], true) ? $defaults['tipe'] : 'Wajib';

        $analysis = $this->analyzeData($rows, $headerRowIndex, $suggestedMapping, $defaultProdi, $defaultTipe);

        $columns = [];
        foreach ($headerRow as $colIdx => $colName) {
            $name = trim((string) $colName);
            if ($name === '') {
                $name = 'Kolom '.($colIdx + 1);
            }

            $sample = '';
            for ($r = $headerRowIndex + 1; $r < min($headerRowIndex + 6, count($rows)); $r++) {
                if (isset($rows[$r][$colIdx]) && trim((string) $rows[$r][$colIdx]) !== '') {
                    $sample = (string) $rows[$r][$colIdx];
                    break;
                }
            }

            $columns[] = [
                'index' => $colIdx,
                'name' => $name,
                'sample' => $sample,
            ];
        }

        $previewRows = [];
        for ($r = $headerRowIndex + 1; $r < count($rows) && count($previewRows) < 5; $r++) {
            if (! $this->isEmptyRow($rows[$r])) {
                $previewRows[] = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $rows[$r]);
            }
        }

        return [
            'header_row_index' => $headerRowIndex + 1,
            'total_rows' => max(0, count($rows) - ($headerRowIndex + 1)),
            'columns' => $columns,
            'suggested_mapping' => $suggestedMapping,
            'preview_rows' => $previewRows,
            'target_fields' => self::TARGET_FIELDS,
            'summary' => $analysis['summary'],
            'items' => $analysis['items'],
            'prodis' => $analysis['prodis'],
        ];
    }

    /**
     * Menganalisis seluruh baris spreadsheet, mendeteksi anomali, tabel dosen, dan klasifikasi MK.
     */
    public function analyzeData(array $rows, int $headerRowIndex, array $headerMap, string $defaultProdi = 'Informatika', string $defaultTipe = 'Wajib'): array
    {
        $dataRows = array_slice($rows, $headerRowIndex + 1);

        // 1. Ekstrak kamus inisial/kode dosen jika ada (misal baris "Kode Dosen" - "Dosen")
        $lecturerMap = [];
        $isLecturerSection = false;
        foreach ($dataRows as $r) {
            $col1 = trim((string) ($r[$headerMap['kode_mk'] ?? 1] ?? ''));
            $col2 = trim((string) ($r[$headerMap['nama_mk'] ?? 2] ?? ''));

            if (strcasecmp($col1, 'kode dosen') === 0 || (strcasecmp($col1, 'kode dosen') === 0 && strcasecmp($col2, 'dosen') === 0)) {
                $isLecturerSection = true;

                continue;
            }

            if ($isLecturerSection && $col1 !== '' && $col2 !== '') {
                $lecturerMap[$col1] = $col2;
            }
        }

        // Cache nama dosen yang sudah ada di database untuk deteksi dosen baru
        $existingDosenNames = Dosen::pluck('nama')
            ->map(fn ($n) => strtolower(trim($n)))
            ->toArray();

        // Cache mata kuliah yang sudah ada di database (untuk deteksi kode beda tapi nama sama)
        $existingMks = MataKuliah::all()->keyBy('kode');
        $existingDetails = MataKuliahDetail::all()->keyBy('kode_mk');

        // Hitung jumlah kemunculan kode_mk
        $kodeMkCounts = [];
        $kodeCol = $headerMap['kode_mk'] ?? null;
        if ($kodeCol !== null) {
            foreach ($dataRows as $r) {
                $rawKode = trim((string) ($r[$kodeCol] ?? ''));
                if ($rawKode !== '' && ! in_array(strtolower($rawKode), ['kode mk', 'kode', 'kd mk', 'kdmk', 'kd_mk', 'no', 'kode dosen'], true) && ! isset($lecturerMap[$rawKode])) {
                    $kodeMkCounts[$rawKode] = ($kodeMkCounts[$rawKode] ?? 0) + 1;
                }
            }
        }

        $items = [];
        $seenInFile = [];
        $nameToCodeMap = [];
        $prodis = [];
        $counts = [
            'total' => 0,
            'dikenali' => 0,
            'duplikat' => 0,
            'perlu_verifikasi' => 0,
            'dosen_baru' => 0,
        ];

        $lastSemester = null;
        $inLecturerSection = false;

        foreach ($dataRows as $index => $row) {
            $line = $headerRowIndex + $index + 2;

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $checkKode = trim((string) ($row[$headerMap['kode_mk'] ?? 1] ?? ''));
            $checkNama = trim((string) ($row[$headerMap['nama_mk'] ?? 2] ?? ''));

            // Lewati jika baris ini merupakan sub-header di tengah sheet
            if (in_array(strtolower($checkKode), ['kode mk', 'kode', 'kd mk', 'kdmk', 'kd_mk', 'no'], true)
                || in_array(strtolower($checkNama), ['nama mk', 'nama mata kuliah', 'mata kuliah', 'matakuliah', 'pjmk'], true)) {
                continue;
            }

            // Lewati tabel inisial/daftar dosen (bukan mata kuliah!)
            if (strcasecmp($checkKode, 'kode dosen') === 0 || strcasecmp($checkNama, 'dosen') === 0) {
                $inLecturerSection = true;

                continue;
            }
            if ($inLecturerSection || (isset($lecturerMap[$checkKode]) && $checkNama === $lecturerMap[$checkKode])) {
                continue;
            }

            // Lacak nilai semester terakhir untuk mendukung merged/grouped cell pada Excel
            $semCol = $headerMap['semester'] ?? null;
            if ($semCol !== null && isset($row[$semCol]) && trim((string) $row[$semCol]) !== '') {
                $rawSemVal = trim((string) $row[$semCol]);
                if (! in_array(strtolower($rawSemVal), ['semester', 'smt', 'sem'], true)) {
                    $lastSemester = $rawSemVal;
                }
            }

            $data = $this->extractRowWithDefaults($row, $headerMap, $defaultProdi, $defaultTipe, $index + 1, $lastSemester, $kodeMkCounts);

            if (empty($data['kode_mk']) && empty($data['nama_mk'])) {
                continue;
            }

            // Normalisasi kode MK jika berupa placeholder 'MK Pilihan...' dan ada kode kurikulum resmi
            if (preg_match('/^mk\s*pilihan/i', $data['kode_mk'])) {
                $aliasMap = [
                    'ekonomi energi' => 'EEL13009',
                    'pengolahan citra digital' => 'EEL13008',
                    'radar dan navigasi' => 'EEL13007',
                ];
                $normNama = strtolower(trim($data['nama_mk']));
                if (isset($aliasMap[$normNama])) {
                    $data['kode_mk'] = $aliasMap[$normNama];
                }
            }

            // Multi-dosen splitting pada kolom dosen_ketua (jika berisi 'Dosen A / Dosen B')
            if (! empty($data['dosen_ketua']) && str_contains($data['dosen_ketua'], '/')) {
                $parts = array_map('trim', explode('/', $data['dosen_ketua'], 2));
                $data['dosen_ketua'] = $parts[0];
                if (empty($data['dosen_anggota']) && ! empty($parts[1])) {
                    $data['dosen_anggota'] = $parts[1];
                }
            }

            // Terjemahkan kode inisial dosen pengampu ke nama lengkap dosen jika ada di tabel referensi
            if (! empty($data['dosen_ketua']) && isset($lecturerMap[$data['dosen_ketua']])) {
                $data['dosen_ketua'] = $lecturerMap[$data['dosen_ketua']];
            }
            if (! empty($data['dosen_anggota']) && isset($lecturerMap[$data['dosen_anggota']])) {
                $data['dosen_anggota'] = $lecturerMap[$data['dosen_anggota']];
            }

            // Jika kode_mk kosong tapi nama_mk ada, buat kode otomatis
            if (empty($data['kode_mk'])) {
                $data['kode_mk'] = 'MK'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
            }

            $baseKode = preg_replace('/-[A-Za-z0-9]+$/', '', $data['kode_mk']);
            $kelasAsli = null;
            if (isset($data['kelas']) && $data['kelas'] !== null && trim((string) $data['kelas']) !== '') {
                $kelasAsli = strtoupper(trim((string) $data['kelas']));
                $fullKodeMk = $baseKode.'-'.$kelasAsli;
            } else {
                $fullKodeMk = $data['kode_mk'];
            }

            $kategori = $this->classifyKategori($baseKode, $data['tipe'] ?? 'Wajib', $data['nama_mk'] ?? '', $data['prodi'] ?? '');
            $pemilik = $this->resolvePemilik($kategori, $data['prodi'] ?? '');

            if (! empty($data['prodi']) && ! in_array($data['prodi'], $prodis, true)) {
                $prodis[] = $data['prodi'];
            }

            // Evaluasi status dan anomali
            $status = 'dikenali';
            $catatan = [];

            // 1. Cek Duplikat
            if (isset($seenInFile[$fullKodeMk])) {
                $status = 'duplikat';
                $catatan[] = "Duplikat di baris {$seenInFile[$fullKodeMk]} pada file ini.";
            } elseif ($existingDetails->has($fullKodeMk)) {
                $catatan[] = 'Data penawaran kelas ini sudah ada di database (akan diperbarui).';
            }
            $seenInFile[$fullKodeMk] = $line;

            // 2. Cek Nama Sama Tapi Kode Berbeda
            $normNamaKey = strtolower(trim($data['nama_mk']));
            if (isset($nameToCodeMap[$normNamaKey]) && $nameToCodeMap[$normNamaKey] !== $baseKode) {
                $status = ($status === 'duplikat') ? 'duplikat' : 'perlu_verifikasi';
                $catatan[] = "Nama MK sama dengan kode berbeda: {$baseKode} vs {$nameToCodeMap[$normNamaKey]}.";
            } else {
                $nameToCodeMap[$normNamaKey] = $baseKode;
            }

            // 3. Cek Dosen Terdaftar
            if (! empty($data['dosen_ketua']) && ! in_array(strtolower(trim($data['dosen_ketua'])), $existingDosenNames, true)) {
                $catatan[] = "Dosen ketua '{$data['dosen_ketua']}' belum terdaftar (akan otomatis dibuatkan).";
                $counts['dosen_baru']++;
            }
            if (! empty($data['dosen_anggota']) && ! in_array(strtolower(trim($data['dosen_anggota'])), $existingDosenNames, true)) {
                $catatan[] = "Dosen anggota '{$data['dosen_anggota']}' belum terdaftar (akan otomatis dibuatkan).";
            }

            // 4. Cek Semester & Prodi
            if (empty($data['semester']) || (int) $data['semester'] < 1 || (int) $data['semester'] > 8) {
                $status = 'perlu_verifikasi';
                $catatan[] = 'Semester tidak valid atau tidak terdeteksi.';
            }
            if (empty($data['prodi'])) {
                $status = 'perlu_verifikasi';
                $catatan[] = 'Program studi tidak terdeteksi.';
            }

            $counts['total']++;
            if ($status === 'dikenali') {
                $counts['dikenali']++;
            } elseif ($status === 'duplikat') {
                $counts['duplikat']++;
            } else {
                $counts['perlu_verifikasi']++;
            }

            $items[] = [
                'row_index' => $line,
                'kode_mk' => $fullKodeMk,
                'kode_induk' => $baseKode,
                'nama_mk' => $data['nama_mk'],
                'sks' => (int) $data['sks'],
                'semester' => (int) $data['semester'],
                'kelas' => $kelasAsli,
                'tipe' => $data['tipe'],
                'kategori' => $kategori,
                'prodi' => $data['prodi'],
                'pemilik' => $pemilik,
                'dosen_ketua' => $data['dosen_ketua'] ?? null,
                'dosen_anggota' => $data['dosen_anggota'] ?? null,
                'jumlah_kelas' => 1,
                'kapasitas_per_kelas' => $data['kapasitas_per_kelas'] ?? 40,
                'butuh_lab' => (bool) ($data['butuh_lab'] ?? false),
                'tanpa_ruangan' => (bool) ($data['tanpa_ruangan'] ?? false),
                'status' => $status,
                'catatan' => $catatan,
            ];
        }

        return [
            'summary' => $counts,
            'items' => $items,
            'prodis' => $prodis,
            'lecturer_map' => $lecturerMap,
        ];
    }

    /**
     * Klasifikasikan kategori mata kuliah secara otomatis dan objektif.
     */
    public function classifyKategori(string $kode, string $tipe, string $nama, ?string $prodi): string
    {
        $kodeUpper = strtoupper(trim($kode));
        $namaLower = strtolower(trim($nama));
        $tipeLower = strtolower(trim($tipe));

        if (str_starts_with($kodeUpper, 'UNV')) {
            return 'MK Universitas';
        }
        if (str_starts_with($kodeUpper, 'FST')) {
            return 'MK Fakultas';
        }

        if (str_starts_with($kodeUpper, 'INF') || str_contains($namaLower, 'informatika')) {
            return ($tipeLower === 'pilihan' || str_contains($namaLower, 'pilihan')) ? 'MK Prodi Pilihan' : 'MK Prodi Wajib';
        }

        if (str_starts_with($kodeUpper, 'EEL') || str_contains($namaLower, 'elektro')) {
            return ($tipeLower === 'pilihan' || str_contains($namaLower, 'pilihan')) ? 'MK Prodi Pilihan' : 'MK Prodi Wajib';
        }

        if ($tipeLower === 'pilihan' || str_contains($namaLower, 'pilihan')) {
            return 'MK Prodi Pilihan';
        }

        return 'MK Prodi Wajib';
    }

    /**
     * Tentukan pemilik resmi mata kuliah berdasarkan kategorinya.
     */
    public function resolvePemilik(string $kategori, ?string $prodi): string
    {
        return match ($kategori) {
            'MK Universitas' => 'Universitas',
            'MK Fakultas' => 'Fakultas',
            default => $prodi ?: 'Umum',
        };
    }

    /**
     * Impor data mata kuliah dari file spreadsheet atau array item yang telah diverifikasi.
     */
    public function import(string $path, array $mapping = [], array $defaults = [], array $verifiedItems = []): array
    {
        $defaultProdi = trim((string) ($defaults['prodi'] ?? 'Informatika')) ?: 'Informatika';
        $defaultTipe = in_array($defaults['tipe'] ?? '', ['Wajib', 'Pilihan'], true) ? $defaults['tipe'] : 'Wajib';

        if (! empty($verifiedItems)) {
            $items = $verifiedItems;
        } else {
            $spreadsheet = $this->loadSpreadsheet($path);
            $rows = $this->getBestSheetData($spreadsheet);

            if (empty($rows)) {
                return $this->result(0, 0, [], ['File kosong atau tidak memiliki data.']);
            }

            $headerRowIndex = $this->findHeaderRow($rows);
            $headerMap = ! empty($mapping) ? $mapping : $this->autoDetectHeaderMap($rows, $headerRowIndex);

            $analysis = $this->analyzeData($rows, $headerRowIndex, $headerMap, $defaultProdi, $defaultTipe);
            $items = $analysis['items'];
        }

        if (empty($items)) {
            return $this->result(0, 0, [], ['Tidak ada baris mata kuliah yang valid untuk diimpor.']);
        }

        $created = 0;
        $updated = 0;
        $failed = [];
        $seen = [];

        foreach ($items as $item) {
            $line = $item['row_index'] ?? 0;
            $fullKode = trim((string) ($item['kode_mk'] ?? ''));
            $namaMk = trim((string) ($item['nama_mk'] ?? ''));
            $sks = (int) ($item['sks'] ?? 3);
            $semester = (int) ($item['semester'] ?? 1);
            $kelas = ! empty($item['kelas']) ? strtoupper(trim((string) $item['kelas'])) : null;
            $tipe = in_array($item['tipe'] ?? '', ['Wajib', 'Pilihan'], true) ? $item['tipe'] : 'Wajib';
            $prodi = trim((string) ($item['prodi'] ?? $defaultProdi)) ?: $defaultProdi;
            $kategori = $item['kategori'] ?? $this->classifyKategori($fullKode, $tipe, $namaMk, $prodi);
            $pemilik = $item['pemilik'] ?? $this->resolvePemilik($kategori, $prodi);

            if (empty($fullKode) || empty($namaMk)) {
                continue;
            }

            $baseKode = preg_replace('/-[A-Za-z0-9]+$/', '', $fullKode);
<<<<<<< HEAD
            if ($kelas) {
                $fullKode = $baseKode.'-'.$kelas;
            }
=======
>>>>>>> d1c29ef9954c3c6e9b48137a65ff2e4deacab6d0

            // 1. Simpan / Sinkronkan Master MK Tunggal (hindari duplikasi master untuk MK Universitas / Fakultas)
            $masterMk = MataKuliah::firstOrCreate(
                ['kode' => $baseKode],
                [
                    'nama' => $namaMk,
                    'sks' => $sks,
                    'semester' => $semester,
                    'tipe' => in_array($kategori, ['MK Universitas', 'MK Fakultas']) ? 'Fakultas' : 'Prodi',
                    'kategori' => $kategori,
                    'pemilik' => $pemilik,
                    'kurikulum' => '2024',
                    'level_id' => 1,
                ]
            );

            // Update master jika ada data kategori / pemilik yang belum terisi
            if (! $masterMk->kategori || ! $masterMk->pemilik) {
                $masterMk->update([
                    'kategori' => $kategori,
                    'pemilik' => $pemilik,
                ]);
            }

            // 2. Resolve Dosen Pengampu
            $dosenKetuaId = $this->resolveDosen($item['dosen_ketua'] ?? null, $prodi);
            $dosenAnggotaId = $this->resolveDosen($item['dosen_anggota'] ?? null, $prodi);

            if ($dosenKetuaId && $dosenKetuaId === $dosenAnggotaId) {
                $dosenAnggotaId = null;
            }

            $payload = [
                'master_mk_id' => $masterMk->id,
                'nama_mk' => $namaMk,
                'sks' => $sks,
                'semester' => $semester,
                'kelas' => $kelas,
                'tipe' => $tipe,
                'kategori' => $kategori,
                'prodi' => $prodi,
                'dosen_ketua_id' => $dosenKetuaId,
                'dosen_anggota_id' => $dosenAnggotaId,
                'jumlah_kelas' => 1,
                'kapasitas_per_kelas' => isset($item['kapasitas_per_kelas']) ? max(10, min(200, (int) $item['kapasitas_per_kelas'])) : 40,
                'butuh_lab' => (bool) ($item['butuh_lab'] ?? false),
                'tanpa_ruangan' => (bool) ($item['tanpa_ruangan'] ?? false),
                'status_verifikasi' => $item['status'] ?? 'dikenali',
                'status_tawar' => true,
                'is_active' => true,
            ];

            $exists = MataKuliahDetail::where('kode_mk', $fullKode)->exists();
            MataKuliahDetail::updateOrCreate(['kode_mk' => $fullKode], $payload);

            $seen[$fullKode] = $line;
            if ($exists) {
                $updated++;
            } else {
                $created++;
            }
        }

        return $this->result($created, $updated, $seen, $failed);
    }

    protected function result(int $created, int $updated, array $seen, array $failed): array
    {
        return [
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'total' => $created + $updated + count($failed),
        ];
    }

    protected function extractRowWithDefaults(array $row, array $headerMap, string $defaultProdi, string $defaultTipe, int $rowIndex = 1, ?string $lastSemester = null, array $kodeMkCounts = []): array
    {
        $data = [];
        foreach ($headerMap as $field => $col) {
            $value = $row[$col] ?? null;
            $data[$field] = is_string($value) ? trim($value) : $value;
        }

        // Jika sel semester kosong tetapi ada semester dari baris sebelumnya (merged cell)
        if ((! isset($data['semester']) || $data['semester'] === null || trim((string) $data['semester']) === '') && $lastSemester !== null) {
            $data['semester'] = $lastSemester;
        }

        // Ekstraksi angka SKS secara cerdas (misal: "3 SKS", "3 (2-1)", "3", fallback: 3)
        $rawNama = (string) ($data['nama_mk'] ?? '');
        $isSkripsi = (bool) preg_match('/(skripsi|tugas\s*akhir)/i', $rawNama);

        if (isset($data['sks']) && $data['sks'] !== null && trim((string) $data['sks']) !== '') {
            $sksDigits = preg_replace('/[^0-9]/', '', (string) $data['sks']);
            if ($sksDigits !== '') {
                $num = (int) substr($sksDigits, 0, 1);
                $data['sks'] = ($num >= 1 && $num <= 6) ? $num : ($isSkripsi ? 6 : 3);
            } else {
                $data['sks'] = $isSkripsi ? 6 : 3;
            }
        } else {
            $data['sks'] = $isSkripsi ? 6 : 3;
        }

        // Ekstraksi angka Semester secara cerdas (termasuk angka romawi VI, VIII)
        $rawKode = (string) ($data['kode_mk'] ?? '');
        $combinedText = $rawNama.' '.$rawKode;

        if (isset($data['semester']) && $data['semester'] !== null && trim((string) $data['semester']) !== '') {
            $rawSem = (string) $data['semester'];
            if (str_contains(strtolower($rawSem), 'pilihan')) {
                $data['tipe'] = 'Pilihan';
                if (preg_match('/\(viii\)/i', $combinedText) || str_contains($rawSem, '8')) {
                    $data['semester'] = 8;
                } else {
                    $data['semester'] = 6;
                }
            } else {
                $semDigits = preg_replace('/[^0-9]/', '', $rawSem);
                if ($semDigits !== '') {
                    $num = (int) $semDigits;
                    $data['semester'] = ($num >= 1 && $num <= 8) ? $num : 1;
                } else {
                    $data['semester'] = 1;
                }
            }
        } elseif (preg_match('/\(viii\)/i', $combinedText)) {
            $data['semester'] = 8;
            $data['tipe'] = 'Pilihan';
        } elseif (preg_match('/\(vi\)/i', $combinedText)) {
            $data['semester'] = 6;
            $data['tipe'] = 'Pilihan';
        } else {
            $data['semester'] = 1;
        }

        // Normalisasi Tipe
        if (! empty($data['tipe'])) {
            $tipeRaw = strtolower(trim((string) $data['tipe']));
            if (str_starts_with($tipeRaw, 'p') || str_contains($tipeRaw, 'pilih')) {
                $data['tipe'] = 'Pilihan';
            } elseif (str_starts_with($tipeRaw, 'w') || str_contains($tipeRaw, 'wajib')) {
                $data['tipe'] = 'Wajib';
            } else {
                $data['tipe'] = $defaultTipe;
            }
        } else {
            $data['tipe'] = $defaultTipe;
        }

        // Normalisasi Prodi
        if (empty($data['prodi'])) {
            $data['prodi'] = $defaultProdi;
        }

        // Normalisasi Jumlah Kelas (mengutamakan total kemunculan baris kode_mk pada sheet jika ada kelas paralel)
        if (! empty($data['kode_mk']) && isset($kodeMkCounts[$data['kode_mk']])) {
            $data['jumlah_kelas'] = max(1, min(10, $kodeMkCounts[$data['kode_mk']]));
        } elseif (isset($data['jumlah_kelas']) && $data['jumlah_kelas'] !== null && trim((string) $data['jumlah_kelas']) !== '') {
            $jkDigits = preg_replace('/[^0-9]/', '', (string) $data['jumlah_kelas']);
            $data['jumlah_kelas'] = $jkDigits !== '' ? max(1, min(10, (int) $jkDigits)) : 1;
        } else {
            $data['jumlah_kelas'] = 1;
        }

        // Normalisasi Kapasitas
        if (isset($data['kapasitas_per_kelas']) && $data['kapasitas_per_kelas'] !== null && trim((string) $data['kapasitas_per_kelas']) !== '') {
            $kapDigits = preg_replace('/[^0-9]/', '', (string) $data['kapasitas_per_kelas']);
            $data['kapasitas_per_kelas'] = $kapDigits !== '' ? max(10, min(200, (int) $kapDigits)) : 40;
        } else {
            $data['kapasitas_per_kelas'] = 40;
        }

        // Deteksi Kebutuhan Laboratorium secara cerdas dari kolom tipe/jenis atau nama mata kuliah
        $rawTipeOrJenis = (string) ($row[$headerMap['tipe'] ?? -1] ?? ($data['tipe'] ?? ''));
        $isPraktikum = (bool) (preg_match('/(praktikum|lab|laboratorium)/i', $rawTipeOrJenis) || preg_match('/praktikum/i', $rawNama));
        $data['butuh_lab'] = $isPraktikum || $this->toBool($data['butuh_lab'] ?? null);

        // Deteksi Mata Kuliah Tanpa Ruangan Fisik (Skripsi, KKN, Kerja Praktik, Magang)
        $isTanpaRuangan = (bool) preg_match('/(skripsi|tugas\s*akhir|kukerta|kkn|kerja\s*praktek|kerja\s*praktik|magang|seminar\s*proposal)/i', $rawNama);
        $data['tanpa_ruangan'] = $isTanpaRuangan || $this->toBool($data['tanpa_ruangan'] ?? null);

        return $data;
    }

    protected function validateRow(array $data): array
    {
        $errors = [];

        if (empty($data['kode_mk'])) {
            $errors[] = 'kode_mk wajib diisi.';
        } elseif (strlen((string) $data['kode_mk']) > 20) {
            $errors[] = 'kode_mk maksimal 20 karakter.';
        }

        if (empty($data['nama_mk'])) {
            $errors[] = 'nama_mk wajib diisi.';
        }

        if (! is_numeric($data['sks'] ?? null) || (int) $data['sks'] < 1 || (int) $data['sks'] > 6) {
            $errors[] = 'sks harus 1-6.';
        }

        if (! in_array((int) ($data['semester'] ?? 0), range(1, 8), true)) {
            $errors[] = 'semester harus 1-8.';
        }

        if (! in_array($data['tipe'] ?? null, ['Wajib', 'Pilihan'], true)) {
            $errors[] = 'tipe harus Wajib atau Pilihan.';
        }

        if (empty($data['prodi'])) {
            $errors[] = 'prodi wajib diisi.';
        }

        if (isset($data['jumlah_kelas']) && $data['jumlah_kelas'] !== '' && ((int) $data['jumlah_kelas'] < 1 || (int) $data['jumlah_kelas'] > 10)) {
            $errors[] = 'jumlah_kelas harus 1-10.';
        }

        if (isset($data['kapasitas_per_kelas']) && $data['kapasitas_per_kelas'] !== '' && ((int) $data['kapasitas_per_kelas'] < 10 || (int) $data['kapasitas_per_kelas'] > 200)) {
            $errors[] = 'kapasitas_per_kelas harus 10-200.';
        }

        return $errors;
    }

    protected function resolveDosen($value, ?string $prodi): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);

        if (ctype_digit($value)) {
            $dosen = Dosen::find((int) $value);
            if ($dosen) {
                return $dosen->id;
            }
        }

        $dosen = Dosen::where('nama', $value)->first();
        if ($dosen) {
            return $dosen->id;
        }

        // Pencarian sebagian hanya jika panjang nama minimal 5 karakter agar inisial/kode dosen tidak bertabrakan
        if (strlen($value) >= 5) {
            $dosen = Dosen::where('nama', 'like', '%'.$value.'%')->first();
            if ($dosen) {
                return $dosen->id;
            }
        }

        if ($dosen) {
            return $dosen->id;
        }

        $created = Dosen::create([
            'nama' => $value,
            'inisial' => Dosen::generateInisialFromName($value),
            'jabatan' => 'Ketua & Anggota',
            'prodi' => $prodi,
            'is_active' => true,
        ]);

        return $created->id;
    }

    protected function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'ya', 'y', 'yes', 'true', 'lab', 'butuh', 'perlu'], true);
    }

    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[_\-\.\:\/\(\)]+/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }
}
