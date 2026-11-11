<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AccountGenerationHistory;
use App\Models\Fakultas;
use App\Models\Jurusan;
use App\Models\Prodi;
use App\Models\User;
use App\Services\AccountBulkGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private const SUPER_ADMIN_EMAIL = 'admin@siwalan.com';

    private const SUPER_ADMIN_PASSWORD = 'admin123';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function superAdmin(): User
    {
        return User::where('email', self::SUPER_ADMIN_EMAIL)->firstOrFail();
    }

    private function adminAkun(): User
    {
        return User::where('email', 'admin@apjad.com')->firstOrFail();
    }

    private function fakultas(): User
    {
        return User::where('email', 'bdarell@gmail.com')->firstOrFail();
    }

    private function jurusan(): User
    {
        return User::where('email', 'randtian@gmail.com')->firstOrFail();
    }

    private function prodi(): User
    {
        return User::where('email', 'prodi@apjad.com')->firstOrFail();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('APJAD');
        $response->assertSee('Universitas Maritim');
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => self::SUPER_ADMIN_EMAIL,
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('show_error', true);
        $this->assertGuest();
    }

    public function test_login_succeeds_with_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => self::SUPER_ADMIN_EMAIL,
            'password' => self::SUPER_ADMIN_PASSWORD,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_login_ajax_succeeds_with_json_popup_response(): void
    {
        $response = $this->postJson('/login', [
            'email' => self::SUPER_ADMIN_EMAIL,
            'password' => self::SUPER_ADMIN_PASSWORD,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Login Berhasil',
        ]);
        $this->assertAuthenticated();
    }

    public function test_super_admin_dashboard_renders_operational_account_kpi_only(): void
    {
        $response = $this->actingAs($this->superAdmin())->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard Super Admin');
        $response->assertDontSee('Ringkasan Master Data Organisasi');
        $response->assertDontSee(route('master-data.fakultas.index'));
        $response->assertSee('Total Akun');
        $response->assertSee('Akun Fakultas');
        $response->assertSee('Akun Jurusan');
        $response->assertSee('Akun Prodi');
    }

    public function test_admin_dashboard_renders_master_data_and_account_summary(): void
    {
        $response = $this->actingAs($this->adminAkun())->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard Admin');
        $response->assertSee('Ringkasan Master Data Organisasi');
        $response->assertSee('Total Fakultas');
        $response->assertSee('Total Jurusan');
        $response->assertSee('Total Program Studi');
        $response->assertSee('Akun Fakultas');
        $response->assertSee('Akun Jurusan');
        $response->assertSee('Akun Prodi');
    }

    public function test_manajemen_akun_redirects_and_renders_role_separated_pages(): void
    {
        $superAdmin = $this->superAdmin();
        $admin = $this->adminAkun();

        $respSaAll = $this->actingAs($superAdmin)->get('/manajemen-akun');
        $respSaAll->assertOk();
        $respSaAll->assertSee('Semua Akun');
        $respSaAll->assertDontSee('Akun Super Admin');

        $respAdminAll = $this->actingAs($admin)->get('/manajemen-akun');
        $respAdminAll->assertOk();
        $respAdminAll->assertSee('Semua Akun');
        $respAdminAll->assertDontSee('Akun Super Admin');

        // Halaman Akun Admin ditutup (403) karena hanya ada 1 akun Admin sistem
        $this->actingAs($superAdmin)->get('/manajemen-akun/admin')->assertForbidden();
        $this->actingAs($admin)->get('/manajemen-akun/admin')->assertForbidden();

        // Halaman Akun Super Admin hanya dapat dilihat oleh Admin (read-only)
        $this->actingAs($superAdmin)->get('/manajemen-akun/super-admin')->assertForbidden();
        $respSuperAdmin = $this->actingAs($admin)->get('/manajemen-akun/super-admin');
        $respSuperAdmin->assertOk();
        $respSuperAdmin->assertSee('Akun Super Admin');
        $respSuperAdmin->assertSee('admin@siwalan.com');

        // Halaman Manajemen Akun Fakultas (Super Admin)
        $respFakultas = $this->actingAs($superAdmin)->get('/manajemen-akun/fakultas');
        $respFakultas->assertStatus(200);
        $respFakultas->assertSee('Manajemen Akun Fakultas');
        $respFakultas->assertSee('bdarell@gmail.com');
        $respFakultas->assertDontSee('randtian@gmail.com');

        // Halaman Manajemen Akun Jurusan (Super Admin)
        $respJurusan = $this->actingAs($superAdmin)->get('/manajemen-akun/jurusan');
        $respJurusan->assertStatus(200);
        $respJurusan->assertSee('Manajemen Akun Jurusan');
        $respJurusan->assertSee('randtian@gmail.com');
        $respJurusan->assertDontSee('prodi@apjad.com');

        // Halaman Manajemen Akun Prodi (Super Admin)
        $respProdi = $this->actingAs($superAdmin)->get('/manajemen-akun/prodi');
        $respProdi->assertStatus(200);
        $respProdi->assertSee('Manajemen Akun Program Studi');
        $respProdi->assertSee('prodi@apjad.com');
        $respProdi->assertDontSee('bdarell@gmail.com');
    }

    public function test_manajemen_akun_search_filter(): void
    {
        $response = $this->actingAs($this->superAdmin())->get('/manajemen-akun/fakultas?search=bdarell');

        $response->assertStatus(200);
        $response->assertSee('bdarell@gmail.com');
        $response->assertDontSee('randtian@gmail.com');
    }

    // =========================================================================
    // SECURITY & ROLE SEPARATION TESTS SESUAI FIX.MD
    // =========================================================================

    public function test_admin_cannot_create_admin_returns_403(): void
    {
        $admin = $this->adminAkun();

        $response = $this->actingAs($admin)->post('/users', [
            'nama' => 'Admin Baru',
            'email' => 'adminbaru@gmail.com',
            'role' => UserRole::ADMIN,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'adminbaru@gmail.com']);
    }

    public function test_admin_cannot_create_super_admin_returns_403(): void
    {
        $admin = $this->adminAkun();

        $response = $this->actingAs($admin)->post('/users', [
            'nama' => 'Super Admin Ilegal',
            'email' => 'superilegal@gmail.com',
            'role' => UserRole::SUPER_ADMIN,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'superilegal@gmail.com']);
    }

    public function test_super_admin_cannot_create_admin_or_super_admin_returns_403(): void
    {
        $superAdmin = $this->superAdmin();

        $respAdmin = $this->actingAs($superAdmin)->post('/users', [
            'nama' => 'Admin Kedua Ilegal',
            'email' => 'admin2@apjad.com',
            'role' => UserRole::ADMIN,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $respAdmin->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'admin2@apjad.com']);

        $respSuper = $this->actingAs($superAdmin)->post('/users', [
            'nama' => 'Super Admin Kedua Ilegal',
            'email' => 'super2@apjad.com',
            'role' => UserRole::SUPER_ADMIN,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $respSuper->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'super2@apjad.com']);
    }

    public function test_super_admin_can_crud_operational_accounts_but_cannot_mutate_admin_or_self(): void
    {
        $superAdmin = $this->superAdmin();
        $admin = $this->adminAkun();
        $target = $this->fakultas();

        // Super Admin dapat mengedit akun operasional
        $this->actingAs($superAdmin)->put("/users/{$target->id}", [
            'nama' => 'Brian Darell Updated',
            'email' => $target->email,
            'role' => UserRole::FAKULTAS,
            'fakultas_id' => $target->fakultas_id,
        ])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $target->id, 'nama' => 'Brian Darell Updated']);

        // Super Admin dapat toggle status akun operasional
        $this->actingAs($superAdmin)->post("/users/{$target->id}/toggle-status")
            ->assertRedirect();
        $this->assertSame('nonaktif', $target->fresh()->status);

        // Super Admin dapat reset password akun operasional
        $this->actingAs($superAdmin)->post("/users/{$target->id}/reset-password", [
            'new_password' => 'newpass123',
            'new_password_confirmation' => 'newpass123',
        ])->assertRedirect();
        $this->assertTrue(Hash::check('newpass123', $target->fresh()->password));

        // Super Admin tidak boleh mengedit atau menghapus akun Admin maupun dirinya sendiri
        $this->actingAs($superAdmin)->put("/users/{$admin->id}", [
            'nama' => 'Admin Diubah',
            'email' => $admin->email,
            'role' => UserRole::ADMIN,
        ])->assertForbidden();

        $this->actingAs($superAdmin)->delete("/users/{$admin->id}")
            ->assertForbidden();
    }

    public function test_admin_cannot_edit_super_admin_or_admin_returns_403(): void
    {
        $admin = $this->adminAkun();
        $superAdmin = $this->superAdmin();

        $this->actingAs($admin)->put("/users/{$superAdmin->id}", [
            'nama' => 'Super Admin Diubah',
            'email' => $superAdmin->email,
            'role' => $superAdmin->role,
        ])->assertForbidden();

        $this->actingAs($admin)->put("/users/{$admin->id}", [
            'nama' => 'Admin Diubah',
            'email' => $admin->email,
            'role' => UserRole::ADMIN,
        ])->assertForbidden();
    }

    public function test_faculty_department_and_prodi_cannot_access_account_management_returns_403(): void
    {
        foreach ([$this->fakultas(), $this->jurusan(), $this->prodi()] as $user) {
            $this->actingAs($user)->get('/dashboard')->assertForbidden();
            $this->actingAs($user)->get('/manajemen-akun')->assertForbidden();
            $this->actingAs($user)->post('/users', [])->assertForbidden();
        }
    }

    public function test_admin_can_create_operational_account_successfully(): void
    {
        $admin = $this->adminAkun();
        $fakultas = Fakultas::firstOrFail();
        $jurusan = Jurusan::where('kode', 'TEI')->firstOrFail();
        $prodi = Prodi::where('kode', 'TE')->firstOrFail();

        $response = $this->actingAs($admin)->post('/users', [
            'nama' => 'Kaprodi TE Baru',
            'email' => 'kaprodite@apjad.com',
            'nuptk' => '99887766',
            'no_hp' => '081234567890',
            'jabatan' => 'Ketua Program Studi',
            'fakultas_id' => $fakultas->id,
            'jurusan_id' => $jurusan->id,
            'prodi_id' => $prodi->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('flash_success');
        $this->assertDatabaseHas('users', [
            'email' => 'kaprodite@apjad.com',
            'nuptk' => '99887766',
            'no_hp' => '081234567890',
            'jabatan' => 'Ketua Program Studi',
            'role' => UserRole::PRODI,
            'fakultas_id' => $fakultas->id,
            'jurusan_id' => $jurusan->id,
            'prodi_id' => $prodi->id,
            'status' => 'aktif',
            'created_by' => $admin->id,
        ]);
    }

    public function test_organization_hierarchy_consistency_rejected_if_mismatched(): void
    {
        $superAdmin = $this->superAdmin();
        $fakultas2 = Fakultas::create(['nama' => 'Fakultas Lain', 'kode' => 'FL', 'status' => 'ACTIVE']);
        $jurusan = Jurusan::firstOrFail();

        $response = $this->actingAs($superAdmin)->post('/users', [
            'nama' => 'Jurusan Tidak Cocok',
            'email' => 'mismatch@apjad.com',
            'role' => UserRole::JURUSAN,
            'fakultas_id' => $fakultas2->id,
            'jurusan_id' => $jurusan->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('jurusan_id');
        $this->assertDatabaseMissing('users', ['email' => 'mismatch@apjad.com']);
    }

    public function test_admin_cannot_toggle_or_reset_password_of_super_admin_or_self_returns_403(): void
    {
        $admin = $this->adminAkun();
        $superAdmin = $this->superAdmin();

        $this->actingAs($admin)->post("/users/{$superAdmin->id}/toggle-status")->assertForbidden();
        $this->actingAs($admin)->post("/users/{$superAdmin->id}/reset-password")->assertForbidden();
        $this->actingAs($admin)->post("/users/{$admin->id}/toggle-status")->assertForbidden();
    }

    public function test_admin_can_toggle_user_status_and_block_deactivated_user(): void
    {
        $admin = $this->adminAkun();
        $target = User::where('email', 'randtian@gmail.com')->firstOrFail();

        $response = $this->actingAs($admin)->post("/users/{$target->id}/toggle-status");
        $response->assertRedirect();
        $response->assertSessionHas('flash_success');
        $this->assertEquals('nonaktif', $target->fresh()->status);

        auth()->logout();
        $loginResp = $this->post('/login', [
            'email' => 'randtian@gmail.com',
            'password' => 'password123',
        ]);
        $loginResp->assertSessionHas('flash_error');
        $this->assertGuest();

        $this->actingAs($admin)->post("/users/{$target->id}/toggle-status");
        $this->assertEquals('aktif', $target->fresh()->status);
    }

    public function test_admin_can_reset_password_and_delete_operational_user(): void
    {
        $admin = $this->adminAkun();
        $target = User::where('email', 'bdarell@gmail.com')->firstOrFail();

        $response = $this->actingAs($admin)->post("/users/{$target->id}/reset-password", [
            'new_password' => 'rahasia123',
            'new_password_confirmation' => 'rahasia123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('flash_success');
        $this->assertTrue(Hash::check('rahasia123', $target->fresh()->password));

        $selfDel = $this->actingAs($admin)->delete("/users/{$admin->id}");
        $selfDel->assertRedirect();
        $selfDel->assertSessionHas('flash_error', 'Anda tidak dapat menghapus akun Anda sendiri!');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);

        $delResp = $this->actingAs($admin)->delete("/users/{$target->id}");
        $delResp->assertRedirect();
        $delResp->assertSessionHas('flash_success');
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_fakultas_account_requires_fakultas_and_nullifies_jurusan_and_prodi(): void
    {
        $superAdmin = $this->superAdmin();
        $fakultas = Fakultas::firstOrFail();
        $jurusan = Jurusan::firstOrFail();
        $prodi = Prodi::firstOrFail();

        $failResp = $this->actingAs($superAdmin)->post('/users', [
            'nama' => 'Admin Fakultas Tanpa Scope',
            'email' => 'fakultas_noscope@apjad.com',
            'role' => UserRole::FAKULTAS,
            'fakultas_id' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $failResp->assertSessionHasErrors('fakultas_id');

        $okResp = $this->actingAs($superAdmin)->post('/users', [
            'nama' => 'Admin Fakultas Baru',
            'email' => 'fakultasbaru@apjad.com',
            'jabatan' => 'Wakil Dekan',
            'fakultas_id' => $fakultas->id,
            'jurusan_id' => $jurusan->id,
            'prodi_id' => $prodi->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $okResp->assertRedirect();
        $okResp->assertSessionHas('flash_success');
        $this->assertDatabaseHas('users', [
            'email' => 'fakultasbaru@apjad.com',
            'jabatan' => 'Wakil Dekan',
            'role' => UserRole::FAKULTAS,
            'fakultas_id' => $fakultas->id,
            'jurusan_id' => null,
            'prodi_id' => null,
            'status' => 'aktif',
        ]);
    }

    public function test_jurusan_account_requires_fakultas_and_jurusan_and_nullifies_prodi(): void
    {
        $superAdmin = $this->superAdmin();
        $fakultas = Fakultas::firstOrFail();
        $jurusanTIM = Jurusan::where('kode', 'TIM')->firstOrFail();
        $prodi = Prodi::firstOrFail();

        $failResp = $this->actingAs($superAdmin)->post('/users', [
            'nama' => 'Admin Jurusan Tanpa Jurusan',
            'email' => 'jurusan_noscope@apjad.com',
            'role' => UserRole::JURUSAN,
            'fakultas_id' => $fakultas->id,
            'jurusan_id' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $failResp->assertSessionHasErrors('jurusan_id');

        $okResp = $this->actingAs($superAdmin)->post('/users', [
            'nama' => 'Ketua Jurusan TIM',
            'email' => 'kajur_tim@apjad.com',
            'jabatan' => 'Ketua Jurusan',
            'jurusan_id' => $jurusanTIM->id,
            'prodi_id' => $prodi->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $okResp->assertRedirect();
        $okResp->assertSessionHas('flash_success');
        $this->assertDatabaseHas('users', [
            'email' => 'kajur_tim@apjad.com',
            'jabatan' => 'Ketua Jurusan',
            'role' => UserRole::JURUSAN,
            'fakultas_id' => $fakultas->id,
            'jurusan_id' => $jurusanTIM->id,
            'prodi_id' => null,
            'status' => 'aktif',
        ]);
    }

    public function test_prodi_account_requires_fakultas_jurusan_and_prodi(): void
    {
        $superAdmin = $this->superAdmin();
        $fakultas = Fakultas::firstOrFail();
        $jurusan = Jurusan::where('kode', 'TEI')->firstOrFail();
        $prodiTE = Prodi::where('kode', 'TE')->firstOrFail();

        $failResp = $this->actingAs($superAdmin)->post('/users', [
            'nama' => 'Admin Prodi Tanpa Prodi',
            'email' => 'prodi_noscope@apjad.com',
            'role' => UserRole::PRODI,
            'fakultas_id' => $fakultas->id,
            'jurusan_id' => $jurusan->id,
            'prodi_id' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $failResp->assertSessionHasErrors('prodi_id');

        $okResp = $this->actingAs($superAdmin)->post('/users', [
            'nama' => 'Admin Prodi TE Baru',
            'email' => 'prodibaru@apjad.com',
            'jabatan' => 'Ketua Program Studi',
            'prodi_id' => $prodiTE->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $okResp->assertRedirect();
        $okResp->assertSessionHas('flash_success');
        $this->assertDatabaseHas('users', [
            'email' => 'prodibaru@apjad.com',
            'jabatan' => 'Ketua Program Studi',
            'role' => UserRole::PRODI,
            'fakultas_id' => $fakultas->id,
            'jurusan_id' => $jurusan->id,
            'prodi_id' => $prodiTE->id,
            'status' => 'aktif',
        ]);
    }

    public function test_admin_editing_user_name_email_and_organization_scopes(): void
    {
        $admin = $this->adminAkun();
        $target = $this->prodi();
        $origFakultasId = $target->fakultas_id;
        $origJurusanId = $target->jurusan_id;
        $origProdiId = $target->prodi_id;

        $response = $this->actingAs($admin)->put("/users/{$target->id}", [
            'nama' => 'Admin Prodi TI Updated',
            'email' => $target->email,
            'role' => UserRole::PRODI,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('flash_success');
        $fresh = $target->fresh();
        $this->assertEquals('Admin Prodi TI Updated', $fresh->nama);
        $this->assertEquals($origFakultasId, $fresh->fakultas_id);
        $this->assertEquals($origJurusanId, $fresh->jurusan_id);
        $this->assertEquals($origProdiId, $fresh->prodi_id);

        $prodiTE = Prodi::where('kode', 'TE')->firstOrFail();
        $responseScope = $this->actingAs($admin)->put("/users/{$target->id}", [
            'nama' => 'Admin Prodi TE Updated',
            'email' => $target->email,
            'role' => UserRole::PRODI,
            'fakultas_id' => $prodiTE->fakultas_id,
            'jurusan_id' => $prodiTE->jurusan_id,
            'prodi_id' => $prodiTE->id,
        ]);
        $responseScope->assertRedirect();
        $this->assertEquals($prodiTE->id, $target->fresh()->prodi_id);
    }

    public function test_super_admin_and_admin_sidebars_and_action_buttons_follow_fix_md(): void
    {
        $superAdmin = $this->superAdmin();
        $admin = $this->adminAkun();

        // Super Admin fokus pada CRUD Akun & Generate Akun tanpa Master Data di sidebar
        $saPage = $this->actingAs($superAdmin)->get('/manajemen-akun/fakultas');
        $saPage->assertOk();
        $saPage->assertDontSee(route('master-data.fakultas.index'));
        $saPage->assertSee('Manajemen Akun');
        $saPage->assertSee('Generate Akun Excel');
        $saPage->assertSee('Riwayat Generate Akun');
        $saPage->assertSee('Download Template Excel');
        $saPage->assertSee('openEditUserModal');
        $saPage->assertSee('openResetPasswordModal');
        $saPage->assertSee('openDeleteUserModal');

        // Admin melihat Master Data serta fitur lengkap Manajemen Akun
        $adminPage = $this->actingAs($admin)->get('/manajemen-akun/fakultas');
        $adminPage->assertOk();
        $adminPage->assertSee('Master Data');
        $adminPage->assertDontSee('Akun Super Admin');
        $adminPage->assertSee('Semua Akun');
        $adminPage->assertSee('Akun Fakultas');
        $adminPage->assertSee('Akun Jurusan');
        $adminPage->assertSee('Akun Program Studi');
        $adminPage->assertSee('Generate Akun Excel');
        $adminPage->assertSee('Riwayat Generate Akun');
        $adminPage->assertSee('openEditUserModal');
        $adminPage->assertSee('openResetPasswordModal');
        $adminPage->assertSee('openDeleteUserModal');

        // Pastikan halaman Generate Akun Excel dapat dirender tanpa error
        $excelPage = $this->actingAs($superAdmin)->get(route('manajemen-akun.generate-excel'));
        $excelPage->assertOk();
        $excelPage->assertSee('Generate Akun Massal via Excel');
    }

    // =========================================================================
    // SKENARIO PENGUJIAN WAJIB FIX.MD BAGIAN 12 (GENERATE MASSAL & PREVIEW)
    // =========================================================================

    public function test_template_excel_download_and_preview_5_sample_rows_follow_fix_md(): void
    {
        $superAdmin = $this->superAdmin();

        // Nonaktifkan akun operasional bawaan seeder agar 5 baris contoh Fix.md Bagian 4.1 tidak bentrok jabatan aktif
        User::whereIn('role', [UserRole::FAKULTAS, UserRole::JURUSAN, UserRole::PRODI])->update(['status' => 'nonaktif']);

        // 1. Download template Excel resmi dan pastikan kosong tanpa data bawaan
        $downloadResp = $this->actingAs($superAdmin)->get(route('manajemen-akun.template-excel'));
        $downloadResp->assertOk();

        /** @var AccountBulkGeneratorService $bulkService */
        $bulkService = app(AccountBulkGeneratorService::class);
        $cleanTemplatePath = $bulkService->templatePath(false);
        $spreadsheet = IOFactory::load($cleanTemplatePath);
        $sheet = $spreadsheet->getActiveSheet();
        $this->assertSame(AccountBulkGeneratorService::TEMPLATE_HEADERS, $sheet->rangeToArray('A1:H1')[0]);
        $this->assertNull($sheet->getCell('A2')->getValue(), 'Template harus bersih dan tidak memiliki baris data dummy bawaan');
        @unlink($cleanTemplatePath);

        // 2. Uji parse & preview file dengan data contoh
        $samplePath = $bulkService->templatePath(true);
        $evaluation = $bulkService->parseAndPreviewFile($samplePath);
        @unlink($samplePath);

        $rows = $evaluation['rows'];
        $summary = $evaluation['summary'];

        $this->assertCount(5, $rows);
        $this->assertSame(5, $summary['valid']);
        $this->assertSame(0, $summary['error']);
        $this->assertSame(2, $summary['count_fakultas']);
        $this->assertSame(1, $summary['count_jurusan']);
        $this->assertSame(2, $summary['count_prodi']);

        $fttk = Fakultas::where('kode', 'FTTK')->firstOrFail();
        $tei = Jurusan::where('kode', 'TEI')->firstOrFail();
        $ti = Prodi::where('kode', 'TI')->firstOrFail();
        $te = Prodi::where('kode', 'TE')->firstOrFail();

        // Skenario 1: Dekan -> FAKULTAS
        $this->assertSame('Dekan', $rows[0]['jabatan']);
        $this->assertSame(UserRole::FAKULTAS, $rows[0]['role']);
        $this->assertSame($fttk->id, $rows[0]['fakultas_id']);
        $this->assertNull($rows[0]['jurusan_id']);
        $this->assertNull($rows[0]['prodi_id']);

        // Skenario 2: Wakil Dekan -> FAKULTAS
        $this->assertSame('Wakil Dekan', $rows[1]['jabatan']);
        $this->assertSame(UserRole::FAKULTAS, $rows[1]['role']);
        $this->assertSame($fttk->id, $rows[1]['fakultas_id']);
        $this->assertNull($rows[1]['jurusan_id']);
        $this->assertNull($rows[1]['prodi_id']);

        // Skenario 3: Ketua Jurusan -> JURUSAN & Fakultas otomatis mengikuti Jurusan
        $this->assertSame('Ketua Jurusan', $rows[2]['jabatan']);
        $this->assertSame(UserRole::JURUSAN, $rows[2]['role']);
        $this->assertSame($fttk->id, $rows[2]['fakultas_id']);
        $this->assertSame($tei->id, $rows[2]['jurusan_id']);
        $this->assertNull($rows[2]['prodi_id']);

        // Skenario 4: Ketua Program Studi TI -> PRODI & Jurusan/Fakultas otomatis mengikuti relasi induk
        $this->assertSame('Ketua Program Studi', $rows[3]['jabatan']);
        $this->assertSame(UserRole::PRODI, $rows[3]['role']);
        $this->assertSame($fttk->id, $rows[3]['fakultas_id']);
        $this->assertSame($tei->id, $rows[3]['jurusan_id']);
        $this->assertSame($ti->id, $rows[3]['prodi_id']);

        // Skenario 5: Ketua Program Studi TE -> PRODI & Jurusan/Fakultas otomatis mengikuti relasi induk
        $this->assertSame('Ketua Program Studi', $rows[4]['jabatan']);
        $this->assertSame(UserRole::PRODI, $rows[4]['role']);
        $this->assertSame($fttk->id, $rows[4]['fakultas_id']);
        $this->assertSame($tei->id, $rows[4]['jurusan_id']);
        $this->assertSame($te->id, $rows[4]['prodi_id']);
    }

    public function test_preview_flags_invalid_jabatan_mismatched_hierarchy_and_duplicates(): void
    {
        /** @var AccountBulkGeneratorService $bulkService */
        $bulkService = app(AccountBulkGeneratorService::class);

        $evaluation = $bulkService->evaluatePreviewRows([
            // Skenario 6: Jabatan tidak valid (Kaprodi / Kajur / Admin)
            [
                'row_number' => 2,
                'nama' => 'Salah Jabatan',
                'email' => 'salahjabatan@fttk.ac.id',
                'nuptk' => '10000001',
                'fakultas' => 'FTTK',
                'jurusan' => 'TEI',
                'prodi' => 'Teknik Informatika',
                'jabatan' => 'Kaprodi',
            ],
            // Skenario 7: Program Studi tidak sesuai Jurusan (TI di bawah TSP)
            [
                'row_number' => 3,
                'nama' => 'Salah Hierarki',
                'email' => 'salahhierarki@fttk.ac.id',
                'nuptk' => '10000002',
                'fakultas' => 'FTTK',
                'jurusan' => 'Teknik Sipil dan Perencanaan',
                'prodi' => 'Teknik Informatika',
                'jabatan' => 'Ketua Program Studi',
            ],
            // Skenario 10 & 11: Email & NUPTK duplikat antarbaris
            [
                'row_number' => 4,
                'nama' => 'Wakil Dekan Satu',
                'email' => 'kembar@fttk.ac.id',
                'nuptk' => '99999999',
                'fakultas' => 'FTTK',
                'jabatan' => 'Wakil Dekan',
            ],
            [
                'row_number' => 5,
                'nama' => 'Wakil Dekan Dua',
                'email' => 'kembar@fttk.ac.id',
                'nuptk' => '99999999',
                'fakultas' => 'FTTK',
                'jabatan' => 'Wakil Dekan',
            ],
        ]);

        $rows = $evaluation['rows'];
        $this->assertSame('ERROR', $rows[0]['status']);
        $this->assertSame('ERROR', $rows[1]['status']);
        $this->assertSame('ERROR', $rows[2]['status']);
        $this->assertSame('ERROR', $rows[3]['status']);
        $this->assertFalse($evaluation['summary']['can_commit']);
    }

    public function test_preview_revalidation_updates_role_and_parent_organization_automatically(): void
    {
        $superAdmin = $this->superAdmin();
        $fttk = Fakultas::where('kode', 'FTTK')->firstOrFail();
        $tim = Jurusan::where('kode', 'TIM')->firstOrFail();
        $prodiTP = Prodi::where('kode', 'TP')->firstOrFail();

        // Skenario 8 & 9: Mengubah Jabatan & Program Studi di Preview memperbarui Role, Jurusan, dan Fakultas otomatis
        $response = $this->actingAs($superAdmin)->postJson(route('manajemen-akun.generate-excel.revalidate'), [
            'rows' => [
                [
                    'row_number' => 2,
                    'nama' => 'Kaprodi Perkapalan',
                    'email' => 'kaproditp@fttk.ac.id',
                    'nuptk' => '77776666',
                    'jabatan' => 'Ketua Program Studi',
                    'prodi_id' => $prodiTP->id,
                    '_changed_field' => 'prodi',
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.rows.0.role', UserRole::PRODI);
        $response->assertJsonPath('data.rows.0.fakultas_id', $fttk->id);
        $response->assertJsonPath('data.rows.0.jurusan_id', $tim->id);
        $response->assertJsonPath('data.rows.0.prodi_id', $prodiTP->id);
        $response->assertJsonPath('data.rows.0.status', 'VALID');
    }

    public function test_excluding_error_row_allows_valid_rows_to_commit_with_auto_password_and_idempotency(): void
    {
        $superAdmin = $this->superAdmin();
        $fttk = Fakultas::where('kode', 'FTTK')->firstOrFail();
        $tim = Jurusan::where('kode', 'TIM')->firstOrFail();
        $prodiTP = Prodi::where('kode', 'TP')->firstOrFail();

        $idempotencyKey = 'batch-test-idempotency-001';

        $payloadRows = [
            // Baris 1: ERROR tetapi dikecualikan (excluded = true) -> Skenario 12
            [
                'row_number' => 2,
                'nama' => 'Baris Error Dikecualikan',
                'email' => 'invalid-email',
                'jabatan' => 'Kajur Tidak Baku',
                'excluded' => true,
            ],
            // Baris 2: VALID (Wakil Dekan)
            [
                'row_number' => 3,
                'nama' => 'Sitimah Laila',
                'email' => 'sitimah@fttk.ac.id',
                'nuptk' => '73123790',
                'no_hp' => '82377564312',
                'jabatan' => 'Wakil Dekan',
                'fakultas_id' => $fttk->id,
                'excluded' => false,
            ],
            // Baris 3: VALID (Ketua Program Studi TP)
            [
                'row_number' => 4,
                'nama' => 'Kaprodi Perkapalan',
                'email' => 'kaproditp@fttk.ac.id',
                'nuptk' => '88881111',
                'no_hp' => '0811223344',
                'jabatan' => 'Ketua Program Studi',
                'fakultas_id' => $fttk->id,
                'jurusan_id' => $tim->id,
                'prodi_id' => $prodiTP->id,
                'excluded' => false,
            ],
        ];

        // Skenario 13: Commit dengan password otomatis
        $commitResp = $this->actingAs($superAdmin)->postJson(route('manajemen-akun.generate-excel.commit'), [
            'password_method' => 'auto',
            'file_name' => 'batch-fttk.xlsx',
            'idempotency_key' => $idempotencyKey,
            'rows' => $payloadRows,
        ]);

        $commitResp->assertOk();
        $commitResp->assertJsonPath('success', true);
        $commitResp->assertJsonPath('created_count', 2);
        $token = $commitResp->json('credentials_token');
        $this->assertNotEmpty($token);

        $user1 = User::where('email', 'sitimah@fttk.ac.id')->firstOrFail();
        $user2 = User::where('email', 'kaproditp@fttk.ac.id')->firstOrFail();

        $this->assertSame(UserRole::FAKULTAS, $user1->role);
        $this->assertTrue($user1->must_change_password);
        $this->assertSame(UserRole::PRODI, $user2->role);
        $this->assertTrue($user2->must_change_password);
        $this->assertNotSame($user1->password, $user2->password);

        // One-time download kredensial sementara berhasil pada unduhan pertama dan 404 pada unduhan kedua
        $credDownload1 = $this->actingAs($superAdmin)->get(route('manajemen-akun.generate-excel.credentials', ['token' => $token]));
        $credDownload1->assertOk();

        $credDownload2 = $this->actingAs($superAdmin)->get(route('manajemen-akun.generate-excel.credentials', ['token' => $token]));
        $credDownload2->assertNotFound();

        // Skenario 15: Penekanan tombol ACC berulang kali dengan idempotency_key yang sama tidak menggandakan akun
        $repeatResp = $this->actingAs($superAdmin)->postJson(route('manajemen-akun.generate-excel.commit'), [
            'password_method' => 'auto',
            'file_name' => 'batch-fttk.xlsx',
            'idempotency_key' => $idempotencyKey,
            'rows' => $payloadRows,
        ]);

        $repeatResp->assertOk();
        $repeatResp->assertJsonPath('already_processed', true);
        $this->assertSame(1, AccountGenerationHistory::where('idempotency_key', $idempotencyKey)->count());

        // Pastikan Riwayat Generate Akun tercatat tanpa menyimpan password plaintext
        $historyPage = $this->actingAs($superAdmin)->get(route('manajemen-akun.riwayat-generate'));
        $historyPage->assertOk();
        $historyPage->assertSee('batch-fttk.xlsx');
        $historyPage->assertSee('sitimah@fttk.ac.id');
    }

    public function test_manual_password_mode_validates_passwords_before_acc(): void
    {
        $admin = $this->adminAkun();
        $fttk = Fakultas::where('kode', 'FTTK')->firstOrFail();

        // Skenario 14: Password manual kurang dari 6 karakter atau konfirmasi tidak cocok ditolak sebelum ACC
        $failResp = $this->actingAs($admin)->postJson(route('manajemen-akun.generate-excel.commit'), [
            'password_method' => 'manual',
            'rows' => [
                [
                    'row_number' => 2,
                    'nama' => 'Wakil Dekan Manual',
                    'email' => 'wdmanual@fttk.ac.id',
                    'jabatan' => 'Wakil Dekan',
                    'fakultas_id' => $fttk->id,
                    'manual_password' => '123',
                    'manual_password_confirmation' => '456',
                ],
            ],
        ]);

        $failResp->assertStatus(422);
        $this->assertDatabaseMissing('users', ['email' => 'wdmanual@fttk.ac.id']);

        // Password manual valid -> berhasil dibuat
        $okResp = $this->actingAs($admin)->postJson(route('manajemen-akun.generate-excel.commit'), [
            'password_method' => 'manual',
            'rows' => [
                [
                    'row_number' => 2,
                    'nama' => 'Wakil Dekan Manual',
                    'email' => 'wdmanual@fttk.ac.id',
                    'jabatan' => 'Wakil Dekan',
                    'fakultas_id' => $fttk->id,
                    'manual_password' => 'manualPass123',
                    'manual_password_confirmation' => 'manualPass123',
                ],
            ],
        ]);

        $okResp->assertOk();
        $created = User::where('email', 'wdmanual@fttk.ac.id')->firstOrFail();
        $this->assertTrue(Hash::check('manualPass123', $created->password));
        $this->assertFalse($created->must_change_password);
    }

    public function test_newly_added_organization_by_admin_is_immediately_available_in_form_and_excel_preview(): void
    {
        $admin = $this->adminAkun();
        $superAdmin = $this->superAdmin();
        $fttk = Fakultas::where('kode', 'FTTK')->firstOrFail();
        $tei = Jurusan::where('kode', 'TEI')->firstOrFail();

        // Skenario 19: Admin menambahkan Program Studi baru
        $this->actingAs($admin)->post(route('master-data.prodi.store'), [
            'fakultas_id' => $fttk->id,
            'jurusan_id' => $tei->id,
            'kode' => 'TRPL',
            'nama' => 'S1 Teknologi Rekayasa Perangkat Lunak',
        ])->assertRedirect();

        // Langsung tersedia di halaman form pembuatan akun Super Admin
        $formPage = $this->actingAs($superAdmin)->get(route('manajemen-akun.prodi'));
        $formPage->assertOk();
        $formPage->assertSee('S1 Teknologi Rekayasa Perangkat Lunak');

        // Langsung dikenali oleh proses Preview Excel
        /** @var AccountBulkGeneratorService $bulkService */
        $bulkService = app(AccountBulkGeneratorService::class);
        $eval = $bulkService->evaluatePreviewRows([
            [
                'row_number' => 2,
                'nama' => 'Kaprodi TRPL',
                'email' => 'kaproditrpl@fttk.ac.id',
                'fakultas' => 'FTTK',
                'jurusan' => 'Teknik Elektro dan Teknik Informatika',
                'prodi' => 'Teknologi Rekayasa Perangkat Lunak',
                'jabatan' => 'Ketua Program Studi',
            ],
        ]);

        $this->assertSame('VALID', $eval['rows'][0]['status']);
        $this->assertSame(UserRole::PRODI, $eval['rows'][0]['role']);
    }

    public function test_csv_upload_preview_blocks_admin_and_super_admin_creation(): void
    {
        $superAdmin = $this->superAdmin();

        // Skenario 18: Super Admin maupun Admin tidak dapat membuat akun Admin/Super Admin melalui unggahan Excel/CSV
        $csvContent = implode("\n", [
            'Nama Lengkap,Email,NUPTK,No. HP,Fakultas,Jurusan,Program Studi,Jabatan',
            'Akun Admin Ilegal,ilegal_admin@fttk.ac.id,123456,081234,FTTK,,,Admin',
            'Akun Super Ilegal,ilegal_super@fttk.ac.id,654321,081235,FTTK,,,Super Admin',
        ]);

        $file = UploadedFile::fake()->createWithContent('ilegal.csv', $csvContent);

        $resp = $this->actingAs($superAdmin)->postJson(route('manajemen-akun.generate-excel.preview'), [
            'file' => $file,
        ]);

        $resp->assertOk();
        $resp->assertJsonPath('data.summary.error', 2);
        $resp->assertJsonPath('data.summary.can_commit', false);
    }
}
