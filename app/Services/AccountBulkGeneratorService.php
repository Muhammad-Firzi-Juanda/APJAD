<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\AccountGenerationHistory;
use App\Models\Fakultas;
use App\Models\Jurusan;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AccountBulkGeneratorService
{
    public const TEMPLATE_HEADERS = [
        'Nama Lengkap',
        'Email',
        'NUPTK',
        'No. HP',
        'Fakultas',
        'Jurusan',
        'Program Studi',
        'Jabatan',
    ];

    public const STATUS_VALID = 'VALID';

    public const STATUS_WARNING = 'WARNING';

    public const STATUS_ERROR = 'ERROR';

    /**
     * Membuat file template Excel (.xlsx) sesuai spesifikasi Fix.md Bagian 4.1.
     * Tidak menyertakan kolom Role maupun Password.
     */
    public function templatePath(): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Akun APJAD');

        $sheet->fromArray([self::TEMPLATE_HEADERS], null, 'A1');

        // Contoh data dummy sesuai Fix.md Bagian 4.1 (sel kosong untuk tanda "-")
        $dummyRows = [
            ['Martaleli Ibet', 'martaleli@fttk.ac.id', '11223344', '82177642122', 'FTTK', '', '', 'Dekan'],
            ['Sitimah Laila', 'sitimah@fttk.ac.id', '73123790', '82377564312', 'FTTK', '', '', 'Wakil Dekan'],
            ['Rizal Ferdi', 'rizal@fttk.ac.id', '34562199', '81289903321', 'FTTK', 'Teknik Elektro dan Teknik Informatika', '', 'Ketua Jurusan'],
            ['Feri Irawan', 'feri@fttk.ac.id', '67784354', '85231890716', 'FTTK', 'Teknik Elektro dan Teknik Informatika', 'Teknik Informatika', 'Ketua Program Studi'],
            ['Holland Budi', 'holland@fttk.ac.id', '54378851', '82288904366', 'FTTK', 'Teknik Elektro dan Teknik Informatika', 'Teknik Elektro', 'Ketua Program Studi'],
        ];

        $sheet->fromArray($dummyRows, null, 'A2');

        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0860BE'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $path = tempnam(sys_get_temp_dir(), 'apjad_akun_template_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    /**
     * Membuat file Excel/CSV sementara untuk unduhan kredensial sementara satu kali (Fix.md Bagian 7).
     */
    public function createCredentialsDownloadFile(array $credentials): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Kredensial Sementara');

        $headers = [
            'Nama Lengkap',
            'Email',
            'NUPTK',
            'No. HP',
            'Jabatan',
            'Role',
            'Fakultas',
            'Jurusan',
            'Program Studi',
            'Password Sementara (Wajib Ganti Saat Login Pertama)',
        ];

        $sheet->fromArray([$headers], null, 'A1');

        $rows = [];
        foreach ($credentials as $item) {
            $rows[] = [
                $item['nama'] ?? '',
                $item['email'] ?? '',
                $item['nuptk'] ?? '',
                $item['no_hp'] ?? '',
                $item['jabatan'] ?? '',
                $item['role'] ?? '',
                $item['fakultas_nama'] ?? '',
                $item['jurusan_nama'] ?? '',
                $item['prodi_nama'] ?? '',
                $item['temporary_password'] ?? '',
            ];
        }

        if (! empty($rows)) {
            $sheet->fromArray($rows, null, 'A2');
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $path = tempnam(sys_get_temp_dir(), 'apjad_credentials_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    /**
     * Membaca file Excel/CSV tanpa mengeksekusi formula atau macro (Fix.md Bagian 4.2),
     * memvalidasi struktur kolom wajib, dan mengembalikan hasil evaluasi Preview.
     *
     * @throws \InvalidArgumentException
     */
    public function parseAndPreviewFile(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        // Jangan menjalankan formula atau macro dari file Excel (Fix.md Bagian 4.2)
        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }

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

        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getSheet(0);
        // Ambil nilai mentah tanpa mengkalkulasi formula ($calculateFormulas = false)
        $rawSheetRows = $sheet->toArray(null, false, false, false);

        if (empty($rawSheetRows)) {
            throw new \InvalidArgumentException('File Excel kosong atau tidak memiliki data.');
        }

        // Cari baris header
        $headerRowIndex = null;
        $columnMap = [];

        foreach ($rawSheetRows as $idx => $row) {
            if ($this->isRowEmpty($row)) {
                continue;
            }

            $detected = $this->detectHeaderColumns($row);
            if (isset($detected['nama']) || isset($detected['email']) || isset($detected['jabatan'])) {
                $headerRowIndex = $idx;
                $columnMap = $detected;
                break;
            }
        }

        if ($headerRowIndex === null) {
            throw new \InvalidArgumentException(
                'Struktur kolom wajib tidak ditemukan. Pastikan file memiliki header: Nama Lengkap, Email, dan Jabatan.'
            );
        }

        $missingRequiredHeaders = [];
        foreach (['nama' => 'Nama Lengkap', 'email' => 'Email', 'jabatan' => 'Jabatan'] as $key => $label) {
            if (! array_key_exists($key, $columnMap)) {
                $missingRequiredHeaders[] = $label;
            }
        }

        if (! empty($missingRequiredHeaders)) {
            throw new \InvalidArgumentException(
                'Kolom wajib tidak ditemukan pada file: '.implode(', ', $missingRequiredHeaders).'.'
            );
        }

        $extractedRows = [];
        $totalSheetRows = count($rawSheetRows);

        for ($i = $headerRowIndex + 1; $i < $totalSheetRows; $i++) {
            $row = $rawSheetRows[$i];
            if ($this->isRowEmpty($row)) {
                continue;
            }

            $extractedRows[] = [
                'row_number' => $i + 1,
                'nama' => $this->cleanCellValue($row[$columnMap['nama']] ?? ''),
                'email' => $this->cleanCellValue($row[$columnMap['email']] ?? ''),
                'nuptk' => isset($columnMap['nuptk']) ? $this->cleanCellValue($row[$columnMap['nuptk']] ?? '') : '',
                'no_hp' => isset($columnMap['no_hp']) ? $this->cleanCellValue($row[$columnMap['no_hp']] ?? '') : '',
                'fakultas' => isset($columnMap['fakultas']) ? $this->cleanCellValue($row[$columnMap['fakultas']] ?? '') : '',
                'jurusan' => isset($columnMap['jurusan']) ? $this->cleanCellValue($row[$columnMap['jurusan']] ?? '') : '',
                'prodi' => isset($columnMap['prodi']) ? $this->cleanCellValue($row[$columnMap['prodi']] ?? '') : '',
                'jabatan' => $this->cleanCellValue($row[$columnMap['jabatan']] ?? ''),
                'excluded' => false,
            ];
        }

        if (empty($extractedRows)) {
            throw new \InvalidArgumentException('Tidak ada baris data pengguna untuk diproses di dalam file.');
        }

        return $this->evaluatePreviewRows($extractedRows);
    }

    /**
     * Mengevaluasi dan memvalidasi daftar baris Preview (baik hasil parse Excel maupun hasil edit di Preview).
     * Mengembalikan struktur lengkap baris beserta ringkasan jumlah VALID, WARNING, dan ERROR.
     */
    public function evaluatePreviewRows(array $inputRows): array
    {
        $allFakultas = Fakultas::orderBy('nama')->get();
        $allJurusan = Jurusan::with('fakultas')->orderBy('nama')->get();
        $allProdi = Prodi::with(['fakultas', 'jurusan'])->orderBy('nama')->get();
        $activeFakultas = $allFakultas->filter(fn (Fakultas $f) => $f->isActive())->values();

        $evaluated = [];

        foreach ($inputRows as $idx => $raw) {
            $rowIndex = $idx;
            $rowNumber = (int) ($raw['row_number'] ?? ($idx + 1));
            $excluded = filter_var($raw['excluded'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $nama = trim((string) ($raw['nama'] ?? ''));
            $email = trim((string) ($raw['email'] ?? ''));
            $nuptk = $this->cleanCellValue($raw['nuptk'] ?? '');
            $noHp = $this->cleanCellValue($raw['no_hp'] ?? '');
            $rawJabatan = trim((string) ($raw['jabatan'] ?? ''));

            // Jangan pernah menerima formula Excel yang diawali "="
            if (str_starts_with($nama, '=')) {
                $nama = ltrim($nama, '=');
            }

            $errors = [];
            $warnings = [];

            // 1. Validasi Nama & Email
            if ($nama === '') {
                $errors[] = 'Nama Lengkap wajib diisi.';
            }

            if ($email === '') {
                $errors[] = 'Email wajib diisi.';
            } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Format email '{$email}' tidak valid.";
            }

            // 2. Deteksi Role Otomatis Berdasarkan Jabatan Resmi (Fix.md Bagian 4.3)
            $canonicalJabatan = AccountAuthorizationService::normalizeJabatan($rawJabatan);
            $autoRole = AccountAuthorizationService::mapJabatanToRole($canonicalJabatan);

            if ($rawJabatan === '') {
                $errors[] = 'Jabatan wajib diisi.';
            } elseif ($canonicalJabatan === null || $autoRole === null) {
                $errors[] = "Jabatan '{$rawJabatan}' tidak sesuai daftar resmi (Dekan, Wakil Dekan, Ketua Jurusan, Ketua Program Studi).";
            }

            // 3. Pemetaan Struktur Organisasi Otomatis (Fix.md Bagian 4.4)
            $fakultasId = ! empty($raw['fakultas_id']) ? (int) $raw['fakultas_id'] : null;
            $jurusanId = ! empty($raw['jurusan_id']) ? (int) $raw['jurusan_id'] : null;
            $prodiId = ! empty($raw['prodi_id']) ? (int) $raw['prodi_id'] : null;

            $fakultasText = $this->cleanCellValue($raw['fakultas'] ?? '');
            $jurusanText = $this->cleanCellValue($raw['jurusan'] ?? '');
            $prodiText = $this->cleanCellValue($raw['prodi'] ?? '');

            // Jika pengguna mengubah pilihan di Preview (mengirimkan _changed_field), prioritaskan relasi baru
            $changedField = (string) ($raw['_changed_field'] ?? '');

            $resolvedFakultas = null;
            $resolvedJurusan = null;
            $resolvedProdi = null;

            if ($autoRole === UserRole::FAKULTAS) {
                // Dekan & Wakil Dekan -> FAKULTAS. Jurusan & Prodi dikosongkan.
                if ($jurusanId !== null || $jurusanText !== '' || $prodiId !== null || $prodiText !== '') {
                    $warnings[] = 'Kolom Jurusan dan Program Studi dikosongkan otomatis untuk jabatan tingkat Fakultas.';
                }
                $jurusanId = null;
                $prodiId = null;

                if ($fakultasId !== null) {
                    $resolvedFakultas = $allFakultas->firstWhere('id', $fakultasId);
                } elseif ($fakultasText !== '') {
                    $resolvedFakultas = $this->matchFakultas($fakultasText, $allFakultas);
                }

                if ($resolvedFakultas === null) {
                    if ($fakultasId === null && $fakultasText === '') {
                        if ($activeFakultas->count() === 1) {
                            $resolvedFakultas = $activeFakultas->first();
                        } else {
                            $errors[] = 'Fakultas wajib dipilih karena terdapat beberapa Fakultas aktif.';
                        }
                    } else {
                        $label = $fakultasText !== '' ? $fakultasText : (string) $fakultasId;
                        $errors[] = "Fakultas '{$label}' tidak ditemukan pada Master Data Organisasi.";
                    }
                } elseif (! $resolvedFakultas->isActive()) {
                    $errors[] = "Fakultas '{$resolvedFakultas->nama}' berstatus nonaktif.";
                }
            } elseif ($autoRole === UserRole::JURUSAN) {
                // Ketua Jurusan -> JURUSAN. Fakultas otomatis dari induk Jurusan, Prodi dikosongkan.
                if ($prodiId !== null || $prodiText !== '') {
                    $warnings[] = 'Kolom Program Studi dikosongkan otomatis untuk jabatan Ketua Jurusan.';
                }
                $prodiId = null;

                if ($jurusanId !== null) {
                    $resolvedJurusan = $allJurusan->firstWhere('id', $jurusanId);
                } elseif ($jurusanText !== '') {
                    $resolvedJurusan = $this->matchJurusan($jurusanText, $allJurusan);
                }

                if ($resolvedJurusan === null) {
                    if ($jurusanId === null && $jurusanText === '') {
                        $errors[] = 'Jurusan wajib dipilih untuk jabatan Ketua Jurusan.';
                    } else {
                        $label = $jurusanText !== '' ? $jurusanText : (string) $jurusanId;
                        $errors[] = "Jurusan '{$label}' tidak ditemukan pada Master Data Organisasi.";
                    }
                } else {
                    if (! $resolvedJurusan->isActive()) {
                        $errors[] = "Jurusan '{$resolvedJurusan->nama}' berstatus nonaktif.";
                    }

                    // Validasi kecocokan Fakultas jika diisi dan bukan sedang mengganti Jurusan di Preview
                    if ($changedField !== 'jurusan') {
                        $explicitFakultas = null;
                        if ($fakultasId !== null) {
                            $explicitFakultas = $allFakultas->firstWhere('id', $fakultasId);
                            if (! $explicitFakultas) {
                                $errors[] = 'Fakultas yang dipilih tidak ditemukan.';
                            }
                        } elseif ($fakultasText !== '') {
                            $explicitFakultas = $this->matchFakultas($fakultasText, $allFakultas);
                            if (! $explicitFakultas) {
                                $errors[] = "Fakultas '{$fakultasText}' tidak ditemukan pada Master Data Organisasi.";
                            }
                        }

                        if ($explicitFakultas && (int) $explicitFakultas->id !== (int) $resolvedJurusan->fakultas_id) {
                            $errors[] = "Fakultas '{$explicitFakultas->nama}' tidak sesuai dengan induk Jurusan '{$resolvedJurusan->nama}'.";
                        }
                    }

                    // Fakultas otomatis diambil dari induk Jurusan
                    $resolvedFakultas = $allFakultas->firstWhere('id', (int) $resolvedJurusan->fakultas_id);
                    if ($resolvedFakultas && ! $resolvedFakultas->isActive()) {
                        $errors[] = "Fakultas induk '{$resolvedFakultas->nama}' berstatus nonaktif.";
                    }
                }
            } elseif ($autoRole === UserRole::PRODI) {
                // Ketua Program Studi -> PRODI. Jurusan & Fakultas otomatis dari relasi induk Prodi.
                if ($prodiId !== null) {
                    $resolvedProdi = $allProdi->firstWhere('id', $prodiId);
                } elseif ($prodiText !== '') {
                    $resolvedProdi = $this->matchProdi($prodiText, $allProdi);
                }

                if ($resolvedProdi === null) {
                    if ($prodiId === null && $prodiText === '') {
                        $errors[] = 'Program Studi wajib dipilih untuk jabatan Ketua Program Studi.';
                    } else {
                        $label = $prodiText !== '' ? $prodiText : (string) $prodiId;
                        $errors[] = "Program Studi '{$label}' tidak ditemukan pada Master Data Organisasi.";
                    }
                } else {
                    if (! $resolvedProdi->isActive()) {
                        $errors[] = "Program Studi '{$resolvedProdi->nama}' berstatus nonaktif.";
                    }

                    // Jika pengguna baru saja mengubah Program Studi di Preview, perbarui Jurusan & Fakultas otomatis dari induknya
                    if ($changedField !== 'prodi') {
                        $explicitJurusan = null;
                        if ($jurusanId !== null) {
                            $explicitJurusan = $allJurusan->firstWhere('id', $jurusanId);
                            if (! $explicitJurusan) {
                                $errors[] = 'Jurusan yang dipilih tidak ditemukan.';
                            }
                        } elseif ($jurusanText !== '') {
                            $explicitJurusan = $this->matchJurusan($jurusanText, $allJurusan);
                            if (! $explicitJurusan) {
                                $errors[] = "Jurusan '{$jurusanText}' tidak ditemukan pada Master Data Organisasi.";
                            }
                        }

                        if ($explicitJurusan && (int) $explicitJurusan->id !== (int) $resolvedProdi->jurusan_id) {
                            $errors[] = "Jurusan '{$explicitJurusan->nama}' tidak sesuai dengan induk Program Studi '{$resolvedProdi->nama}'.";
                        }

                        $explicitFakultas = null;
                        if ($fakultasId !== null) {
                            $explicitFakultas = $allFakultas->firstWhere('id', $fakultasId);
                            if (! $explicitFakultas) {
                                $errors[] = 'Fakultas yang dipilih tidak ditemukan.';
                            }
                        } elseif ($fakultasText !== '') {
                            $explicitFakultas = $this->matchFakultas($fakultasText, $allFakultas);
                            if (! $explicitFakultas) {
                                $errors[] = "Fakultas '{$fakultasText}' tidak ditemukan pada Master Data Organisasi.";
                            }
                        }

                        if ($explicitFakultas && (int) $explicitFakultas->id !== (int) $resolvedProdi->fakultas_id) {
                            $errors[] = "Fakultas '{$explicitFakultas->nama}' tidak sesuai dengan induk Program Studi '{$resolvedProdi->nama}'.";
                        }
                    }

                    // Fakultas & Jurusan otomatis diperoleh dari relasi induk Program Studi
                    $resolvedJurusan = $allJurusan->firstWhere('id', (int) $resolvedProdi->jurusan_id);
                    $resolvedFakultas = $allFakultas->firstWhere('id', (int) $resolvedProdi->fakultas_id);

                    if ($resolvedJurusan && ! $resolvedJurusan->isActive()) {
                        $errors[] = "Jurusan induk '{$resolvedJurusan->nama}' berstatus nonaktif.";
                    }
                    if ($resolvedFakultas && ! $resolvedFakultas->isActive()) {
                        $errors[] = "Fakultas induk '{$resolvedFakultas->nama}' berstatus nonaktif.";
                    }
                }
            }

            $finalFakultasId = $resolvedFakultas?->id;
            $finalJurusanId = $resolvedJurusan?->id;
            $finalProdiId = $resolvedProdi?->id;

            // 4. Validasi Duplikasi dengan Database (Email, NUPTK, & Konflik Jabatan Aktif - Fix.md Bagian 6)
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emailExists = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->exists();
                if ($emailExists) {
                    $errors[] = "Email '{$email}' sudah digunakan oleh akun yang terdaftar.";
                }
            }

            if ($nuptk !== '') {
                $nuptkExists = User::where('nuptk', $nuptk)->exists();
                if ($nuptkExists) {
                    $errors[] = "NUPTK '{$nuptk}' sudah digunakan oleh akun yang terdaftar.";
                }
            }

            if ($canonicalJabatan !== null && empty($errors)) {
                $conflictMsg = AccountAuthorizationService::findActiveJabatanConflictMessage(
                    $canonicalJabatan,
                    $finalFakultasId,
                    $finalJurusanId,
                    $finalProdiId
                );
                if ($conflictMsg !== null) {
                    $errors[] = $conflictMsg;
                }
            }

            $evaluated[$rowIndex] = [
                'index' => $rowIndex,
                'row_number' => $rowNumber,
                'excluded' => $excluded,
                'nama' => $nama,
                'email' => $email,
                'nuptk' => $nuptk,
                'no_hp' => $noHp,
                'jabatan_raw' => $rawJabatan,
                'jabatan' => $canonicalJabatan ?? $rawJabatan,
                'role' => $autoRole ?? '-',
                'fakultas_id' => $finalFakultasId,
                'fakultas_nama' => $resolvedFakultas ? "{$resolvedFakultas->nama} ({$resolvedFakultas->kode})" : ($fakultasText ?: '-'),
                'jurusan_id' => $finalJurusanId,
                'jurusan_nama' => $resolvedJurusan ? $resolvedJurusan->nama : ($jurusanText ?: '-'),
                'prodi_id' => $finalProdiId,
                'prodi_nama' => $resolvedProdi ? $resolvedProdi->nama : ($prodiText ?: '-'),
                'manual_password' => (string) ($raw['manual_password'] ?? ''),
                'manual_password_confirmation' => (string) ($raw['manual_password_confirmation'] ?? ''),
                'errors' => $errors,
                'warnings' => $warnings,
                'status' => self::STATUS_VALID,
                'messages' => [],
            ];
        }

        // 5. Pemeriksaan Duplikasi Antarbaris pada File yang Sama (Hanya Baris yang Tidak Dikecualikan - Fix.md Bagian 5 & 6)
        $emailTracker = [];
        $nuptkTracker = [];
        $singletonScopeTracker = [];

        foreach ($evaluated as $idx => $row) {
            if ($row['excluded']) {
                continue;
            }

            $emailKey = strtolower($row['email']);
            if ($emailKey !== '') {
                $emailTracker[$emailKey][] = $idx;
            }

            if ($row['nuptk'] !== '') {
                $nuptkTracker[$row['nuptk']][] = $idx;
            }

            $canonicalJab = AccountAuthorizationService::normalizeJabatan($row['jabatan']);
            if ($canonicalJab !== null && AccountAuthorizationService::isSingletonJabatan($canonicalJab)) {
                $scopeKey = match ($canonicalJab) {
                    AccountAuthorizationService::JABATAN_DEKAN => $row['fakultas_id'] ? "dekan:{$row['fakultas_id']}" : null,
                    AccountAuthorizationService::JABATAN_KETUA_JURUSAN => $row['jurusan_id'] ? "kajur:{$row['jurusan_id']}" : null,
                    AccountAuthorizationService::JABATAN_KETUA_PRODI => $row['prodi_id'] ? "kaprodi:{$row['prodi_id']}" : null,
                    default => null,
                };

                if ($scopeKey !== null) {
                    $singletonScopeTracker[$scopeKey][] = $idx;
                }
            }
        }

        foreach ($emailTracker as $emailKey => $indices) {
            if (count($indices) > 1) {
                foreach ($indices as $i) {
                    $evaluated[$i]['errors'][] = "Duplikasi email '{$evaluated[$i]['email']}' ditemukan antarbaris dalam file yang sama.";
                }
            }
        }

        foreach ($nuptkTracker as $nuptkVal => $indices) {
            if (count($indices) > 1) {
                foreach ($indices as $i) {
                    $evaluated[$i]['errors'][] = "Duplikasi NUPTK '{$nuptkVal}' ditemukan antarbaris dalam file yang sama.";
                }
            }
        }

        foreach ($singletonScopeTracker as $scopeKey => $indices) {
            if (count($indices) > 1) {
                foreach ($indices as $i) {
                    $evaluated[$i]['errors'][] = "Konflik penugasan jabatan '{$evaluated[$i]['jabatan']}' ganda pada unit organisasi yang sama di dalam file.";
                }
            }
        }

        // 6. Hitung Status Akhir Per Baris & Ringkasan (Fix.md Bagian 5)
        $validCount = 0;
        $warningCount = 0;
        $errorCount = 0;
        $excludedCount = 0;
        $includedCount = 0;

        $roleCounts = [
            UserRole::FAKULTAS => 0,
            UserRole::JURUSAN => 0,
            UserRole::PRODI => 0,
        ];

        foreach ($evaluated as $idx => &$row) {
            $row['errors'] = array_values(array_unique($row['errors']));
            $row['warnings'] = array_values(array_unique($row['warnings']));

            if (! empty($row['errors'])) {
                $row['status'] = self::STATUS_ERROR;
                $row['messages'] = $row['errors'];
            } elseif (! empty($row['warnings'])) {
                $row['status'] = self::STATUS_WARNING;
                $row['messages'] = $row['warnings'];
            } else {
                $row['status'] = self::STATUS_VALID;
                $row['messages'] = ['Data lengkap dan sesuai.'];
            }

            if ($row['excluded']) {
                $excludedCount++;
                continue;
            }

            $includedCount++;
            if ($row['status'] === self::STATUS_ERROR) {
                $errorCount++;
            } elseif ($row['status'] === self::STATUS_WARNING) {
                $warningCount++;
                if (isset($roleCounts[$row['role']])) {
                    $roleCounts[$row['role']]++;
                }
            } else {
                $validCount++;
                if (isset($roleCounts[$row['role']])) {
                    $roleCounts[$row['role']]++;
                }
            }
        }
        unset($row);

        return [
            'rows' => array_values($evaluated),
            'summary' => [
                'total' => count($evaluated),
                'included' => $includedCount,
                'excluded' => $excludedCount,
                'valid' => $validCount,
                'warning' => $warningCount,
                'error' => $errorCount,
                'count_fakultas' => $roleCounts[UserRole::FAKULTAS],
                'count_jurusan' => $roleCounts[UserRole::JURUSAN],
                'count_prodi' => $roleCounts[UserRole::PRODI],
                'can_commit' => $includedCount > 0 && $errorCount === 0,
            ],
        ];
    }

    /**
     * Mengeksekusi pembuatan akun massal setelah pengguna menekan ACC (Fix.md Bagian 7, 8, 9).
     */
    public function commitBatch(
        array $rawRows,
        string $passwordMethod,
        User $actor,
        ?string $fileName = null,
        ?string $idempotencyKey = null
    ): array {
        if (! AccountAuthorizationService::canAccessAccountManagement($actor) || ! $actor->isActive()) {
            abort(403, 'Akses ditolak: Anda tidak memiliki izin untuk melakukan generate akun massal.');
        }

        // Cek Idempotensi untuk mencegah klik ganda / pengiriman ulang (Fix.md Bagian 8 & 12.15)
        if (! empty($idempotencyKey)) {
            $existingHistory = AccountGenerationHistory::where('idempotency_key', $idempotencyKey)->first();
            if ($existingHistory) {
                return [
                    'already_processed' => true,
                    'success' => true,
                    'history' => $existingHistory,
                    'created_count' => $existingHistory->total_created,
                    'failed_count' => $existingHistory->total_failed,
                    'credentials_token' => null,
                    'errors' => $existingHistory->error_summary ?? [],
                ];
            }
        }

        $passwordMethod = in_array($passwordMethod, ['auto', 'manual'], true) ? $passwordMethod : 'auto';

        // Validasi ulang seluruh baris di backend (Fix.md Bagian 6, 8, 11)
        $evaluation = $this->evaluatePreviewRows($rawRows);
        $evaluatedRows = $evaluation['rows'];

        $includedRows = array_values(array_filter($evaluatedRows, fn (array $r) => ! $r['excluded']));
        if (empty($includedRows)) {
            return [
                'already_processed' => false,
                'success' => false,
                'message' => 'Tidak ada baris aktif yang dipilih untuk diproses.',
                'created_count' => 0,
                'failed_count' => 0,
                'credentials_token' => null,
                'errors' => ['Tidak ada baris aktif yang dipilih untuk diproses.'],
            ];
        }

        // Validasi password manual jika metode manual dipilih (Fix.md Bagian 7 & 12.14)
        $passwordErrors = [];
        if ($passwordMethod === 'manual') {
            foreach ($includedRows as $r) {
                $pwd = (string) ($r['manual_password'] ?? '');
                $pwdConf = (string) ($r['manual_password_confirmation'] ?? '');
                $rowLabel = "Baris #{$r['row_number']} ({$r['nama']})";

                if ($pwd === '') {
                    $passwordErrors[] = "{$rowLabel}: Password manual wajib diisi.";
                } elseif (strlen($pwd) < 6) {
                    $passwordErrors[] = "{$rowLabel}: Password manual minimal 6 karakter.";
                } elseif ($pwd !== $pwdConf) {
                    $passwordErrors[] = "{$rowLabel}: Konfirmasi password manual tidak cocok.";
                }
            }
        }

        $validationErrors = [];
        foreach ($includedRows as $r) {
            if ($r['status'] === self::STATUS_ERROR) {
                $rowLabel = "Baris #{$r['row_number']} ({$r['nama']})";
                foreach ($r['errors'] as $err) {
                    $validationErrors[] = "{$rowLabel}: {$err}";
                }
            }
        }

        $allErrors = array_merge($validationErrors, $passwordErrors);

        // Apabila masih terdapat baris aktif berstatus ERROR atau password manual tidak valid, tolak ACC
        if (! empty($allErrors)) {
            $history = AccountGenerationHistory::create([
                'user_id' => $actor->id,
                'actor_name' => $actor->nama,
                'actor_role' => $actor->role,
                'file_name' => $fileName ?: 'Input Preview Massal',
                'idempotency_key' => null,
                'password_method' => $passwordMethod,
                'total_processed' => count($includedRows),
                'total_created' => 0,
                'total_failed' => count($includedRows),
                'count_fakultas' => 0,
                'count_jurusan' => 0,
                'count_prodi' => 0,
                'error_summary' => $allErrors,
                'created_accounts_summary' => [],
            ]);

            return [
                'already_processed' => false,
                'success' => false,
                'history' => $history,
                'message' => 'Terdapat data tidak valid atau konflik yang belum diselesaikan.',
                'created_count' => 0,
                'failed_count' => count($includedRows),
                'credentials_token' => null,
                'errors' => $allErrors,
            ];
        }

        $createdAccountsSummary = [];
        $temporaryCredentials = [];
        $usedPasswords = [];
        $roleCounters = [
            UserRole::FAKULTAS => 0,
            UserRole::JURUSAN => 0,
            UserRole::PRODI => 0,
        ];

        $history = DB::transaction(function () use (
            $includedRows,
            $passwordMethod,
            $actor,
            $fileName,
            $idempotencyKey,
            &$createdAccountsSummary,
            &$temporaryCredentials,
            &$usedPasswords,
            &$roleCounters
        ) {
            foreach ($includedRows as $r) {
                // Pastikan tidak pernah membuat Admin atau Super Admin (Fix.md Bagian 1 & 11)
                $resolvedScope = AccountAuthorizationService::resolveScopeForJabatan(
                    (string) $r['jabatan'],
                    $r['fakultas_id'] ? (int) $r['fakultas_id'] : null,
                    $r['jurusan_id'] ? (int) $r['jurusan_id'] : null,
                    $r['prodi_id'] ? (int) $r['prodi_id'] : null,
                    true
                );

                if (! AccountAuthorizationService::canCreateRole($actor, $resolvedScope['role'])) {
                    abort(403, 'Akses ditolak: Pembuatan akun dengan role ini tidak diizinkan.');
                }

                // Cek ulang konflik jabatan aktif di dalam transaksi
                AccountAuthorizationService::validateActiveJabatanConflict(
                    $resolvedScope['jabatan'],
                    $resolvedScope['fakultas_id'],
                    $resolvedScope['jurusan_id'],
                    $resolvedScope['prodi_id'],
                    'aktif'
                );

                if ($passwordMethod === 'auto') {
                    $plainPassword = $this->generateUniqueStrongPassword($usedPasswords);
                    $usedPasswords[$plainPassword] = true;
                    $mustChangePassword = true;
                } else {
                    $plainPassword = (string) $r['manual_password'];
                    $mustChangePassword = false;
                }

                $createdUser = User::create([
                    'nama' => $r['nama'],
                    'email' => $r['email'],
                    'nuptk' => $r['nuptk'] !== '' ? $r['nuptk'] : null,
                    'no_hp' => $r['no_hp'] !== '' ? $r['no_hp'] : null,
                    'role' => $resolvedScope['role'],
                    'jabatan' => $resolvedScope['jabatan'],
                    'password' => Hash::make($plainPassword),
                    'fakultas_id' => $resolvedScope['fakultas_id'],
                    'jurusan_id' => $resolvedScope['jurusan_id'],
                    'prodi_id' => $resolvedScope['prodi_id'],
                    'status' => 'aktif',
                    'must_change_password' => $mustChangePassword,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);

                $roleCounters[$createdUser->role] = ($roleCounters[$createdUser->role] ?? 0) + 1;

                // Simpan ringkasan akun TANPA password plaintext untuk riwayat generate (Fix.md Bagian 7 & 9)
                $accountSummary = [
                    'id' => $createdUser->id,
                    'nama' => $createdUser->nama,
                    'email' => $createdUser->email,
                    'nuptk' => $createdUser->nuptk,
                    'no_hp' => $createdUser->no_hp,
                    'jabatan' => $createdUser->jabatan,
                    'role' => $createdUser->role,
                    'fakultas_nama' => $r['fakultas_nama'],
                    'jurusan_nama' => $r['jurusan_nama'],
                    'prodi_nama' => $r['prodi_nama'],
                ];
                $createdAccountsSummary[] = $accountSummary;

                if ($passwordMethod === 'auto') {
                    $temporaryCredentials[] = array_merge($accountSummary, [
                        'temporary_password' => $plainPassword,
                    ]);
                }
            }

            return AccountGenerationHistory::create([
                'user_id' => $actor->id,
                'actor_name' => $actor->nama,
                'actor_role' => $actor->role,
                'file_name' => $fileName ?: 'Upload Excel Massal',
                'idempotency_key' => ! empty($idempotencyKey) ? $idempotencyKey : Str::uuid()->toString(),
                'password_method' => $passwordMethod,
                'total_processed' => count($includedRows),
                'total_created' => count($createdAccountsSummary),
                'total_failed' => 0,
                'count_fakultas' => $roleCounters[UserRole::FAKULTAS] ?? 0,
                'count_jurusan' => $roleCounters[UserRole::JURUSAN] ?? 0,
                'count_prodi' => $roleCounters[UserRole::PRODI] ?? 0,
                'error_summary' => [],
                'created_accounts_summary' => $createdAccountsSummary,
            ]);
        });

        $credentialsToken = null;
        if ($passwordMethod === 'auto' && ! empty($temporaryCredentials)) {
            $credentialsToken = Str::random(40);
            // Simpan kredensial sementara di cache terenkripsi selama 15 menit untuk 1x unduhan (Fix.md Bagian 7)
            Cache::put(
                "apjad_temp_credentials_{$actor->id}_{$credentialsToken}",
                encrypt($temporaryCredentials),
                now()->addMinutes(15)
            );
        }

        Log::info('Generate akun massal berhasil dijalankan', [
            'history_id' => $history->id,
            'actor_id' => $actor->id,
            'actor_role' => $actor->role,
            'total_created' => $history->total_created,
            'password_method' => $passwordMethod,
        ]);

        return [
            'already_processed' => false,
            'success' => true,
            'history' => $history,
            'created_count' => $history->total_created,
            'failed_count' => 0,
            'credentials_token' => $credentialsToken,
            'temporary_credentials' => $temporaryCredentials,
            'errors' => [],
        ];
    }

    /**
     * Menghasilkan password acak yang kuat dan unik untuk setiap akun pada mode Password Otomatis (Fix.md Bagian 7).
     */
    public function generateUniqueStrongPassword(array $usedPasswords = []): string
    {
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghijkmnopqrstuvwxyz';
        $digits = '23456789';
        $symbols = '@#$!%*?&';

        do {
            $chars = [
                $upper[random_int(0, strlen($upper) - 1)],
                $upper[random_int(0, strlen($upper) - 1)],
                $lower[random_int(0, strlen($lower) - 1)],
                $lower[random_int(0, strlen($lower) - 1)],
                $digits[random_int(0, strlen($digits) - 1)],
                $digits[random_int(0, strlen($digits) - 1)],
                $symbols[random_int(0, strlen($symbols) - 1)],
            ];

            $pool = $upper.$lower.$digits;
            for ($i = 0; $i < 5; $i++) {
                $chars[] = $pool[random_int(0, strlen($pool) - 1)];
            }

            shuffle($chars);
            $password = implode('', $chars);
        } while (isset($usedPasswords[$password]));

        return $password;
    }

    /**
     * Mencocokkan Fakultas berdasarkan kode atau nama dari Master Data Organisasi di database.
     */
    public function matchFakultas(string $rawInput, Collection $fakultasList): ?Fakultas
    {
        $clean = $this->normalizeOrgText($rawInput, ['fakultas']);
        if ($clean === '') {
            return null;
        }

        foreach ($fakultasList as $fak) {
            if (strtolower(trim($fak->kode)) === $clean || strtolower(trim($fak->nama)) === $clean) {
                return $fak;
            }
            if ($this->normalizeOrgText($fak->nama, ['fakultas']) === $clean) {
                return $fak;
            }
        }

        return null;
    }

    /**
     * Mencocokkan Jurusan berdasarkan kode atau nama dari Master Data Organisasi di database.
     */
    public function matchJurusan(string $rawInput, Collection $jurusanList): ?Jurusan
    {
        $clean = $this->normalizeOrgText($rawInput, ['jurusan', 'departemen']);
        if ($clean === '') {
            return null;
        }

        foreach ($jurusanList as $jur) {
            if (strtolower(trim($jur->kode)) === $clean || strtolower(trim($jur->nama)) === $clean) {
                return $jur;
            }
            if ($this->normalizeOrgText($jur->nama, ['jurusan', 'departemen']) === $clean) {
                return $jur;
            }
        }

        // Fallback toleransi penulisan "Teknik Elektro dan Teknik Informatika" <-> "Teknik Elektro dan Informatika"
        $compactInput = str_replace(' dan teknik ', ' dan ', $clean);
        foreach ($jurusanList as $jur) {
            $compactTarget = str_replace(' dan teknik ', ' dan ', $this->normalizeOrgText($jur->nama, ['jurusan', 'departemen']));
            if ($compactInput !== '' && $compactInput === $compactTarget) {
                return $jur;
            }
        }

        return null;
    }

    /**
     * Mencocokkan Program Studi berdasarkan kode atau nama (dengan atau tanpa jenjang S1/D3/D4/S2) dari database.
     */
    public function matchProdi(string $rawInput, Collection $prodiList): ?Prodi
    {
        $clean = $this->normalizeOrgText($rawInput, ['program studi', 'prodi', 's1', 's2', 'd3', 'd4']);
        if ($clean === '') {
            return null;
        }

        foreach ($prodiList as $prd) {
            if (strtolower(trim($prd->kode)) === $clean || strtolower(trim($prd->nama)) === $clean) {
                return $prd;
            }
            if ($this->normalizeOrgText($prd->nama, ['program studi', 'prodi', 's1', 's2', 'd3', 'd4']) === $clean) {
                return $prd;
            }
        }

        return null;
    }

    private function normalizeOrgText(string $value, array $stripPrefixes = []): string
    {
        $norm = trim((string) preg_replace('/\s+/', ' ', strtolower(trim($value))));
        foreach ($stripPrefixes as $prefix) {
            $p = strtolower(trim($prefix)).' ';
            if (str_starts_with($norm, $p)) {
                $norm = trim(substr($norm, strlen($p)));
            }
        }

        return $norm;
    }

    private function detectHeaderColumns(array $row): array
    {
        $map = [];
        $aliases = [
            'nama' => ['nama lengkap', 'nama', 'full name', 'nama_lengkap'],
            'email' => ['email', 'e-mail', 'alamat email'],
            'nuptk' => ['nuptk', 'nip', 'nidn'],
            'no_hp' => ['no. hp', 'no hp', 'nomor hp', 'no_hp', 'no telp', 'telepon', 'hp'],
            'fakultas' => ['fakultas', 'faculty'],
            'jurusan' => ['jurusan', 'department', 'departemen'],
            'prodi' => ['program studi', 'prodi', 'program_studi', 'study program'],
            'jabatan' => ['jabatan', 'posisi', 'position'],
        ];

        foreach ($row as $colIdx => $cellVal) {
            $norm = trim((string) preg_replace('/\s+/', ' ', strtolower(trim((string) $cellVal))));
            if ($norm === '') {
                continue;
            }

            foreach ($aliases as $field => $validHeaders) {
                if (! isset($map[$field]) && in_array($norm, $validHeaders, true)) {
                    $map[$field] = $colIdx;
                    break;
                }
            }
        }

        return $map;
    }

    private function cleanCellValue(mixed $value): string
    {
        $str = trim((string) ($value ?? ''));
        if (in_array($str, ['-', '—', '–', 'N/A', 'n/a'], true)) {
            return '';
        }

        return $str;
    }

    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if ($this->cleanCellValue($cell) !== '') {
                return false;
            }
        }

        return true;
    }
}

