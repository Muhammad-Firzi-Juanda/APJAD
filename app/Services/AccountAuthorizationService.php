<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Fakultas;
use App\Models\Jurusan;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AccountAuthorizationService
{
    public const JABATAN_DEKAN = 'Dekan';

    public const JABATAN_WAKIL_DEKAN = 'Wakil Dekan';

    public const JABATAN_KETUA_JURUSAN = 'Ketua Jurusan';

    public const JABATAN_KETUA_PRODI = 'Ketua Program Studi';

    public const OFFICIAL_JABATAN_ROLE_MAP = [
        self::JABATAN_DEKAN => UserRole::FAKULTAS,
        self::JABATAN_WAKIL_DEKAN => UserRole::FAKULTAS,
        self::JABATAN_KETUA_JURUSAN => UserRole::JURUSAN,
        self::JABATAN_KETUA_PRODI => UserRole::PRODI,
    ];

    /**
     * Daftar 4 jabatan resmi yang diterima sistem APJAD (Fix.md Bagian 3, 4.3, 13).
     *
     * @return list<string>
     */
    public static function officialJabatanList(): array
    {
        return [
            self::JABATAN_DEKAN,
            self::JABATAN_WAKIL_DEKAN,
            self::JABATAN_KETUA_JURUSAN,
            self::JABATAN_KETUA_PRODI,
        ];
    }

    /**
     * Pemetaan jabatan resmi ke role sistem (Fix.md Bagian 3 & 4.3).
     *
     * @return array<string, string>
     */
    public static function jabatanRoleMap(): array
    {
        return [
            'dekan' => UserRole::FAKULTAS,
            'wakil dekan' => UserRole::FAKULTAS,
            'ketua jurusan' => UserRole::JURUSAN,
            'ketua program studi' => UserRole::PRODI,
        ];
    }

    /**
     * Normalisasi teks jabatan resmi (mengabaikan huruf besar/kecil dan spasi berlebih).
     * Tidak menerima alias seperti Kajur, Kepala Jurusan, Kaprodi, atau Kepala Prodi.
     */
    public static function normalizeJabatan(?string $rawJabatan): ?string
    {
        if ($rawJabatan === null) {
            return null;
        }

        $normalized = trim((string) preg_replace('/\s+/', ' ', strtolower(trim($rawJabatan))));
        if ($normalized === '') {
            return null;
        }

        return match ($normalized) {
            'dekan' => self::JABATAN_DEKAN,
            'wakil dekan' => self::JABATAN_WAKIL_DEKAN,
            'ketua jurusan' => self::JABATAN_KETUA_JURUSAN,
            'ketua program studi' => self::JABATAN_KETUA_PRODI,
            default => null,
        };
    }

    /**
     * Menentukan role otomatis berdasarkan jabatan resmi (Fix.md Bagian 3 & 4.3).
     */
    public static function mapJabatanToRole(?string $rawJabatan): ?string
    {
        $canonical = self::normalizeJabatan($rawJabatan);
        if ($canonical === null) {
            return null;
        }

        return self::jabatanRoleMap()[strtolower($canonical)] ?? null;
    }

    /**
     * Memeriksa apakah jabatan hanya boleh memiliki satu pemegang aktif pada satu scope organisasi (Fix.md Bagian 6).
     * Wakil Dekan diperbolehkan lebih dari satu orang.
     */
    public static function isSingletonJabatan(string $canonicalJabatan): bool
    {
        return in_array($canonicalJabatan, [
            self::JABATAN_DEKAN,
            self::JABATAN_KETUA_JURUSAN,
            self::JABATAN_KETUA_PRODI,
        ], true);
    }

    /**
     * Validasi konflik penugasan jabatan aktif dalam scope organisasi yang sama (Fix.md Bagian 6).
     *
     * @throws ValidationException
     */
    public static function validateActiveJabatanConflict(
        ?string $jabatan,
        ?int $fakultasId,
        ?int $jurusanId,
        ?int $prodiId,
        string $status = 'aktif',
        ?int $ignoreUserId = null
    ): void {
        if ($status !== 'aktif' || empty($jabatan)) {
            return;
        }

        $canonical = self::normalizeJabatan($jabatan);
        if ($canonical === null || ! self::isSingletonJabatan($canonical)) {
            return;
        }

        $conflictError = self::findActiveJabatanConflictMessage($canonical, $fakultasId, $jurusanId, $prodiId, $ignoreUserId);
        if ($conflictError !== null) {
            throw ValidationException::withMessages([
                'jabatan' => [$conflictError],
            ]);
        }
    }

    /**
     * Mengembalikan pesan error apabila terdapat pemegang jabatan aktif yang bertabrakan pada scope yang sama.
     */
    public static function findActiveJabatanConflictMessage(
        string $canonicalJabatan,
        ?int $fakultasId,
        ?int $jurusanId,
        ?int $prodiId,
        ?int $ignoreUserId = null
    ): ?string {
        if (! self::isSingletonJabatan($canonicalJabatan)) {
            return null;
        }

        $query = User::query()
            ->whereIn('status', ['aktif', 'ACTIVE', 'active'])
            ->whereRaw('LOWER(TRIM(jabatan)) = ?', [strtolower($canonicalJabatan)]);

        if ($ignoreUserId !== null) {
            $query->where('id', '!=', $ignoreUserId);
        }

        if ($canonicalJabatan === self::JABATAN_DEKAN && ! empty($fakultasId)) {
            $existing = (clone $query)->where('fakultas_id', $fakultasId)->first();
            if ($existing) {
                $fakName = Fakultas::find($fakultasId)?->nama ?? 'Fakultas ini';

                return "Jabatan Dekan aktif untuk {$fakName} sudah dipegang oleh {$existing->nama} ({$existing->email}).";
            }
        }

        if ($canonicalJabatan === self::JABATAN_KETUA_JURUSAN && ! empty($jurusanId)) {
            $existing = (clone $query)->where('jurusan_id', $jurusanId)->first();
            if ($existing) {
                $jurName = Jurusan::find($jurusanId)?->nama ?? 'Jurusan ini';

                return "Jabatan Ketua Jurusan aktif untuk {$jurName} sudah dipegang oleh {$existing->nama} ({$existing->email}).";
            }
        }

        if ($canonicalJabatan === self::JABATAN_KETUA_PRODI && ! empty($prodiId)) {
            $existing = (clone $query)->where('prodi_id', $prodiId)->first();
            if ($existing) {
                $prodiName = Prodi::find($prodiId)?->nama ?? 'Program Studi ini';

                return "Jabatan Ketua Program Studi aktif untuk {$prodiName} sudah dipegang oleh {$existing->nama} ({$existing->email}).";
            }
        }

        return null;
    }

    /**
     * Memeriksa apakah user dapat melihat/mengakses modul manajemen akun.
     */
    public static function canAccessAccountManagement(User $actor): bool
    {
        return in_array($actor->role, [UserRole::SUPER_ADMIN, UserRole::ADMIN], true);
    }

    /**
     * Memeriksa apakah user dapat melihat Master Data Organisasi.
     * Hanya ADMIN yang dapat mengakses dan mengelola Master Data Organisasi.
     */
    public static function canViewMasterData(User $actor): bool
    {
        return $actor->role === UserRole::ADMIN && $actor->isActive();
    }

    /**
     * Memeriksa apakah user dapat mengelola (CRUD / Toggle Status) Master Data Organisasi.
     * Hanya ADMIN yang diizinkan mengubah Master Data Organisasi.
     */
    public static function canManageMasterData(User $actor): bool
    {
        return $actor->role === UserRole::ADMIN && $actor->isActive();
    }

    /**
     * Alias kompatibilitas untuk pengelolaan Master Data Organisasi.
     */
    public static function canAccessMasterData(User $actor): bool
    {
        return self::canManageMasterData($actor);
    }

    /**
     * Memeriksa apakah aktor memiliki hak untuk melihat role tertentu di daftar akun.
     */
    public static function canViewRole(User $actor, string $targetRole): bool
    {
        if (! self::canAccessAccountManagement($actor) || ! $actor->isActive()) {
            return false;
        }

        if ($actor->role === UserRole::SUPER_ADMIN) {
            return in_array($targetRole, [UserRole::FAKULTAS, UserRole::JURUSAN, UserRole::PRODI], true);
        }

        if ($actor->role === UserRole::ADMIN) {
            return in_array($targetRole, [UserRole::SUPER_ADMIN, UserRole::FAKULTAS, UserRole::JURUSAN, UserRole::PRODI], true);
        }

        return false;
    }

    /**
     * Memeriksa apakah aktor boleh membuat akun dengan role target.
     * SUPER ADMIN dan ADMIN hanya boleh membuat akun operasional: Fakultas, Jurusan, Prodi.
     * Keduanya tidak boleh membuat akun ADMIN atau SUPER ADMIN tambahan.
     */
    public static function canCreateRole(User $actor, string $targetRole): bool
    {
        if (! self::canAccessAccountManagement($actor) || ! $actor->isActive()) {
            return false;
        }

        if (in_array($targetRole, [UserRole::SUPER_ADMIN, UserRole::ADMIN], true)) {
            return false;
        }

        return in_array($targetRole, [UserRole::FAKULTAS, UserRole::JURUSAN, UserRole::PRODI], true);
    }

    /**
     * Memeriksa apakah aktor boleh mengedit akun target.
     * - Super Admin dan Admin dapat mengedit akun operasional (Fakultas, Jurusan, Prodi).
     * - Akun Admin dan Super Admin tidak dapat diedit melalui manajemen akun.
     */
    public static function canEditUser(User $actor, User $targetUser, ?string $newRole = null): bool
    {
        if (! self::canAccessAccountManagement($actor) || ! $actor->isActive()) {
            return false;
        }

        if ($targetUser->role === UserRole::ADMIN || $targetUser->role === UserRole::SUPER_ADMIN) {
            return false;
        }

        if ($newRole !== null && ! in_array($newRole, [UserRole::FAKULTAS, UserRole::JURUSAN, UserRole::PRODI], true)) {
            return false;
        }

        return in_array($targetUser->role, [UserRole::FAKULTAS, UserRole::JURUSAN, UserRole::PRODI], true);
    }

    /**
     * Memeriksa apakah aktor boleh mengaktifkan / menonaktifkan akun target.
     */
    public static function canToggleStatus(User $actor, User $targetUser): bool
    {
        if (! self::canAccessAccountManagement($actor) || ! $actor->isActive()) {
            return false;
        }

        if ($actor->id === $targetUser->id) {
            return false;
        }

        if ($targetUser->role === UserRole::ADMIN || $targetUser->role === UserRole::SUPER_ADMIN) {
            return false;
        }

        return in_array($targetUser->role, [UserRole::FAKULTAS, UserRole::JURUSAN, UserRole::PRODI], true);
    }

    /**
     * Memeriksa apakah aktor boleh mereset password akun target.
     */
    public static function canResetPassword(User $actor, User $targetUser): bool
    {
        if (! self::canAccessAccountManagement($actor) || ! $actor->isActive()) {
            return false;
        }

        if ($targetUser->role === UserRole::ADMIN || $targetUser->role === UserRole::SUPER_ADMIN) {
            return false;
        }

        return in_array($targetUser->role, [UserRole::FAKULTAS, UserRole::JURUSAN, UserRole::PRODI], true);
    }

    /**
     * Memeriksa apakah aktor boleh menghapus akun target.
     */
    public static function canDeleteUser(User $actor, User $targetUser): bool
    {
        if (! self::canAccessAccountManagement($actor) || ! $actor->isActive()) {
            return false;
        }

        if ($actor->id === $targetUser->id) {
            return false;
        }

        if ($targetUser->role === UserRole::ADMIN || $targetUser->role === UserRole::SUPER_ADMIN) {
            return false;
        }

        return in_array($targetUser->role, [UserRole::FAKULTAS, UserRole::JURUSAN, UserRole::PRODI], true);
    }

    /**
     * Menurunkan relasi induk organisasi secara otomatis berdasarkan Jabatan (Fix.md Bagian 3 & 4.4),
     * lalu memvalidasi konsistensi scope secara ketat.
     *
     * @return array{role: string, jabatan: string, fakultas_id: ?int, jurusan_id: ?int, prodi_id: ?int}
     *
     * @throws ValidationException
     */
    public static function resolveScopeForJabatan(
        string $rawJabatan,
        ?int $fakultasId,
        ?int $jurusanId,
        ?int $prodiId,
        bool $autoSelectSingleFakultas = false
    ): array {
        $canonicalJabatan = self::normalizeJabatan($rawJabatan);
        if ($canonicalJabatan === null) {
            throw ValidationException::withMessages([
                'jabatan' => ['Jabatan tidak sesuai daftar resmi. Pilihan resmi hanya: Dekan, Wakil Dekan, Ketua Jurusan, Ketua Program Studi.'],
            ]);
        }

        $role = self::mapJabatanToRole($canonicalJabatan);

        if ($role === UserRole::FAKULTAS) {
            if (empty($fakultasId) && $autoSelectSingleFakultas) {
                $activeFakultas = Fakultas::active()->get();
                if ($activeFakultas->count() === 1) {
                    $fakultasId = $activeFakultas->first()->id;
                }
            }
            $jurusanId = null;
            $prodiId = null;
        } elseif ($role === UserRole::JURUSAN) {
            if (! empty($jurusanId)) {
                $jur = Jurusan::find($jurusanId);
                if ($jur && empty($fakultasId)) {
                    $fakultasId = (int) $jur->fakultas_id;
                }
            }
            $prodiId = null;
        } elseif ($role === UserRole::PRODI) {
            if (! empty($prodiId)) {
                $prd = Prodi::find($prodiId);
                if ($prd) {
                    if (empty($jurusanId)) {
                        $jurusanId = (int) $prd->jurusan_id;
                    }
                    if (empty($fakultasId)) {
                        $fakultasId = (int) $prd->fakultas_id;
                    }
                }
            }
        }

        $scope = self::validateStrictRoleScope($role, $fakultasId, $jurusanId, $prodiId);

        return [
            'role' => $role,
            'jabatan' => $canonicalJabatan,
            'fakultas_id' => $scope['fakultas_id'],
            'jurusan_id' => $scope['jurusan_id'],
            'prodi_id' => $scope['prodi_id'],
        ];
    }

    /**
     * Memvalidasi konsistensi relasi organisasi:
     * Faculty -> Department -> Study Program
     *
     * @throws ValidationException
     */
    public static function validateOrganizationConsistency(string $role, ?int $fakultasId, ?int $jurusanId, ?int $prodiId): array
    {
        if ($role === UserRole::SUPER_ADMIN || $role === UserRole::ADMIN) {
            return [
                'fakultas_id' => null,
                'jurusan_id' => null,
                'prodi_id' => null,
            ];
        }

        if (in_array($role, [UserRole::FAKULTAS, UserRole::JURUSAN, UserRole::PRODI], true)) {
            if (! empty($prodiId)) {
                $prodi = Prodi::findOrFail($prodiId);

                if (! $prodi->isActive()) {
                    throw ValidationException::withMessages([
                        'prodi_id' => ['Program Studi yang dipilih berstatus nonaktif dan tidak dapat dipilih.'],
                    ]);
                }

                if (! empty($jurusanId) && (int) $prodi->jurusan_id !== (int) $jurusanId) {
                    throw ValidationException::withMessages([
                        'prodi_id' => ['Program Studi yang dipilih tidak berada di bawah Jurusan tersebut.'],
                    ]);
                }

                if (! empty($fakultasId) && (int) $prodi->fakultas_id !== (int) $fakultasId) {
                    throw ValidationException::withMessages([
                        'prodi_id' => ['Program Studi yang dipilih tidak berada di bawah Fakultas tersebut.'],
                    ]);
                }

                $jurusanId = ! empty($jurusanId) ? $jurusanId : $prodi->jurusan_id;
                $fakultasId = ! empty($fakultasId) ? $fakultasId : $prodi->fakultas_id;
            }

            if (! empty($jurusanId)) {
                $jurusan = Jurusan::findOrFail($jurusanId);

                if (! $jurusan->isActive()) {
                    throw ValidationException::withMessages([
                        'jurusan_id' => ['Jurusan yang dipilih berstatus nonaktif dan tidak dapat dipilih.'],
                    ]);
                }

                if (! empty($fakultasId) && (int) $jurusan->fakultas_id !== (int) $fakultasId) {
                    throw ValidationException::withMessages([
                        'jurusan_id' => ['Jurusan yang dipilih tidak berada di bawah Fakultas tersebut.'],
                    ]);
                }

                $fakultasId = ! empty($fakultasId) ? $fakultasId : $jurusan->fakultas_id;
            }

            if (! empty($fakultasId)) {
                $fakultas = Fakultas::findOrFail($fakultasId);

                if (! $fakultas->isActive()) {
                    throw ValidationException::withMessages([
                        'fakultas_id' => ['Fakultas yang dipilih berstatus nonaktif dan tidak dapat dipilih.'],
                    ]);
                }
            }

            return [
                'fakultas_id' => ! empty($fakultasId) ? (int) $fakultasId : null,
                'jurusan_id' => ! empty($jurusanId) ? (int) $jurusanId : null,
                'prodi_id' => ! empty($prodiId) ? (int) $prodiId : null,
            ];
        }

        return [
            'fakultas_id' => null,
            'jurusan_id' => null,
            'prodi_id' => null,
        ];
    }

    /**
     * Memvalidasi ketentuan scope pengguna secara ketat berdasarkan matriks role (Fix.md Bagian 2):
     * - SUPER ADMIN & ADMIN : faculty_id = NULL, department_id = NULL, study_program_id = NULL
     * - FAKULTAS            : faculty_id = Wajib, department_id = NULL, study_program_id = NULL
     * - JURUSAN             : faculty_id = Wajib, department_id = Wajib, study_program_id = NULL
     * - PRODI               : faculty_id = Wajib, department_id = Wajib, study_program_id = Wajib
     *
     * @return array{fakultas_id: ?int, jurusan_id: ?int, prodi_id: ?int}
     *
     * @throws ValidationException
     */
    public static function validateStrictRoleScope(string $role, ?int $fakultasId, ?int $jurusanId, ?int $prodiId): array
    {
        if (in_array($role, [UserRole::SUPER_ADMIN, UserRole::ADMIN], true)) {
            return [
                'fakultas_id' => null,
                'jurusan_id' => null,
                'prodi_id' => null,
            ];
        }

        if ($role === UserRole::FAKULTAS) {
            if (empty($fakultasId)) {
                throw ValidationException::withMessages([
                    'fakultas_id' => ['Fakultas wajib dipilih untuk role Fakultas.'],
                ]);
            }

            $validated = self::validateOrganizationConsistency($role, $fakultasId, null, null);

            return [
                'fakultas_id' => $validated['fakultas_id'],
                'jurusan_id' => null,
                'prodi_id' => null,
            ];
        }

        if ($role === UserRole::JURUSAN) {
            if (empty($fakultasId)) {
                throw ValidationException::withMessages([
                    'fakultas_id' => ['Fakultas wajib dipilih untuk role Jurusan.'],
                ]);
            }

            if (empty($jurusanId)) {
                throw ValidationException::withMessages([
                    'jurusan_id' => ['Jurusan wajib dipilih untuk role Jurusan.'],
                ]);
            }

            $validated = self::validateOrganizationConsistency($role, $fakultasId, $jurusanId, null);

            return [
                'fakultas_id' => $validated['fakultas_id'],
                'jurusan_id' => $validated['jurusan_id'],
                'prodi_id' => null,
            ];
        }

        if ($role === UserRole::PRODI) {
            if (empty($fakultasId)) {
                throw ValidationException::withMessages([
                    'fakultas_id' => ['Fakultas wajib dipilih untuk role Prodi.'],
                ]);
            }

            if (empty($jurusanId)) {
                throw ValidationException::withMessages([
                    'jurusan_id' => ['Jurusan wajib dipilih untuk role Prodi.'],
                ]);
            }

            if (empty($prodiId)) {
                throw ValidationException::withMessages([
                    'prodi_id' => ['Program Studi wajib dipilih untuk role Prodi.'],
                ]);
            }

            return self::validateOrganizationConsistency($role, $fakultasId, $jurusanId, $prodiId);
        }

        throw ValidationException::withMessages([
            'role' => ['Role pengguna tidak dikenali oleh sistem APJAD.'],
        ]);
    }
}
