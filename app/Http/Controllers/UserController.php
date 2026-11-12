<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\AccountGenerationHistory;
use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\Jadwal;
use App\Models\Jurusan;
use App\Models\MataKuliahDetail;
use App\Models\Prodi;
use App\Models\Ruangan;
use App\Models\User;
use App\Services\AccountAuthorizationService;
use App\Services\AccountBulkGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $currentUser = Auth::user();
        $isSuperAdmin = $currentUser->role === UserRole::SUPER_ADMIN;
        $isAdmin = $currentUser->role === UserRole::ADMIN;

        $query = User::with(['fakultas', 'jurusan', 'prodi']);

        if ($isSuperAdmin) {
            $query->whereIn('role', [UserRole::FAKULTAS, UserRole::JURUSAN, UserRole::PRODI]);
        } else {
            $query->whereIn('role', [UserRole::SUPER_ADMIN, UserRole::FAKULTAS, UserRole::JURUSAN, UserRole::PRODI]);
        }

        $users = $query->orderBy('id', 'asc')->get();

        if ($isAdmin) {
            $stats = [
                'org_fakultas' => Fakultas::count(),
                'org_jurusan' => Jurusan::count(),
                'org_prodi' => Prodi::count(),
                'total_mata_kuliah' => MataKuliahDetail::where('is_active', true)->count(),
                'total_dosen' => Dosen::where('is_active', true)->count(),
                'total_ruangan' => Ruangan::where('is_active', true)->count(),
                'total_jadwal' => Jadwal::where('is_active', true)->count(),
                'total_mata_kuliah' => MataKuliahDetail::where('is_active', true)->count(),
                'total_dosen' => Dosen::where('is_active', true)->count(),
                'total_ruangan' => Ruangan::where('is_active', true)->count(),
                'total_jadwal' => Jadwal::where('is_active', true)->count(),
                'total_users' => User::count(),
                'total_super_admin' => User::where('role', UserRole::SUPER_ADMIN)->count(),
                'total_admin' => User::where('role', UserRole::ADMIN)->count(),
                'total_fakultas' => User::where('role', UserRole::FAKULTAS)->count(),
                'total_jurusan' => User::where('role', UserRole::JURUSAN)->count(),
                'total_prodi' => User::where('role', UserRole::PRODI)->count(),
                'total_aktif' => User::where('status', 'aktif')->count(),
                'total_nonaktif' => User::where('status', 'nonaktif')->count(),
                'total_generate_history' => AccountGenerationHistory::count(),
            ];
        } else {
            $operationalUsers = User::whereIn('role', [UserRole::FAKULTAS, UserRole::JURUSAN, UserRole::PRODI]);
            $stats = [
                'org_fakultas' => Fakultas::count(),
                'org_jurusan' => Jurusan::count(),
                'org_prodi' => Prodi::count(),
                'total_users' => (clone $operationalUsers)->count(),
                'total_fakultas' => User::where('role', UserRole::FAKULTAS)->count(),
                'total_jurusan' => User::where('role', UserRole::JURUSAN)->count(),
                'total_prodi' => User::where('role', UserRole::PRODI)->count(),
                'total_aktif' => (clone $operationalUsers)->where('status', 'aktif')->count(),
                'total_nonaktif' => (clone $operationalUsers)->where('status', 'nonaktif')->count(),
                'total_generate_history' => AccountGenerationHistory::count(),
            ];
        }

        return view('dashboard', compact('users', 'stats', 'isSuperAdmin', 'isAdmin'));
    }

    /**
     * Endpoint /manajemen-akun dan /manajemen-akun/daftar.
     */
    public function manajemenAkun(Request $request): View
    {
        $role = (string) $request->query('role', 'Semua');
        if (empty($role) || ! in_array($role, [UserRole::FAKULTAS, UserRole::JURUSAN, UserRole::PRODI], true)) {
            $role = 'Semua';
        }

        return $this->renderRolePage($request, $role);
    }

    /**
     * Halaman Tambah Akun Manual (Fix.md Bagian 3 & 10).
     */
    public function tambahManual(Request $request): View
    {
        $request->merge(['open_manual' => '1']);

        return $this->renderRolePage($request, 'Semua', 'manajemen-akun');
    }

    /**
     * Halaman Akun Super Admin (Eksklusif ADMIN Global).
     */
    public function akunSuperAdmin(Request $request): View
    {
        $currentUser = Auth::user();
        if ($currentUser->role !== UserRole::ADMIN) {
            abort(403, 'Akses ditolak: Hanya Admin yang dapat melihat daftar akun Super Admin.');
        }

        return $this->renderRolePage($request, UserRole::SUPER_ADMIN);
    }

    /**
     * Halaman Akun Admin ditutup karena hanya ada 1 akun Admin sistem dari bootstrap/seeder (Fix.md Bagian 1).
     */
    public function akunAdmin(Request $request): View
    {
        abort(403, 'Akses ditolak: Hanya terdapat 1 akun Admin sistem dan tidak ada halaman pembuatan akun Admin.');
    }

    /**
     * Halaman Akun Fakultas (SUPER ADMIN: Buat Akun | ADMIN: Kelola Akun).
     */
    public function akunFakultas(Request $request): View
    {
        return $this->renderRolePage($request, UserRole::FAKULTAS);
    }

    /**
     * Halaman Akun Jurusan (SUPER ADMIN: Buat Akun | ADMIN: Kelola Akun).
     */
    public function akunJurusan(Request $request): View
    {
        return $this->renderRolePage($request, UserRole::JURUSAN);
    }

    /**
     * Halaman Akun Prodi (SUPER ADMIN: Buat Akun | ADMIN: Kelola Akun).
     */
    public function akunProdi(Request $request): View
    {
        return $this->renderRolePage($request, UserRole::PRODI);
    }

    /**
     * Render halaman manajemen akun terpisah berdasarkan role atau semua akun operasional.
     */
    private function renderRolePage(Request $request, string $pageRole = 'Semua', ?string $overrideActiveMenu = null): View
    {
        $currentUser = Auth::user();
        $isSuperAdmin = $currentUser->role === UserRole::SUPER_ADMIN;
        $isAdmin = $currentUser->role === UserRole::ADMIN;

        if ($pageRole !== 'Semua' && ! AccountAuthorizationService::canViewRole($currentUser, $pageRole)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki izin untuk mengakses kategori akun ini.');
        }

        $filterStatus = $request->query('status');
        $filterFakultasId = $request->query('fakultas_id');
        $filterJurusanId = $request->query('jurusan_id');
        $filterProdiId = $request->query('prodi_id');
        $search = trim((string) $request->query('search'));

        $query = User::with(['fakultas', 'jurusan', 'prodi']);

        if ($pageRole === 'Semua') {
            $query->whereIn('role', [UserRole::FAKULTAS, UserRole::JURUSAN, UserRole::PRODI]);
        } else {
            $query->where('role', $pageRole);
        }

        if ($filterStatus && in_array($filterStatus, ['aktif', 'nonaktif'], true)) {
            $query->where('status', $filterStatus);
        }

        if (in_array($pageRole, ['Semua', UserRole::FAKULTAS, UserRole::JURUSAN, UserRole::PRODI], true) && ! empty($filterFakultasId)) {
            $query->where('fakultas_id', (int) $filterFakultasId);
        }

        if (in_array($pageRole, ['Semua', UserRole::JURUSAN, UserRole::PRODI], true) && ! empty($filterJurusanId)) {
            $query->where('jurusan_id', (int) $filterJurusanId);
        }

        if (in_array($pageRole, ['Semua', UserRole::PRODI], true) && ! empty($filterProdiId)) {
            $query->where('prodi_id', (int) $filterProdiId);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('nuptk', 'like', "%{$search}%")
                    ->orWhere('jabatan', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('id', 'asc')->paginate(10)->withQueryString();

        $allowedRoles = UserRole::canCreateRoles($currentUser->role);
        $officialJabatan = AccountAuthorizationService::officialJabatanList();
        $fakultas = Fakultas::active()->orderBy('nama')->get();
        $jurusan = Jurusan::active()->with('fakultas')->orderBy('nama')->get();
        $prodi = Prodi::active()->with(['fakultas', 'jurusan'])->orderBy('nama')->get();

        $pageRouteName = 'manajemen-akun';
        $activeMenu = $overrideActiveMenu ?? 'manajemen-akun';

        $pageTitle = match ($pageRole) {
            UserRole::SUPER_ADMIN => 'Akun Super Admin',
            UserRole::FAKULTAS => 'Manajemen Akun Fakultas',
            UserRole::JURUSAN => 'Manajemen Akun Jurusan',
            UserRole::PRODI => 'Manajemen Akun Program Studi',
            default => 'Manajemen Akun Pengguna',
        };
        $pageDescription = match ($pageRole) {
            UserRole::SUPER_ADMIN => 'Daftar akun Super Admin pembuat akun operasional (read-only).',
            UserRole::FAKULTAS => 'Kelola akun tingkat Fakultas (Dekan & Wakil Dekan) yang terikat pada satu Fakultas.',
            UserRole::JURUSAN => 'Kelola akun tingkat Jurusan (Ketua Jurusan) yang terikat pada Fakultas dan Jurusan.',
            UserRole::PRODI => 'Kelola akun tingkat Program Studi (Ketua Program Studi) yang terikat pada Fakultas, Jurusan, dan Program Studi.',
            default => 'Kelola seluruh akun pengguna operasional (Fakultas, Jurusan, dan Program Studi).',
        };

        $canCreateAccount = in_array($pageRole, ['Semua', UserRole::FAKULTAS, UserRole::JURUSAN, UserRole::PRODI], true)
            && ($isSuperAdmin || $isAdmin);

        $canManageAccounts = ($isAdmin || $isSuperAdmin)
            && in_array($pageRole, ['Semua', UserRole::FAKULTAS, UserRole::JURUSAN, UserRole::PRODI], true);
        $autoOpenManualModal = $request->boolean('open_manual');

        return view('admin.manajemen-akun', compact(
            'users',
            'isSuperAdmin',
            'isAdmin',
            'canCreateAccount',
            'canManageAccounts',
            'autoOpenManualModal',
            'allowedRoles',
            'officialJabatan',
            'pageRole',
            'pageRouteName',
            'activeMenu',
            'pageTitle',
            'pageDescription',
            'filterStatus',
            'filterFakultasId',
            'filterJurusanId',
            'filterProdiId',
            'search',
            'fakultas',
            'jurusan',
            'prodi'
        ));
    }

    public static function roleRouteName(string $role): string
    {
        return match ($role) {
            UserRole::SUPER_ADMIN => 'manajemen-akun.super-admin',
            UserRole::FAKULTAS => 'manajemen-akun.fakultas',
            UserRole::JURUSAN => 'manajemen-akun.jurusan',
            UserRole::PRODI => 'manajemen-akun.prodi',
            default => 'manajemen-akun.fakultas',
        };
    }

    /**
     * Pembuatan Akun Manual (Fix.md Bagian 3 & 6).
     * Mendukung penentuan role otomatis dari Jabatan resmi maupun kompatibilitas parameter role operasional.
     */
    public function store(Request $request): RedirectResponse
    {
        $currentUser = Auth::user();
        $rawRoleInput = trim((string) $request->input('role', ''));
        $rawJabatanInput = trim((string) $request->input('jabatan', ''));

        // Security check: Dilarang membuat akun Admin atau Super Admin (Fix.md Bagian 1 & 11)
        if (
            in_array($rawRoleInput, [UserRole::ADMIN, UserRole::SUPER_ADMIN], true)
            || in_array(strtolower($rawJabatanInput), ['admin', 'super admin'], true)
        ) {
            abort(403, 'Akses ditolak: Pembuatan akun Admin atau Super Admin baru tidak diizinkan.');
        }

        // Bersihkan input opsional
        $cleanNuptk = trim((string) $request->input('nuptk', ''));
        $cleanNoHp = trim((string) $request->input('no_hp', ''));
        $request->merge([
            'nuptk' => $cleanNuptk !== '' ? $cleanNuptk : null,
            'no_hp' => $cleanNoHp !== '' ? $cleanNoHp : null,
        ]);

        // Jika jabatan diisi (atau role tidak diisi), maka Jabatan menjadi dasar penentuan Role (Fix.md Bagian 3)
        $useJabatanMapping = $rawJabatanInput !== '' || $rawRoleInput === '';

        $canonicalJabatan = null;
        if ($useJabatanMapping) {
            if ($rawJabatanInput === '') {
                throw ValidationException::withMessages([
                    'jabatan' => ['Jabatan wajib dipilih!'],
                ]);
            }

            $canonicalJabatan = AccountAuthorizationService::normalizeJabatan($rawJabatanInput);
            if ($canonicalJabatan === null) {
                throw ValidationException::withMessages([
                    'jabatan' => ['Jabatan tidak sesuai daftar resmi. Pilihan resmi hanya: Dekan, Wakil Dekan, Ketua Jurusan, Ketua Program Studi.'],
                ]);
            }

            $derivedRole = AccountAuthorizationService::mapJabatanToRole($canonicalJabatan);
            $request->merge([
                'jabatan' => $canonicalJabatan,
                'role' => $derivedRole,
            ]);
        }

        $requestedRole = (string) $request->input('role');

        if (! AccountAuthorizationService::canCreateRole($currentUser, $requestedRole)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki izin untuk membuat akun dengan role tersebut.');
        }

        $allowedRoles = UserRole::canCreateRoles($currentUser->role);

        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email',
            'nuptk' => 'nullable|string|max:50|unique:users,nuptk',
            'no_hp' => 'nullable|string|max:30',
            'jabatan' => ['nullable', 'string', Rule::in(AccountAuthorizationService::officialJabatanList())],
            'role' => ['required', Rule::in($allowedRoles)],
            'status' => ['nullable', Rule::in(['aktif', 'nonaktif'])],
            'password' => 'required|min:6|confirmed',
            'fakultas_id' => 'nullable|integer',
            'jurusan_id' => 'nullable|integer',
            'prodi_id' => 'nullable|integer',
        ], [
            'nama.required' => 'Nama lengkap wajib diisi!',
            'email.required' => 'Email wajib diisi!',
            'email.unique' => 'Email sudah terdaftar!',
            'nuptk.unique' => 'NUPTK sudah digunakan oleh akun lain!',
            'jabatan.in' => 'Jabatan tidak sesuai daftar resmi!',
            'role.required' => 'Role wajib dipilih!',
            'role.in' => 'Anda tidak memiliki hak untuk membuat akun dengan role tersebut!',
            'password.required' => 'Password wajib diisi!',
            'password.min' => 'Password minimal 6 karakter!',
            'password.confirmed' => 'Konfirmasi password tidak cocok!',
        ]);

        $fakultasId = ! empty($validated['fakultas_id']) ? (int) $validated['fakultas_id'] : null;
        $jurusanId = ! empty($validated['jurusan_id']) ? (int) $validated['jurusan_id'] : null;
        $prodiId = ! empty($validated['prodi_id']) ? (int) $validated['prodi_id'] : null;

        if ($canonicalJabatan !== null) {
            $resolved = AccountAuthorizationService::resolveScopeForJabatan(
                $canonicalJabatan,
                $fakultasId,
                $jurusanId,
                $prodiId,
                false
            );
            $finalRole = $resolved['role'];
            $scope = [
                'fakultas_id' => $resolved['fakultas_id'],
                'jurusan_id' => $resolved['jurusan_id'],
                'prodi_id' => $resolved['prodi_id'],
            ];
        } else {
            $finalRole = $validated['role'];
            $scope = AccountAuthorizationService::validateStrictRoleScope(
                $finalRole,
                $fakultasId,
                $jurusanId,
                $prodiId
            );
        }

        $status = $validated['status'] ?? 'aktif';

        // Validasi konflik jabatan aktif pada scope organisasi yang sama (Fix.md Bagian 6)
        AccountAuthorizationService::validateActiveJabatanConflict(
            $canonicalJabatan,
            $scope['fakultas_id'],
            $scope['jurusan_id'],
            $scope['prodi_id'],
            $status
        );

        $createdUser = User::create([
            'nama' => trim($validated['nama']),
            'email' => trim($validated['email']),
            'nuptk' => $validated['nuptk'] ?? null,
            'no_hp' => $validated['no_hp'] ?? null,
            'role' => $finalRole,
            'jabatan' => $canonicalJabatan,
            'password' => $validated['password'],
            'fakultas_id' => $scope['fakultas_id'],
            'jurusan_id' => $scope['jurusan_id'],
            'prodi_id' => $scope['prodi_id'],
            'status' => $status,
            'must_change_password' => false,
            'created_by' => $currentUser->id,
            'updated_by' => $currentUser->id,
        ]);

        Log::info('Akun operasional dibuat', [
            'created_user_id' => $createdUser->id,
            'created_user_email' => $createdUser->email,
            'role' => $createdUser->role,
            'jabatan' => $createdUser->jabatan,
            'created_by' => $currentUser->id,
            'actor_role' => $currentUser->role,
        ]);

        return redirect()->back(fallback: route(self::roleRouteName($finalRole)))
            ->with('flash_success', "Akun {$createdUser->nama} ({$finalRole}) berhasil dibuat!");
    }

    public function update(Request $request, ?User $user = null): RedirectResponse
    {
        if (! $user || ! $user->exists) {
            $userId = (int) $request->input('user_id');
            $user = User::findOrFail($userId);
        }

        $currentUser = Auth::user();
        $rawJabatanInput = trim((string) $request->input('jabatan', ''));
        $canonicalJabatan = $user->jabatan;

        if ($request->has('jabatan') && $rawJabatanInput !== '') {
            $canonicalJabatan = AccountAuthorizationService::normalizeJabatan($rawJabatanInput);
            if ($canonicalJabatan === null) {
                throw ValidationException::withMessages([
                    'jabatan' => ['Jabatan tidak sesuai daftar resmi. Pilihan resmi hanya: Dekan, Wakil Dekan, Ketua Jurusan, Ketua Program Studi.'],
                ]);
            }
            $request->merge([
                'jabatan' => $canonicalJabatan,
                'role' => AccountAuthorizationService::mapJabatanToRole($canonicalJabatan),
            ]);
        }

        $newRole = $request->input('role', $user->role);

        if (! AccountAuthorizationService::canEditUser($currentUser, $user, $newRole)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk mengedit akun ini.');
        }

        if ($request->has('nuptk')) {
            $cleanNuptk = trim((string) $request->input('nuptk', ''));
            $request->merge(['nuptk' => $cleanNuptk !== '' ? $cleanNuptk : null]);
        }

        if ($request->has('no_hp')) {
            $cleanNoHp = trim((string) $request->input('no_hp', ''));
            $request->merge(['no_hp' => $cleanNoHp !== '' ? $cleanNoHp : null]);
        }

        $allowedRoles = UserRole::canCreateRoles($currentUser->role);

        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'nuptk' => ['nullable', 'string', 'max:50', Rule::unique('users', 'nuptk')->ignore($user->id)],
            'no_hp' => 'nullable|string|max:30',
            'jabatan' => ['nullable', 'string', Rule::in(AccountAuthorizationService::officialJabatanList())],
            'role' => ['required', Rule::in($allowedRoles)],
            'status' => ['nullable', Rule::in(['aktif', 'nonaktif'])],
            'password' => 'nullable|min:6|confirmed',
            'fakultas_id' => 'nullable|integer',
            'jurusan_id' => 'nullable|integer',
            'prodi_id' => 'nullable|integer',
        ], [
            'nama.required' => 'Nama wajib diisi!',
            'email.required' => 'Email wajib diisi!',
            'email.unique' => 'Email sudah digunakan oleh user lain!',
            'nuptk.unique' => 'NUPTK sudah digunakan oleh user lain!',
            'role.required' => 'Role wajib dipilih!',
            'password.min' => 'Password minimal 6 karakter!',
            'password.confirmed' => 'Konfirmasi password tidak cocok!',
        ]);

        $rawFakultasId = $request->has('fakultas_id')
            ? (! empty($validated['fakultas_id']) ? (int) $validated['fakultas_id'] : null)
            : ($validated['role'] === $user->role ? $user->fakultas_id : null);

        $rawJurusanId = $request->has('jurusan_id')
            ? (! empty($validated['jurusan_id']) ? (int) $validated['jurusan_id'] : null)
            : ($validated['role'] === $user->role ? $user->jurusan_id : null);

        $rawProdiId = $request->has('prodi_id')
            ? (! empty($validated['prodi_id']) ? (int) $validated['prodi_id'] : null)
            : ($validated['role'] === $user->role ? $user->prodi_id : null);

        if ($request->filled('jabatan') && $canonicalJabatan !== null) {
            $resolved = AccountAuthorizationService::resolveScopeForJabatan(
                $canonicalJabatan,
                $rawFakultasId !== null ? (int) $rawFakultasId : null,
                $rawJurusanId !== null ? (int) $rawJurusanId : null,
                $rawProdiId !== null ? (int) $rawProdiId : null,
                false
            );
            $scope = [
                'fakultas_id' => $resolved['fakultas_id'],
                'jurusan_id' => $resolved['jurusan_id'],
                'prodi_id' => $resolved['prodi_id'],
            ];
            $validated['role'] = $resolved['role'];
        } else {
            $scope = AccountAuthorizationService::validateStrictRoleScope(
                $validated['role'],
                $rawFakultasId !== null ? (int) $rawFakultasId : null,
                $rawJurusanId !== null ? (int) $rawJurusanId : null,
                $rawProdiId !== null ? (int) $rawProdiId : null,
            );
        }

        $targetStatus = ! empty($validated['status']) ? $validated['status'] : $user->status;

        AccountAuthorizationService::validateActiveJabatanConflict(
            $canonicalJabatan,
            $scope['fakultas_id'],
            $scope['jurusan_id'],
            $scope['prodi_id'],
            $targetStatus,
            $user->id
        );

        $user->nama = trim($validated['nama']);
        $user->email = trim($validated['email']);
        $user->role = $validated['role'];
        $user->jabatan = $canonicalJabatan;
        $user->fakultas_id = $scope['fakultas_id'];
        $user->jurusan_id = $scope['jurusan_id'];
        $user->prodi_id = $scope['prodi_id'];
        $user->updated_by = $currentUser->id;

        if ($request->has('nuptk')) {
            $user->nuptk = $validated['nuptk'] ?? null;
        }
        if ($request->has('no_hp')) {
            $user->no_hp = $validated['no_hp'] ?? null;
        }

        if (! empty($validated['status'])) {
            $user->status = $validated['status'];
        }

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        Log::info('Akun operasional diperbarui', [
            'updated_user_id' => $user->id,
            'updated_by' => $currentUser->id,
            'actor_role' => $currentUser->role,
        ]);

        return redirect()->back(fallback: route(self::roleRouteName($user->role)))
            ->with('flash_success', "Data akun {$user->nama} berhasil diperbarui!");
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $currentUser = Auth::user();

        if (! AccountAuthorizationService::canToggleStatus($currentUser, $user)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki izin untuk mengubah status akun ini.');
        }

        $newStatus = $user->status === 'aktif' ? 'nonaktif' : 'aktif';

        if ($newStatus === 'aktif' && ! empty($user->jabatan)) {
            AccountAuthorizationService::validateActiveJabatanConflict(
                $user->jabatan,
                $user->fakultas_id,
                $user->jurusan_id,
                $user->prodi_id,
                'aktif',
                $user->id
            );
        }

        $user->update([
            'status' => $newStatus,
            'updated_by' => $currentUser->id,
        ]);

        Log::info('Status akun operasional diubah', [
            'target_user_id' => $user->id,
            'new_status' => $newStatus,
            'updated_by' => $currentUser->id,
        ]);

        $statusText = $newStatus === 'aktif' ? 'diaktifkan' : 'dinonaktifkan';
        $message = "Akun {$user->nama} ({$user->role}) berhasil {$statusText}!";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'message' => $message,
            ]);
        }

        return back()->with('flash_success', $message);
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $currentUser = Auth::user();

        if (! AccountAuthorizationService::canResetPassword($currentUser, $user)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang mereset password akun ini.');
        }

        if ($request->has('new_password_confirmation')) {
            $validated = $request->validate([
                'new_password' => 'required|string|min:6|confirmed',
            ], [
                'new_password.required' => 'Password baru wajib diisi!',
                'new_password.min' => 'Password baru minimal 6 karakter!',
                'new_password.confirmed' => 'Konfirmasi password baru tidak cocok!',
            ]);
            $newPassword = $validated['new_password'];
        } else {
            $newPassword = (string) $request->input('new_password', 'password123');
        }

        $user->update([
            'password' => Hash::make($newPassword),
            'updated_by' => $currentUser->id,
        ]);

        Log::info('Password akun operasional direset', [
            'target_user_id' => $user->id,
            'updated_by' => $currentUser->id,
        ]);

        return back()->with('flash_success', "Password akun {$user->nama} berhasil direset!");
    }

    public function destroy(Request $request, ?User $user = null): RedirectResponse
    {
        if (! $user || ! $user->exists) {
            $userId = (int) $request->input('user_id');
            $user = User::findOrFail($userId);
        }

        $currentUser = Auth::user();

        if (in_array($currentUser->role, [UserRole::SUPER_ADMIN, UserRole::ADMIN], true) && $user->id === $currentUser->id) {
            return back()->with('flash_error', 'Anda tidak dapat menghapus akun Anda sendiri!');
        }

        if (! AccountAuthorizationService::canDeleteUser($currentUser, $user)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk menghapus akun ini.');
        }

        $userName = $user->nama;
        $deletedId = $user->id;
        $user->delete();

        Log::info('Akun operasional dihapus', [
            'deleted_user_id' => $deletedId,
            'deleted_by' => $currentUser->id,
        ]);

        return back()->with('flash_success', "Akun {$userName} berhasil dihapus dari sistem!");
    }

    // =========================================================================
    // GENERATE AKUN MASSAL MELALUI EXCEL & RIWAYAT GENERATE (Fix.md Bagian 4-10)
    // =========================================================================

    /**
     * Halaman Generate Akun Massal melalui Excel.
     */
    public function generateExcel(Request $request): View
    {
        $currentUser = Auth::user();
        if (! AccountAuthorizationService::canAccessAccountManagement($currentUser)) {
            abort(403, 'Akses ditolak.');
        }

        $fakultas = Fakultas::active()->orderBy('nama')->get();
        $jurusan = Jurusan::active()->with('fakultas')->orderBy('nama')->get();
        $prodi = Prodi::active()->with(['fakultas', 'jurusan'])->orderBy('nama')->get();
        $officialJabatan = AccountAuthorizationService::officialJabatanList();
        $officialJabatanMap = AccountAuthorizationService::OFFICIAL_JABATAN_ROLE_MAP;
        $previewData = session('bulk_account_preview');
        $commitResult = session('bulk_account_commit_result');

        return view('admin.generate-akun-excel', compact(
            'fakultas',
            'jurusan',
            'prodi',
            'officialJabatan',
            'officialJabatanMap',
            'previewData',
            'commitResult'
        ));
    }

    /**
     * Download Template Excel (Fix.md Bagian 4.1).
     */
    public function downloadTemplateExcel(AccountBulkGeneratorService $bulkService): BinaryFileResponse
    {
        $currentUser = Auth::user();
        if (! AccountAuthorizationService::canAccessAccountManagement($currentUser)) {
            abort(403, 'Akses ditolak.');
        }

        $path = $bulkService->templatePath();

        return response()->download($path, 'template-generate-akun-apjad.xlsx')->deleteFileAfterSend(true);
    }

    /**
     * Upload & Baca File Excel untuk Preview tanpa langsung membuat akun (Fix.md Bagian 4.2 & 5).
     */
    public function previewExcel(Request $request, AccountBulkGeneratorService $bulkService): RedirectResponse|JsonResponse
    {
        $currentUser = Auth::user();
        if (! AccountAuthorizationService::canAccessAccountManagement($currentUser)) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ], [
            'file.required' => 'Silakan pilih file Excel atau CSV terlebih dahulu.',
            'file.max' => 'Ukuran file maksimal 10MB.',
        ]);

        $uploadedFile = $request->file('file');
        $extension = strtolower($uploadedFile->getClientOriginalExtension());
        if (! in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
            $msg = 'Format file tidak didukung. Harap gunakan file .xlsx, .xls, atau .csv.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->with('flash_error', $msg);
        }

        try {
            $evaluation = $bulkService->parseAndPreviewFile($uploadedFile->getRealPath());
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->with('flash_error', $msg);
        }

        $previewState = [
            'file_name' => $uploadedFile->getClientOriginalName(),
            'idempotency_key' => Str::uuid()->toString(),
            'rows' => $evaluation['rows'],
            'summary' => $evaluation['summary'],
        ];

        session(['bulk_account_preview' => $previewState]);
        session()->forget('bulk_account_commit_result');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $previewState,
            ]);
        }

        return redirect()->route('manajemen-akun.generate-excel')
            ->with('flash_success', 'File berhasil dibaca. Silakan periksa tabel Preview sebelum menekan ACC.');
    }

    /**
     * Re-validasi baris Preview saat diedit oleh pengguna (Fix.md Bagian 5).
     */
    public function revalidatePreview(Request $request, AccountBulkGeneratorService $bulkService): RedirectResponse|JsonResponse
    {
        $currentUser = Auth::user();
        if (! AccountAuthorizationService::canAccessAccountManagement($currentUser)) {
            abort(403, 'Akses ditolak.');
        }

        $rows = $request->input('rows', []);
        if (! is_array($rows)) {
            $rows = [];
        }

        $evaluation = $bulkService->evaluatePreviewRows($rows);
        $currentPreview = session('bulk_account_preview', []);

        $previewState = [
            'file_name' => $request->input('file_name', $currentPreview['file_name'] ?? 'Preview Edit'),
            'idempotency_key' => $request->input('idempotency_key', $currentPreview['idempotency_key'] ?? Str::uuid()->toString()),
            'rows' => $evaluation['rows'],
            'summary' => $evaluation['summary'],
        ];

        session(['bulk_account_preview' => $previewState]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $previewState,
            ]);
        }

        return redirect()->route('manajemen-akun.generate-excel')
            ->with('flash_success', 'Data Preview berhasil diperbarui dan divalidasi ulang.');
    }

    /**
     * Eksekusi pembuatan akun massal setelah pengguna menekan tombol ACC (Fix.md Bagian 7 & 8).
     */
    public function commitExcel(Request $request, AccountBulkGeneratorService $bulkService): RedirectResponse|JsonResponse
    {
        $currentUser = Auth::user();
        if (! AccountAuthorizationService::canAccessAccountManagement($currentUser)) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'password_method' => ['required', Rule::in(['auto', 'manual'])],
            'rows' => ['required', 'array', 'min:1'],
            'file_name' => ['nullable', 'string', 'max:255'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ], [
            'password_method.required' => 'Metode pengaturan password wajib dipilih.',
            'rows.required' => 'Data Preview tidak ditemukan.',
        ]);

        $rows = $request->input('rows', []);
        $passwordMethod = (string) $request->input('password_method', 'auto');
        $fileName = $request->input('file_name') ?: (session('bulk_account_preview.file_name') ?? 'Upload Excel Massal');
        $idempotencyKey = $request->input('idempotency_key') ?: session('bulk_account_preview.idempotency_key');

        $result = $bulkService->commitBatch(
            $rows,
            $passwordMethod,
            $currentUser,
            $fileName,
            $idempotencyKey
        );

        if (! $result['success']) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Gagal memproses pembuatan akun massal.',
                    'errors' => $result['errors'] ?? [],
                ], 422);
            }

            return redirect()->route('manajemen-akun.generate-excel')
                ->with('flash_error', ($result['message'] ?? 'Gagal memproses akun: ').' '.($result['errors'][0] ?? ''));
        }

        session()->forget('bulk_account_preview');
        session([
            'bulk_account_commit_result' => [
                'history_id' => $result['history']->id,
                'created_count' => $result['created_count'],
                'failed_count' => $result['failed_count'],
                'password_method' => $passwordMethod,
                'credentials_token' => $result['credentials_token'],
                'already_processed' => $result['already_processed'],
            ],
        ]);

        $msg = $result['already_processed']
            ? "Proses ini sudah pernah dijalankan sebelumnya ({$result['created_count']} akun dibuat)."
            : "Berhasil membuat {$result['created_count']} akun operasional baru!";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'already_processed' => $result['already_processed'],
                'message' => $msg,
                'created_count' => $result['created_count'],
                'failed_count' => $result['failed_count'],
                'credentials_token' => $result['credentials_token'],
                'credentials_download_url' => $result['credentials_token']
                    ? route('manajemen-akun.generate-excel.credentials', ['token' => $result['credentials_token']])
                    : null,
                'history_id' => $result['history']->id,
            ]);
        }

        return redirect()->route('manajemen-akun.generate-excel')
            ->with('flash_success', $msg);
    }

    /**
     * Unduhan kredensial sementara satu kali setelah generate password otomatis (Fix.md Bagian 7).
     */
    public function downloadTemporaryCredentials(
        Request $request,
        string $token,
        AccountBulkGeneratorService $bulkService
    ): BinaryFileResponse|RedirectResponse {
        $currentUser = Auth::user();
        if (! AccountAuthorizationService::canAccessAccountManagement($currentUser)) {
            abort(403, 'Akses ditolak.');
        }

        $cacheKey = "apjad_temp_credentials_{$currentUser->id}_{$token}";
        $encryptedPayload = Cache::pull($cacheKey);

        if (! $encryptedPayload) {
            abort(404, 'Kredensial sementara sudah pernah diunduh (hanya berlaku satu kali) atau telah kedaluwarsa.');
        }

        $credentials = decrypt($encryptedPayload);
        $path = $bulkService->createCredentialsDownloadFile($credentials);

        return response()->download($path, 'kredensial-sementara-apjad.xlsx')->deleteFileAfterSend(true);
    }

    /**
     * Halaman Riwayat Generate Akun (Fix.md Bagian 9).
     */
    public function riwayatGenerate(Request $request): View
    {
        $currentUser = Auth::user();
        if (! AccountAuthorizationService::canAccessAccountManagement($currentUser)) {
            abort(403, 'Akses ditolak.');
        }

        $histories = AccountGenerationHistory::orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('admin.riwayat-generate-akun', compact('histories'));
    }

    public function getJurusanByFakultas(Fakultas $fakultas): JsonResponse
    {
        $jurusans = Jurusan::active()
            ->where('fakultas_id', $fakultas->id)
            ->orderBy('nama')
            ->get(['id', 'nama', 'kode', 'fakultas_id', 'status']);

        return response()->json($jurusans);
    }

    public function getProdiByJurusan(Jurusan $jurusan): JsonResponse
    {
        $prodis = Prodi::active()
            ->where('jurusan_id', $jurusan->id)
            ->orderBy('nama')
            ->get(['id', 'nama', 'kode', 'fakultas_id', 'jurusan_id', 'status']);

        return response()->json($prodis);
    }
}
