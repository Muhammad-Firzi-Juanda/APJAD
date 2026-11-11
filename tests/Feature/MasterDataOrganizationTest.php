<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Fakultas;
use App\Models\Jurusan;
use App\Models\Prodi;
use App\Models\User;
use App\Services\AccountAuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MasterDataOrganizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function admin(): User
    {
        return User::where('role', UserRole::ADMIN)->firstOrFail();
    }

    private function superAdmin(): User
    {
        return User::where('role', UserRole::SUPER_ADMIN)->firstOrFail();
    }

    public function test_seeder_populates_initial_organization_and_single_admin_idempotently(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(1, Fakultas::where('kode', 'FTTK')->count());
        $this->assertSame(1, Jurusan::where('kode', 'TEI')->where('nama', 'Teknik Elektro dan Teknik Informatika')->count());
        $this->assertSame(1, Jurusan::where('kode', 'TIM')->where('nama', 'Teknik Industri Maritim')->count());
        $this->assertSame(1, Jurusan::where('kode', 'TSP')->where('nama', 'Teknik Sipil dan Perencanaan')->count());
        $this->assertSame(8, Prodi::count());

        // Pastikan hanya 1 akun Super Admin dan 1 akun Admin aktif di sistem (Fix.md Bagian 1)
        $this->assertSame(1, User::where('role', UserRole::SUPER_ADMIN)->count());
        $this->assertSame(1, User::where('role', UserRole::ADMIN)->count());

        $admin = $this->admin();
        $this->assertNull($admin->fakultas_id);
        $this->assertNull($admin->jurusan_id);
        $this->assertNull($admin->prodi_id);
        $this->assertTrue($admin->isActive());

        $fttk = Fakultas::where('kode', 'FTTK')->firstOrFail();
        $jurusan = Jurusan::where('kode', 'TEI')->firstOrFail();
        $prodiTI = Prodi::where('kode', 'TI')->firstOrFail();
        $prodiTE = Prodi::where('kode', 'TE')->firstOrFail();

        $this->assertSame('ACTIVE', $fttk->status);
        $this->assertSame('ACTIVE', $jurusan->status);
        $this->assertSame($fttk->id, $jurusan->fakultas_id);
        $this->assertSame('S1 Teknik Informatika', $prodiTI->nama);
        $this->assertSame('ACTIVE', $prodiTI->status);
        $this->assertSame($jurusan->id, $prodiTI->jurusan_id);
        $this->assertSame($fttk->id, $prodiTI->fakultas_id);
        $this->assertSame('S1 Teknik Elektro', $prodiTE->nama);
        $this->assertSame('ACTIVE', $prodiTE->status);
    }

    public function test_admin_dashboard_displays_organization_summary_and_master_data_sidebar(): void
    {
        $response = $this->actingAs($this->admin())->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Total Fakultas');
        $response->assertSee('Total Jurusan');
        $response->assertSee('Total Program Studi');
        $response->assertSee('Total Akun');
        $response->assertSee('Master Data');
        $response->assertSee(route('master-data.fakultas.index'));
        $response->assertSee(route('master-data.jurusan.index'));
        $response->assertSee(route('master-data.prodi.index'));
    }

    public function test_super_admin_dashboard_hides_master_data_and_focuses_on_account_management(): void
    {
        $superAdmin = $this->superAdmin();

        $dashboard = $this->actingAs($superAdmin)->get(route('dashboard'));
        $dashboard->assertOk();
        $dashboard->assertDontSee('Ringkasan Master Data Organisasi');
        $dashboard->assertDontSee(route('master-data.fakultas.index'));
        $dashboard->assertSee('Manajemen Akun');
    }

    public function test_admin_can_render_fakultas_jurusan_and_prodi_index_pages(): void
    {
        $admin = $this->admin();

        $fakultasPage = $this->actingAs($admin)->get(route('master-data.fakultas.index'));
        $fakultasPage->assertOk();
        $fakultasPage->assertSee('Master Data Fakultas');
        $fakultasPage->assertSee('FTTK');

        $jurusanPage = $this->actingAs($admin)->get(route('master-data.jurusan.index'));
        $jurusanPage->assertOk();
        $jurusanPage->assertSee('Master Data Jurusan');
        $jurusanPage->assertSee('Teknik Elektro dan Teknik Informatika');

        $prodiPage = $this->actingAs($admin)->get(route('master-data.prodi.index'));
        $prodiPage->assertOk();
        $prodiPage->assertSee('Master Data Program Studi');
        $prodiPage->assertSee('S1 Teknik Informatika');
        $prodiPage->assertSee('S1 Teknik Elektro');
    }

    public function test_admin_can_create_edit_and_toggle_status_fakultas(): void
    {
        $admin = $this->admin();

        $createResponse = $this->actingAs($admin)->post(route('master-data.fakultas.store'), [
            'kode' => 'fe',
            'nama' => 'Fakultas Ekonomi dan Bisnis Maritim',
            'status' => 'ACTIVE',
        ]);

        $createResponse->assertRedirect();
        $createResponse->assertSessionHas('flash_success');
        $this->assertDatabaseHas('fakultas', [
            'kode' => 'FE',
            'nama' => 'Fakultas Ekonomi dan Bisnis Maritim',
            'status' => 'ACTIVE',
        ]);

        $fakultas = Fakultas::where('kode', 'FE')->firstOrFail();

        $updateResponse = $this->actingAs($admin)->put(route('master-data.fakultas.update', $fakultas), [
            'kode' => 'FEBM',
            'nama' => 'Fakultas Ekonomi dan Bisnis',
            'status' => 'ACTIVE',
        ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('fakultas', [
            'id' => $fakultas->id,
            'kode' => 'FEBM',
            'nama' => 'Fakultas Ekonomi dan Bisnis',
        ]);

        $toggleResponse = $this->actingAs($admin)->post(route('master-data.fakultas.toggle-status', $fakultas));

        $toggleResponse->assertRedirect();
        $this->assertDatabaseHas('fakultas', [
            'id' => $fakultas->id,
            'status' => 'INACTIVE',
        ]);
    }

    public function test_fakultas_validation_rejects_empty_and_duplicate_code(): void
    {
        $admin = $this->admin();

        $emptyResponse = $this->actingAs($admin)->post(route('master-data.fakultas.store'), [
            'kode' => '',
            'nama' => '',
        ]);

        $emptyResponse->assertSessionHasErrors(['kode', 'nama']);

        $duplicateResponse = $this->actingAs($admin)->post(route('master-data.fakultas.store'), [
            'kode' => 'FTTK',
            'nama' => 'Fakultas Baru',
        ]);

        $duplicateResponse->assertSessionHasErrors([
            'kode' => 'Kode fakultas sudah digunakan!',
        ]);
    }

    public function test_admin_can_create_edit_and_toggle_status_jurusan(): void
    {
        $admin = $this->admin();
        $fttk = Fakultas::where('kode', 'FTTK')->firstOrFail();

        $createResponse = $this->actingAs($admin)->post(route('master-data.jurusan.store'), [
            'fakultas_id' => $fttk->id,
            'kode' => 'tkl',
            'nama' => 'Teknik Kelautan dan Kemaritiman',
        ]);

        $createResponse->assertRedirect();
        $createResponse->assertSessionHas('flash_success');
        $this->assertDatabaseHas('jurusan', [
            'fakultas_id' => $fttk->id,
            'kode' => 'TKL',
            'nama' => 'Teknik Kelautan dan Kemaritiman',
            'status' => 'ACTIVE',
        ]);

        $jurusan = Jurusan::where('kode', 'TKL')->firstOrFail();

        $updateResponse = $this->actingAs($admin)->put(route('master-data.jurusan.update', $jurusan), [
            'fakultas_id' => $fttk->id,
            'kode' => 'TKM',
            'nama' => 'Teknik Kemaritiman Nusantara',
            'status' => 'ACTIVE',
        ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('jurusan', [
            'id' => $jurusan->id,
            'kode' => 'TKM',
            'nama' => 'Teknik Kemaritiman Nusantara',
        ]);

        $toggleResponse = $this->actingAs($admin)->post(route('master-data.jurusan.toggle-status', $jurusan));

        $toggleResponse->assertRedirect();
        $this->assertDatabaseHas('jurusan', [
            'id' => $jurusan->id,
            'status' => 'INACTIVE',
        ]);
    }

    public function test_admin_can_create_edit_and_toggle_status_prodi_with_consistent_hierarchy(): void
    {
        $admin = $this->admin();
        $fttk = Fakultas::where('kode', 'FTTK')->firstOrFail();
        $jurusan = Jurusan::where('kode', 'TEI')->firstOrFail();

        $createResponse = $this->actingAs($admin)->post(route('master-data.prodi.store'), [
            'fakultas_id' => $fttk->id,
            'jurusan_id' => $jurusan->id,
            'kode' => 'si',
            'nama' => 'S1 Sistem Informasi',
        ]);

        $createResponse->assertRedirect();
        $createResponse->assertSessionHas('flash_success');
        $this->assertDatabaseHas('prodi', [
            'fakultas_id' => $fttk->id,
            'jurusan_id' => $jurusan->id,
            'kode' => 'SI',
            'nama' => 'S1 Sistem Informasi',
            'status' => 'ACTIVE',
        ]);

        $prodi = Prodi::where('kode', 'SI')->firstOrFail();

        $updateResponse = $this->actingAs($admin)->put(route('master-data.prodi.update', $prodi), [
            'fakultas_id' => $fttk->id,
            'jurusan_id' => $jurusan->id,
            'kode' => 'SIF',
            'nama' => 'S1 Sistem Informasi Maritim',
            'status' => 'ACTIVE',
        ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('prodi', [
            'id' => $prodi->id,
            'kode' => 'SIF',
            'nama' => 'S1 Sistem Informasi Maritim',
        ]);

        $toggleResponse = $this->actingAs($admin)->post(route('master-data.prodi.toggle-status', $prodi));

        $toggleResponse->assertRedirect();
        $this->assertDatabaseHas('prodi', [
            'id' => $prodi->id,
            'status' => 'INACTIVE',
        ]);
    }

    public function test_prodi_creation_rejects_mismatched_fakultas_and_jurusan(): void
    {
        $admin = $this->admin();
        $fakultasLain = Fakultas::create([
            'kode' => 'FKIP',
            'nama' => 'Fakultas Keguruan dan Ilmu Pendidikan',
            'status' => 'ACTIVE',
        ]);
        $jurusanTEI = Jurusan::where('kode', 'TEI')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('master-data.prodi.store'), [
            'fakultas_id' => $fakultasLain->id,
            'jurusan_id' => $jurusanTEI->id,
            'kode' => 'PBIO',
            'nama' => 'S1 Pendidikan Biologi',
        ]);

        $response->assertSessionHasErrors([
            'jurusan_id' => 'Jurusan yang dipilih tidak berada di bawah Fakultas tersebut.',
        ]);
        $this->assertDatabaseMissing('prodi', ['kode' => 'PBIO']);
    }

    public function test_inactive_organization_cannot_be_selected_for_new_relations(): void
    {
        $admin = $this->admin();
        $inactiveFakultas = Fakultas::create([
            'kode' => 'FIKP',
            'nama' => 'Fakultas Ilmu Kelautan dan Perikanan',
            'status' => 'INACTIVE',
        ]);

        $responseJurusan = $this->actingAs($admin)->post(route('master-data.jurusan.store'), [
            'fakultas_id' => $inactiveFakultas->id,
            'kode' => 'IKL',
            'nama' => 'Ilmu Kelautan',
        ]);

        $responseJurusan->assertSessionHasErrors('fakultas_id');
        $this->assertDatabaseMissing('jurusan', ['kode' => 'IKL']);
    }

    public function test_cascading_dropdown_endpoints_return_only_active_children(): void
    {
        $superAdmin = $this->superAdmin();
        $fttk = Fakultas::where('kode', 'FTTK')->firstOrFail();
        $jurusanTEI = Jurusan::where('kode', 'TEI')->firstOrFail();

        Jurusan::create([
            'fakultas_id' => $fttk->id,
            'kode' => 'NONAKTIF',
            'nama' => 'Jurusan Nonaktif',
            'status' => 'INACTIVE',
        ]);

        Prodi::create([
            'fakultas_id' => $fttk->id,
            'jurusan_id' => $jurusanTEI->id,
            'kode' => 'PNON',
            'nama' => 'S1 Prodi Nonaktif',
            'status' => 'INACTIVE',
        ]);

        $jurusanResp = $this->actingAs($superAdmin)->getJson(route('api.fakultas.jurusan', $fttk));
        $jurusanResp->assertOk();
        $jurusanResp->assertJsonFragment(['kode' => 'TEI']);
        $jurusanResp->assertJsonMissing(['kode' => 'NONAKTIF']);

        $prodiResp = $this->actingAs($superAdmin)->getJson(route('api.jurusan.prodi', $jurusanTEI));
        $prodiResp->assertOk();
        $prodiResp->assertJsonFragment(['kode' => 'TI']);
        $prodiResp->assertJsonFragment(['kode' => 'TE']);
        $prodiResp->assertJsonMissing(['kode' => 'PNON']);
    }

    public function test_organizations_with_existing_relations_cannot_be_hard_deleted(): void
    {
        $admin = $this->admin();
        $fttk = Fakultas::where('kode', 'FTTK')->firstOrFail();
        $jurusanTEI = Jurusan::where('kode', 'TEI')->firstOrFail();
        $prodiTI = Prodi::where('kode', 'TI')->firstOrFail();

        $delFakultas = $this->actingAs($admin)->delete(route('master-data.fakultas.destroy', $fttk));
        $delFakultas->assertRedirect();
        $delFakultas->assertSessionHas('flash_error');
        $this->assertModelExists($fttk);

        $delJurusan = $this->actingAs($admin)->delete(route('master-data.jurusan.destroy', $jurusanTEI));
        $delJurusan->assertRedirect();
        $delJurusan->assertSessionHas('flash_error');
        $this->assertModelExists($jurusanTEI);

        $delProdi = $this->actingAs($admin)->delete(route('master-data.prodi.destroy', $prodiTI));
        $delProdi->assertRedirect();
        $delProdi->assertSessionHas('flash_error');
        $this->assertModelExists($prodiTI);
    }

    public function test_unused_organization_can_be_deleted(): void
    {
        $admin = $this->admin();
        $unusedFakultas = Fakultas::create([
            'kode' => 'FTEST',
            'nama' => 'Fakultas Kosong',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($admin)->delete(route('master-data.fakultas.destroy', $unusedFakultas));

        $response->assertRedirect();
        $response->assertSessionHas('flash_success');
        $this->assertDatabaseMissing('fakultas', ['id' => $unusedFakultas->id]);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function nonAdminRolesProvider(): array
    {
        return [
            'Super Admin' => [UserRole::SUPER_ADMIN],
            'Fakultas' => [UserRole::FAKULTAS],
            'Jurusan' => [UserRole::JURUSAN],
            'Prodi' => [UserRole::PRODI],
        ];
    }

    #[DataProvider('nonAdminRolesProvider')]
    public function test_non_admin_roles_including_super_admin_cannot_access_master_data_organization(string $role): void
    {
        $user = User::where('role', $role)->firstOrFail();
        $fttk = Fakultas::where('kode', 'FTTK')->firstOrFail();

        $this->actingAs($user)->get(route('master-data.fakultas.index'))->assertForbidden();
        $this->actingAs($user)->get(route('master-data.jurusan.index'))->assertForbidden();
        $this->actingAs($user)->get(route('master-data.prodi.index'))->assertForbidden();

        $this->actingAs($user)->post(route('master-data.fakultas.store'), [
            'kode' => 'HACK',
            'nama' => 'Hack Fakultas',
        ])->assertForbidden();

        $this->actingAs($user)->put(route('master-data.fakultas.update', $fttk), [
            'kode' => 'HACK',
            'nama' => 'Hack Fakultas',
        ])->assertForbidden();

        $this->actingAs($user)->delete(route('master-data.fakultas.destroy', $fttk))->assertForbidden();
    }

    public function test_strict_role_scope_validation_enforces_fix_md_matrix(): void
    {
        $fttk = Fakultas::where('kode', 'FTTK')->firstOrFail();
        $jurusan = Jurusan::where('kode', 'TEI')->firstOrFail();
        $prodi = Prodi::where('kode', 'TI')->firstOrFail();

        // 1. SUPER ADMIN & ADMIN -> semua NULL
        $adminScope = AccountAuthorizationService::validateStrictRoleScope(UserRole::ADMIN, $fttk->id, $jurusan->id, $prodi->id);
        $this->assertSame([
            'fakultas_id' => null,
            'jurusan_id' => null,
            'prodi_id' => null,
        ], $adminScope);

        // 2. FAKULTAS -> hanya fakultas_id yang terisi, jurusan_id & prodi_id NULL
        $fakultasScope = AccountAuthorizationService::validateStrictRoleScope(UserRole::FAKULTAS, $fttk->id, $jurusan->id, $prodi->id);
        $this->assertSame([
            'fakultas_id' => $fttk->id,
            'jurusan_id' => null,
            'prodi_id' => null,
        ], $fakultasScope);

        // 3. JURUSAN -> fakultas_id & jurusan_id wajib, prodi_id NULL
        $jurusanScope = AccountAuthorizationService::validateStrictRoleScope(UserRole::JURUSAN, $fttk->id, $jurusan->id, $prodi->id);
        $this->assertSame([
            'fakultas_id' => $fttk->id,
            'jurusan_id' => $jurusan->id,
            'prodi_id' => null,
        ], $jurusanScope);

        // 4. PRODI -> ketiganya wajib dan konsisten
        $prodiScope = AccountAuthorizationService::validateStrictRoleScope(UserRole::PRODI, $fttk->id, $jurusan->id, $prodi->id);
        $this->assertSame([
            'fakultas_id' => $fttk->id,
            'jurusan_id' => $jurusan->id,
            'prodi_id' => $prodi->id,
        ], $prodiScope);

        // 5. PRODI tanpa jurusan_id -> ditolak
        $this->expectException(ValidationException::class);
        AccountAuthorizationService::validateStrictRoleScope(UserRole::PRODI, $fttk->id, null, $prodi->id);
    }
}
