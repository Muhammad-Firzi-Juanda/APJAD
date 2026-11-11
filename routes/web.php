<?php

use App\Enums\UserRole;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FakultasAllocationController;
use App\Http\Controllers\FakultasController;
use App\Http\Controllers\FakultasCourseController;
use App\Http\Controllers\FakultasDashboardController;
use App\Http\Controllers\FakultasLaboratoryController;
use App\Http\Controllers\FakultasRoomController;
use App\Http\Controllers\JadwalKuliahController;
use App\Http\Controllers\JurusanController;
use App\Http\Controllers\JurusanDashboardController;
use App\Http\Controllers\MataKuliahController;
use App\Http\Controllers\PenjadwalanController;
use App\Http\Controllers\ProdiController;
use App\Http\Controllers\ProdiDashboardController;
use App\Http\Controllers\ProdiManagementController;
use App\Http\Controllers\RuanganController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - APJAD
|--------------------------------------------------------------------------
*/

// Root route redirects to dashboard according to role
Route::get('/', function () {
    if (Auth::check()) {
        return match (Auth::user()->role) {
            UserRole::SUPER_ADMIN, UserRole::ADMIN => redirect()->route('dashboard'),
            UserRole::FAKULTAS => redirect()->route('fakultas.dashboard'),
            UserRole::JURUSAN => redirect()->route('jurusan.dashboard'),
            UserRole::PRODI => redirect()->route('prodi.dashboard'),
            default => redirect()->route('login'),
        };
    }

    return redirect()->route('login');
});

// Guest routes (Login)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1')->name('login.submit');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::post('/change-password', [AuthController::class, 'changePassword'])->name('password.change');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout.get');

    // =========================================================================
    // 0. MODUL MASTER DATA ORGANISASI (Eksklusif Admin Global)
    //    Super Admin fokus pada Generate Akun & CRUD Akun Operasional
    // =========================================================================
    Route::middleware('check_role:Admin')->group(function () {
        // Master Fakultas
        Route::get('/master-data/fakultas', [FakultasController::class, 'index'])->name('master-data.fakultas.index');
        Route::post('/master-data/fakultas', [FakultasController::class, 'store'])->name('master-data.fakultas.store');
        Route::put('/master-data/fakultas/{fakultas}', [FakultasController::class, 'update'])->name('master-data.fakultas.update');
        Route::post('/master-data/fakultas/{fakultas}/toggle-status', [FakultasController::class, 'toggleStatus'])->name('master-data.fakultas.toggle-status');
        Route::delete('/master-data/fakultas/{fakultas}', [FakultasController::class, 'destroy'])->name('master-data.fakultas.destroy');

        Route::get('/fakultas', [FakultasController::class, 'index'])->name('fakultas.index');
        Route::post('/fakultas', [FakultasController::class, 'store'])->name('fakultas.store');
        Route::put('/fakultas/{fakultas}', [FakultasController::class, 'update'])->name('fakultas.update');
        Route::post('/fakultas/{fakultas}/toggle-status', [FakultasController::class, 'toggleStatus'])->name('fakultas.toggle-status');
        Route::delete('/fakultas/{fakultas}', [FakultasController::class, 'destroy'])->name('fakultas.destroy');

        // Master Jurusan
        Route::get('/master-data/jurusan', [JurusanController::class, 'index'])->name('master-data.jurusan.index');
        Route::post('/master-data/jurusan', [JurusanController::class, 'store'])->name('master-data.jurusan.store');
        Route::put('/master-data/jurusan/{jurusan}', [JurusanController::class, 'update'])->name('master-data.jurusan.update');
        Route::post('/master-data/jurusan/{jurusan}/toggle-status', [JurusanController::class, 'toggleStatus'])->name('master-data.jurusan.toggle-status');
        Route::delete('/master-data/jurusan/{jurusan}', [JurusanController::class, 'destroy'])->name('master-data.jurusan.destroy');

        Route::get('/jurusan', [JurusanController::class, 'index'])->name('jurusan.index');
        Route::post('/jurusan', [JurusanController::class, 'store'])->name('jurusan.store');
        Route::put('/jurusan/{jurusan}', [JurusanController::class, 'update'])->name('jurusan.update');
        Route::post('/jurusan/{jurusan}/toggle-status', [JurusanController::class, 'toggleStatus'])->name('jurusan.toggle-status');
        Route::delete('/jurusan/{jurusan}', [JurusanController::class, 'destroy'])->name('jurusan.destroy');

        // Master Program Studi
        Route::get('/master-data/prodi', [ProdiController::class, 'index'])->name('master-data.prodi.index');
        Route::post('/master-data/prodi', [ProdiController::class, 'store'])->name('master-data.prodi.store');
        Route::put('/master-data/prodi/{prodi}', [ProdiController::class, 'update'])->name('master-data.prodi.update');
        Route::post('/master-data/prodi/{prodi}/toggle-status', [ProdiController::class, 'toggleStatus'])->name('master-data.prodi.toggle-status');
        Route::delete('/master-data/prodi/{prodi}', [ProdiController::class, 'destroy'])->name('master-data.prodi.destroy');

        Route::get('/prodi', [ProdiController::class, 'index'])->name('prodi.index');
        Route::post('/prodi', [ProdiController::class, 'store'])->name('prodi.store');
        Route::put('/prodi/{prodi}', [ProdiController::class, 'update'])->name('prodi.update');
        Route::post('/prodi/{prodi}/toggle-status', [ProdiController::class, 'toggleStatus'])->name('prodi.toggle-status');
        Route::delete('/prodi/{prodi}', [ProdiController::class, 'destroy'])->name('prodi.destroy');
    });

    // =========================================================================
    // 1. MODUL MANAJEMEN AKUN & GENERATE AKUN MASSAL (Super Admin & Admin)
    // =========================================================================
    Route::middleware('check_role:Super Admin,Admin')->group(function () {
        Route::get('/dashboard', [UserController::class, 'index'])->name('dashboard');
        Route::get('/manajemen-akun', [UserController::class, 'manajemenAkun'])->name('manajemen-akun');
        Route::get('/manajemen-akun/daftar', [UserController::class, 'akunFakultas'])->name('manajemen-akun.daftar');
        Route::get('/manajemen-akun/tambah-manual', [UserController::class, 'tambahManual'])->name('manajemen-akun.tambah-manual');
        Route::get('/manajemen-akun/generate-excel', [UserController::class, 'generateExcel'])->name('manajemen-akun.generate-excel');
        Route::post('/manajemen-akun/generate-excel/preview', [UserController::class, 'previewExcel'])->name('manajemen-akun.generate-excel.preview');
        Route::post('/manajemen-akun/generate-excel/revalidate', [UserController::class, 'revalidatePreview'])->name('manajemen-akun.generate-excel.revalidate');
        Route::post('/manajemen-akun/generate-excel/commit', [UserController::class, 'commitExcel'])->name('manajemen-akun.generate-excel.commit');
        Route::get('/manajemen-akun/generate-excel/credentials/{token}', [UserController::class, 'downloadTemporaryCredentials'])->name('manajemen-akun.generate-excel.credentials');
        Route::get('/manajemen-akun/riwayat-generate', [UserController::class, 'riwayatGenerate'])->name('manajemen-akun.riwayat-generate');
        Route::get('/manajemen-akun/template-excel', [UserController::class, 'downloadTemplateExcel'])->name('manajemen-akun.template-excel');

        Route::get('/manajemen-akun/super-admin', [UserController::class, 'akunSuperAdmin'])->name('manajemen-akun.super-admin');
        Route::get('/manajemen-akun/admin', [UserController::class, 'akunAdmin'])->name('manajemen-akun.admin');
        Route::get('/manajemen-akun/fakultas', [UserController::class, 'akunFakultas'])->name('manajemen-akun.fakultas');
        Route::get('/manajemen-akun/jurusan', [UserController::class, 'akunJurusan'])->name('manajemen-akun.jurusan');
        Route::get('/manajemen-akun/prodi', [UserController::class, 'akunProdi'])->name('manajemen-akun.prodi');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

        // Cascading dropdown helpers
        Route::get('/api/fakultas/{fakultas}/jurusan', [UserController::class, 'getJurusanByFakultas'])->name('api.fakultas.jurusan');
        Route::get('/api/jurusan/{jurusan}/prodi', [UserController::class, 'getProdiByJurusan'])->name('api.jurusan.prodi');
    });

    // =========================================================================
    // 2. MODUL FAKULTAS (Admin Global & Admin Fakultas)
    // =========================================================================
    Route::middleware('check_role:Admin,Fakultas')->group(function () {
        Route::get('/fakultas/dashboard', [FakultasDashboardController::class, 'index'])->name('fakultas.dashboard');

        // Mata Kuliah Fakultas (MKWU & MKWF)
        Route::get('/fakultas/mkwu', [FakultasCourseController::class, 'mkwuIndex'])->name('fakultas.mkwu');
        Route::get('/fakultas/mkwf', [FakultasCourseController::class, 'mkwfIndex'])->name('fakultas.mkwf');
<<<<<<< HEAD
        Route::get('/fakultas/courses/template', [FakultasCourseController::class, 'downloadTemplate'])->name('fakultas.courses.template');
        Route::post('/fakultas/courses/preview-import', [FakultasCourseController::class, 'previewImport'])->name('fakultas.courses.preview-import');
        Route::post('/fakultas/courses/import', [FakultasCourseController::class, 'import'])->name('fakultas.courses.import');
=======
>>>>>>> d1c29ef9954c3c6e9b48137a65ff2e4deacab6d0
        Route::post('/fakultas/courses', [FakultasCourseController::class, 'store'])->name('fakultas.courses.store');
        Route::put('/fakultas/courses/{course}', [FakultasCourseController::class, 'update'])->name('fakultas.courses.update');
        Route::post('/fakultas/courses/{course}/toggle-status', [FakultasCourseController::class, 'toggleStatus'])->name('fakultas.courses.toggle-status');
        Route::delete('/fakultas/courses/{course}', [FakultasCourseController::class, 'destroy'])->name('fakultas.courses.destroy');
        Route::post('/fakultas/courses/bulk-delete', [FakultasCourseController::class, 'bulkDestroy'])->name('fakultas.courses.bulk-delete');

<<<<<<< HEAD
        // Penjadwalan Kuliah Fakultas (MKWU & MKWF)
        Route::get('/fakultas/penjadwalan', [FakultasCourseController::class, 'penjadwalanIndex'])->name('fakultas.penjadwalan');
        Route::post('/fakultas/penjadwalan/generate', [FakultasCourseController::class, 'generateJadwal'])->name('fakultas.penjadwalan.generate');
        Route::delete('/fakultas/penjadwalan/hapus-semua', [FakultasCourseController::class, 'destroyJadwalFakultas'])->name('fakultas.penjadwalan.destroy-all');

=======
>>>>>>> d1c29ef9954c3c6e9b48137a65ff2e4deacab6d0
        // Resources - Ruangan & Laboratorium (Terpadu)
        Route::get('/fakultas/ruangan', [FakultasRoomController::class, 'index'])->name('fakultas.rooms.index');
        Route::post('/fakultas/ruangan', [FakultasRoomController::class, 'store'])->name('fakultas.rooms.store');
        Route::put('/fakultas/ruangan/{room}', [FakultasRoomController::class, 'update'])->name('fakultas.rooms.update');
        Route::post('/fakultas/ruangan/{room}/toggle-status', [FakultasRoomController::class, 'toggleStatus'])->name('fakultas.rooms.toggle-status');
        Route::delete('/fakultas/ruangan/{room}', [FakultasRoomController::class, 'destroy'])->name('fakultas.rooms.destroy');
        Route::post('/fakultas/ruangan/bulk-delete', [FakultasRoomController::class, 'bulkDestroy'])->name('fakultas.rooms.bulk-delete');
        Route::post('/fakultas/ruangan/{room}/assign-prodi', [FakultasRoomController::class, 'assignProdi'])->name('fakultas.rooms.assign-prodi');

        // Resources - Laboratorium (Laboratories)
        Route::get('/fakultas/laboratorium', [FakultasLaboratoryController::class, 'index'])->name('fakultas.laboratories.index');
        Route::post('/fakultas/laboratorium', [FakultasLaboratoryController::class, 'store'])->name('fakultas.laboratories.store');
        Route::put('/fakultas/laboratorium/{laboratory}', [FakultasLaboratoryController::class, 'update'])->name('fakultas.laboratories.update');
        Route::post('/fakultas/laboratorium/{laboratory}/toggle-status', [FakultasLaboratoryController::class, 'toggleStatus'])->name('fakultas.laboratories.toggle-status');
        Route::delete('/fakultas/laboratorium/{laboratory}', [FakultasLaboratoryController::class, 'destroy'])->name('fakultas.laboratories.destroy');

        // Resources - Alokasi Ruangan ke Prodi
        Route::get('/fakultas/alokasi-ruangan', [FakultasAllocationController::class, 'roomAllocationsIndex'])->name('fakultas.allocations.rooms');
        Route::post('/fakultas/alokasi-ruangan', [FakultasAllocationController::class, 'storeRoomAllocation'])->name('fakultas.allocations.rooms.store');
        Route::delete('/fakultas/alokasi-ruangan/{allocation}', [FakultasAllocationController::class, 'destroyRoomAllocation'])->name('fakultas.allocations.rooms.destroy');

        // Resources - Alokasi Laboratorium ke Prodi
        Route::get('/fakultas/alokasi-laboratorium', [FakultasAllocationController::class, 'labAllocationsIndex'])->name('fakultas.allocations.laboratories');
        Route::post('/fakultas/alokasi-laboratorium', [FakultasAllocationController::class, 'storeLabAllocation'])->name('fakultas.allocations.laboratories.store');
        Route::delete('/fakultas/alokasi-laboratorium/{allocation}', [FakultasAllocationController::class, 'destroyLabAllocation'])->name('fakultas.allocations.laboratories.destroy');
    });

    // =========================================================================
    // 3. MODUL JURUSAN (Admin Global & Admin Jurusan)
    // =========================================================================
    Route::middleware('check_role:Admin,Jurusan')->group(function () {
        Route::get('/jurusan/dashboard', [JurusanDashboardController::class, 'index'])->name('jurusan.dashboard');
    });

    // =========================================================================
    // 4. MODUL PRODI (Admin Global & Admin Prodi)
    // =========================================================================
    Route::middleware('check_role:Admin,Prodi')->group(function () {
        Route::get('/prodi/dashboard', [ProdiDashboardController::class, 'index'])->name('prodi.dashboard');
        Route::post('/prodi/jadwal', [ProdiManagementController::class, 'storeJadwal'])->name('prodi.jadwal.store');
    });

    // =========================================================================
    // 5. MODUL AKADEMIK BERSAMA: RUANGAN & PENJADWALAN (Admin Global, Fakultas, Prodi)
    //    *Super Admin DILARANG mengakses modul ini (Fix.md Bagian 1A & 3)*
    // =========================================================================
    Route::middleware('check_role:Admin,Fakultas,Prodi')->group(function () {
        // Master Mata Kuliah
        Route::post('/mata-kuliah', [MataKuliahController::class, 'store'])->name('mata-kuliah.store');
        Route::put('/mata-kuliah/{mataKuliah}', [MataKuliahController::class, 'update'])->name('mata-kuliah.update');
        Route::delete('/mata-kuliah/{mataKuliah}', [MataKuliahController::class, 'destroy'])->name('mata-kuliah.destroy');

        // Ruangan & Laboratorium
        Route::get('/ruangan', [RuanganController::class, 'index'])->name('ruangan.index');
        Route::get('/ruangan/create', [RuanganController::class, 'create'])->name('ruangan.create');
        Route::post('/ruangan', [RuanganController::class, 'store'])->name('ruangan.store');
        Route::get('/ruangan/{ruangan}/edit', [RuanganController::class, 'edit'])->name('ruangan.edit');
        Route::put('/ruangan/{ruangan}', [RuanganController::class, 'update'])->name('ruangan.update');
        Route::delete('/ruangan/{ruangan}', [RuanganController::class, 'destroy'])->name('ruangan.destroy');
        Route::post('/ruangan/{ruangan}/toggle-status', [RuanganController::class, 'toggleStatus'])->name('ruangan.toggle-status');

        // Penjadwalan & Jadwal Kuliah
        Route::get('/jadwal-kuliah', [JadwalKuliahController::class, 'index'])->name('jadwal-kuliah.index');
        Route::get('/penjadwalan', [PenjadwalanController::class, 'index'])->name('penjadwalan.index');
        Route::post('/penjadwalan/mata-kuliah', [PenjadwalanController::class, 'store'])->name('penjadwalan.store');
        Route::put('/penjadwalan/mata-kuliah/{mataKuliah}', [PenjadwalanController::class, 'update'])->name('penjadwalan.update');
        Route::delete('/penjadwalan/mata-kuliah/{mataKuliah}', [PenjadwalanController::class, 'destroy'])->name('penjadwalan.destroy');
        Route::post('/penjadwalan/generate', [PenjadwalanController::class, 'generate'])->name('penjadwalan.generate');
        Route::get('/penjadwalan/template', [PenjadwalanController::class, 'downloadTemplate'])->name('penjadwalan.template');
        Route::post('/penjadwalan/import-preview', [PenjadwalanController::class, 'previewImport'])->name('penjadwalan.import.preview');
        Route::post('/penjadwalan/import', [PenjadwalanController::class, 'import'])->name('penjadwalan.import');
        Route::delete('/penjadwalan/hapus-semua', [PenjadwalanController::class, 'destroyAll'])->name('penjadwalan.destroy-all');
        Route::post('/penjadwalan/clear-log', [PenjadwalanController::class, 'clearLog'])->name('penjadwalan.clear-log');

        // API AJAX Ruangan
        Route::get('/api/ruangan/{ruangan}', [RuanganController::class, 'show'])->name('api.ruangan.show');
        Route::post('/api/ruangan', [RuanganController::class, 'store'])->name('api.ruangan.store');
        Route::put('/api/ruangan/{ruangan}', [RuanganController::class, 'update'])->name('api.ruangan.update');
        Route::delete('/api/ruangan/{ruangan}', [RuanganController::class, 'destroy'])->name('api.ruangan.destroy');
        Route::post('/api/ruangan/{ruangan}/toggle-status', [RuanganController::class, 'toggleStatus'])->name('api.ruangan.toggle-status');
    });
});
