<?php

namespace App\Enums;

class UserRole
{
    public const SUPER_ADMIN = 'Super Admin';

    public const ADMIN = 'Admin';

    public const FAKULTAS = 'Fakultas';

    public const JURUSAN = 'Jurusan';

    public const PRODI = 'Prodi';

    /**
     * All valid roles in the system.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::SUPER_ADMIN,
            self::ADMIN,
            self::FAKULTAS,
            self::JURUSAN,
            self::PRODI,
        ];
    }

    /**
     * Roles that can access Account Management pages.
     * Super Admin accesses operational account creation; Admin accesses full account management.
     */
    public static function canAccessAccountManagement(string $role): bool
    {
        return in_array($role, [self::SUPER_ADMIN, self::ADMIN], true);
    }

    /**
     * Roles that can manage Master Data Organisasi (Fakultas, Jurusan, Prodi).
     * Sesuai Fix.md Bagian 1B & 3: Hanya ADMIN (Global Administrator) yang mengelola Master Data Organisasi.
     */
    public static function canManageMasterData(string $role): bool
    {
        return $role === self::ADMIN;
    }

    /**
     * Roles that can access Academic & Scheduling Modules.
     * Sesuai Fix.md Bagian 1 & 3: ADMIN memiliki akses global, sedangkan SUPER ADMIN dilarang.
     */
    public static function canAccessScheduling(string $role): bool
    {
        return in_array($role, [
            self::ADMIN,
            self::FAKULTAS,
            self::JURUSAN,
            self::PRODI,
        ], true);
    }

    /**
     * Roles that require organization scope.
     */
    public static function requiresFakultas(string $role): bool
    {
        return in_array($role, [self::FAKULTAS, self::JURUSAN, self::PRODI], true);
    }

    public static function requiresJurusan(string $role): bool
    {
        return in_array($role, [self::JURUSAN, self::PRODI], true);
    }

    public static function requiresProdi(string $role): bool
    {
        return $role === self::PRODI;
    }

    /**
     * Roles that a given actor role is allowed to create.
     * Sesuai Fix.md Bagian 1, 2, 3, 5:
     * - Tidak boleh ada pembuatan akun SUPER ADMIN atau ADMIN melalui form/request.
     * - SUPER ADMIN & ADMIN hanya dapat membuat akun operasional: FAKULTAS, JURUSAN, PRODI.
     *
     * @return array<int, string>
     */
    public static function canCreateRoles(string $actorRole): array
    {
        return match ($actorRole) {
            self::SUPER_ADMIN, self::ADMIN => [
                self::FAKULTAS,
                self::JURUSAN,
                self::PRODI,
            ],
            default => [],
        };
    }
}
