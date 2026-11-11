<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Fakultas;
use App\Models\Laboratory;
use App\Models\Prodi;
use App\Models\Room;
use App\Models\StudyProgramLaboratory;
use App\Models\StudyProgramRoom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FakultasModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function fakultasUser(): User
    {
        return User::where('role', UserRole::FAKULTAS)->firstOrFail();
    }

    /**
     * 1. Test User Fakultas can login and view Dashboard with Prompt #3 KPIs.
     */
    public function test_fakultas_can_view_dashboard(): void
    {
        $user = $this->fakultasUser();

        $response = $this->actingAs($user)->get(route('fakultas.dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard Fakultas');
        $response->assertSee('Total MKWU');
        $response->assertSee('Total MKWF');
        $response->assertSee('Total Ruangan');
        $response->assertSee('Total Laboratorium');
        $response->assertSee('Alokasi Ruangan');
        $response->assertSee('Alokasi Laboratorium');
        // Sidebar verification
        $response->assertSee('MKWU (Universitas)');
        $response->assertSee('MKWF (Fakultas)');
        $response->assertSee('Ruangan');
        $response->assertSee('Laboratorium');
    }

    /**
     * 2. Test User Fakultas CANNOT access account management.
     */
    public function test_fakultas_is_forbidden_from_account_management(): void
    {
        $user = $this->fakultasUser();

        $response = $this->actingAs($user)->get(route('manajemen-akun'));
        $response->assertForbidden();

        $responseUsers = $this->actingAs($user)->post(route('users.store'), [
            'nama' => 'Hacker User',
            'email' => 'hacker@siwalan.com',
            'password' => 'password123',
            'role' => 'Fakultas',
        ]);
        $responseUsers->assertForbidden();
    }

    /**
     * 3. Test MKWU (UNV) CRUD & Validation.
     */
    public function test_fakultas_can_crud_mkwu(): void
    {
        $user = $this->fakultasUser();

        // Index
        $response = $this->actingAs($user)->get(route('fakultas.mkwu'));
        $response->assertOk();
        $response->assertSee('MKWU (Mata Kuliah Wajib Universitas)');
        $response->assertSee('modalAddCourse');

        // Create MKWU
        $responseCreate = $this->actingAs($user)->post(route('fakultas.courses.store'), [
            'category_code' => 'UNV',
            'code' => 'UNV999',
            'name' => 'Wawasan Kemaritiman Global',
            'sks' => 2,
            'semester' => 3,
            'type' => 'THEORY',
            'status' => 'ACTIVE',
        ]);

        $responseCreate->assertRedirect(route('fakultas.mkwu'));
        $this->assertDatabaseHas('courses', [
            'code' => 'UNV999',
            'name' => 'Wawasan Kemaritiman Global',
            'faculty_id' => $user->fakultas_id,
        ]);

        $course = Course::where('code', 'UNV999')->firstOrFail();

        // Update MKWU
        $responseUpdate = $this->actingAs($user)->put(route('fakultas.courses.update', $course), [
            'code' => 'UNV999',
            'name' => 'Wawasan Kemaritiman Nusantara',
            'sks' => 3,
            'semester' => 3,
            'type' => 'THEORY',
            'status' => 'ACTIVE',
        ]);

        $responseUpdate->assertRedirect(route('fakultas.mkwu'));
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => 'Wawasan Kemaritiman Nusantara',
            'sks' => 3,
        ]);

        // Toggle Status
        $responseToggle = $this->actingAs($user)->post(route('fakultas.courses.toggle-status', $course));
        $responseToggle->assertRedirect(route('fakultas.mkwu'));
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'status' => 'INACTIVE',
        ]);

        // Delete MKWU
        $responseDelete = $this->actingAs($user)->delete(route('fakultas.courses.destroy', $course));
        $responseDelete->assertRedirect(route('fakultas.mkwu'));
        $this->assertDatabaseMissing('courses', [
            'id' => $course->id,
        ]);
    }

    /**
     * 4. Test MKWF (FST) CRUD & Validation.
     */
    public function test_fakultas_can_crud_mkwf(): void
    {
        $user = $this->fakultasUser();

        // Index
        $response = $this->actingAs($user)->get(route('fakultas.mkwf'));
        $response->assertOk();
        $response->assertSee('MKWF (Mata Kuliah Wajib Fakultas)');
        $response->assertSee('modalAddCourse');

        // Create MKWF
        $responseCreate = $this->actingAs($user)->post(route('fakultas.courses.store'), [
            'category_code' => 'FST',
            'code' => 'FST888',
            'name' => 'Kalkulus Lanjut',
            'sks' => 3,
            'semester' => 2,
            'type' => 'THEORY',
            'status' => 'ACTIVE',
        ]);

        $responseCreate->assertRedirect(route('fakultas.mkwf'));
        $this->assertDatabaseHas('courses', [
            'code' => 'FST888',
            'name' => 'Kalkulus Lanjut',
            'faculty_id' => $user->fakultas_id,
        ]);

        $course = Course::where('code', 'FST888')->firstOrFail();

        // Update MKWF
        $responseUpdate = $this->actingAs($user)->put(route('fakultas.courses.update', $course), [
            'code' => 'FST888',
            'name' => 'Kalkulus Multivariabel',
            'sks' => 4,
            'semester' => 2,
            'type' => 'THEORY',
            'status' => 'ACTIVE',
        ]);

        $responseUpdate->assertRedirect(route('fakultas.mkwf'));
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => 'Kalkulus Multivariabel',
            'sks' => 4,
        ]);

        // Toggle Status
        $responseToggle = $this->actingAs($user)->post(route('fakultas.courses.toggle-status', $course));
        $responseToggle->assertRedirect(route('fakultas.mkwf'));
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'status' => 'INACTIVE',
        ]);

        // Delete MKWF
        $responseDelete = $this->actingAs($user)->delete(route('fakultas.courses.destroy', $course));
        $responseDelete->assertRedirect(route('fakultas.mkwf'));
        $this->assertDatabaseMissing('courses', [
            'id' => $course->id,
        ]);
    }

    /**
     * 5. Scope Security: User Fakultas cannot edit/delete another faculty's course.
     */
    public function test_fakultas_cannot_access_or_modify_other_faculty_course(): void
    {
        $user = $this->fakultasUser();

        // Buat fakultas lain dan course miliknya
        $fakultasLain = Fakultas::create(['nama' => 'Fakultas Ekonomi', 'kode' => 'FE']);
        $catUNV = CourseCategory::where('code', 'UNV')->firstOrFail();
        $courseLain = Course::create([
            'code' => 'UNV-FE-01',
            'name' => 'Agama FE',
            'sks' => 2,
            'semester' => 1,
            'type' => 'THEORY',
            'faculty_id' => $fakultasLain->id,
            'course_category_id' => $catUNV->id,
            'status' => 'ACTIVE',
        ]);

        // Coba edit -> 403 Forbidden
        $response = $this->actingAs($user)->put(route('fakultas.courses.update', $courseLain), [
            'code' => 'UNV-FE-01',
            'name' => 'Hack Name',
            'sks' => 2,
            'semester' => 1,
            'type' => 'THEORY',
        ]);
        $response->assertForbidden();

        // Coba hapus -> 403 Forbidden
        $responseDelete = $this->actingAs($user)->delete(route('fakultas.courses.destroy', $courseLain));
        $responseDelete->assertForbidden();

        // Coba toggle status -> 403 Forbidden
        $responseToggle = $this->actingAs($user)->post(route('fakultas.courses.toggle-status', $courseLain));
        $responseToggle->assertForbidden();
    }

    /**
     * 6. Resource Ruangan CRUD, editable capacity, and status toggle.
     */
    public function test_fakultas_can_crud_rooms(): void
    {
        $user = $this->fakultasUser();

        // Index
        $response = $this->actingAs($user)->get(route('fakultas.rooms.index'));
        $response->assertOk();
        $response->assertSee('Room 1');
        $response->assertSee('Daftar Ruangan');
        $response->assertSee('Alokasi Ruangan ke Prodi');
        $response->assertSee('modalAddRoom');
        $response->assertSee('modalAddAllocation');

        // Create Room
        $responseCreate = $this->actingAs($user)->post(route('fakultas.rooms.store'), [
            'name' => 'Room 99 Custom',
            'capacity' => 48,
            'type' => 'CLASSROOM',
            'status' => 'ACTIVE',
        ]);

        $responseCreate->assertRedirect(route('fakultas.rooms.index'));
        $this->assertDatabaseHas('rooms', [
            'name' => 'Room 99 Custom',
            'capacity' => 48,
            'faculty_id' => $user->fakultas_id,
        ]);

        $room = Room::where('name', 'Room 99 Custom')->firstOrFail();

        // Update Room capacity (Editable capacity test)
        $responseUpdate = $this->actingAs($user)->put(route('fakultas.rooms.update', $room), [
            'name' => 'Room 99 Custom',
            'capacity' => 55, // updated capacity
            'type' => 'CLASSROOM',
            'status' => 'ACTIVE',
        ]);

        $responseUpdate->assertRedirect(route('fakultas.rooms.index'));
        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'capacity' => 55,
        ]);

        // Toggle Room Status
        $responseToggle = $this->actingAs($user)->post(route('fakultas.rooms.toggle-status', $room));
        $responseToggle->assertRedirect(route('fakultas.rooms.index'));
        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'status' => 'INACTIVE',
        ]);

        // Delete Room
        $responseDelete = $this->actingAs($user)->delete(route('fakultas.rooms.destroy', $room));
        $responseDelete->assertRedirect(route('fakultas.rooms.index'));
        $this->assertDatabaseMissing('rooms', [
            'id' => $room->id,
        ]);
    }

    /**
     * 7. Resource Laboratorium CRUD, editable capacity, and status toggle.
     */
    public function test_fakultas_can_crud_laboratories(): void
    {
        $user = $this->fakultasUser();

        // Index
        $response = $this->actingAs($user)->get(route('fakultas.laboratories.index'));
        $response->assertOk();
        $response->assertSee('Lab TI');
        $response->assertSee('Lab Simulasi');
        $response->assertSee('Daftar Laboratorium');
        $response->assertSee('Alokasi Laboratorium ke Prodi');
        $response->assertSee('modalAddLab');
        $response->assertSee('modalAddLabAllocation');

        // Create Lab
        $responseCreate = $this->actingAs($user)->post(route('fakultas.laboratories.store'), [
            'name' => 'Lab AI & Robotika',
            'capacity' => 30,
            'lab_type' => 'Kecerdasan Buatan',
            'status' => 'ACTIVE',
        ]);

        $responseCreate->assertRedirect(route('fakultas.laboratories.index'));
        $this->assertDatabaseHas('laboratories', [
            'name' => 'Lab AI & Robotika',
            'capacity' => 30,
            'faculty_id' => $user->fakultas_id,
        ]);

        $lab = Laboratory::where('name', 'Lab AI & Robotika')->firstOrFail();

        // Update Lab (Editable capacity)
        $responseUpdate = $this->actingAs($user)->put(route('fakultas.laboratories.update', $lab), [
            'name' => 'Lab AI & Robotika Advanced',
            'capacity' => 35,
            'lab_type' => 'Kecerdasan Buatan',
            'status' => 'ACTIVE',
        ]);

        $responseUpdate->assertRedirect(route('fakultas.laboratories.index'));
        $this->assertDatabaseHas('laboratories', [
            'id' => $lab->id,
            'name' => 'Lab AI & Robotika Advanced',
            'capacity' => 35,
        ]);

        // Toggle Status
        $responseToggle = $this->actingAs($user)->post(route('fakultas.laboratories.toggle-status', $lab));
        $responseToggle->assertRedirect(route('fakultas.laboratories.index'));
        $this->assertDatabaseHas('laboratories', [
            'id' => $lab->id,
            'status' => 'INACTIVE',
        ]);

        // Delete Lab
        $responseDelete = $this->actingAs($user)->delete(route('fakultas.laboratories.destroy', $lab));
        $responseDelete->assertRedirect(route('fakultas.laboratories.index'));
        $this->assertDatabaseMissing('laboratories', [
            'id' => $lab->id,
        ]);
    }

    /**
     * 8. Alokasi Ruangan ke Prodi (Many-to-Many).
     */
    public function test_fakultas_can_allocate_room_to_multiple_prodi(): void
    {
        $user = $this->fakultasUser();
        $room = Room::where('name', 'Room 1')->firstOrFail();
        $prodiTI = Prodi::where('kode', 'TI')->firstOrFail();
        $prodiTE = Prodi::where('kode', 'TE')->firstOrFail();

        // Alokasikan Room 1 ke TI
        $responseTI = $this->actingAs($user)->post(route('fakultas.allocations.rooms.store'), [
            'room_id' => $room->id,
            'study_program_id' => $prodiTI->id,
        ]);
        $responseTI->assertRedirect(route('fakultas.rooms.index', ['tab' => 'alokasi']));

        // Alokasikan Room 1 juga ke TE (Many-to-Many)
        $responseTE = $this->actingAs($user)->post(route('fakultas.allocations.rooms.store'), [
            'room_id' => $room->id,
            'study_program_id' => $prodiTE->id,
        ]);
        $responseTE->assertRedirect(route('fakultas.rooms.index', ['tab' => 'alokasi']));

        $this->assertDatabaseHas('study_program_rooms', [
            'room_id' => $room->id,
            'study_program_id' => $prodiTI->id,
        ]);
        $this->assertDatabaseHas('study_program_rooms', [
            'room_id' => $room->id,
            'study_program_id' => $prodiTE->id,
        ]);

        // Hapus salah satu alokasi
        $allocation = StudyProgramRoom::where('room_id', $room->id)
            ->where('study_program_id', $prodiTE->id)
            ->firstOrFail();

        $responseDelete = $this->actingAs($user)->delete(route('fakultas.allocations.rooms.destroy', $allocation));
        $responseDelete->assertRedirect(route('fakultas.rooms.index', ['tab' => 'alokasi']));

        $this->assertDatabaseMissing('study_program_rooms', [
            'id' => $allocation->id,
        ]);
        $this->assertDatabaseHas('study_program_rooms', [
            'room_id' => $room->id,
            'study_program_id' => $prodiTI->id,
        ]);
    }

    /**
     * 9. Alokasi Laboratorium ke Prodi (Many-to-Many).
     */
    public function test_fakultas_can_allocate_laboratory_to_multiple_prodi(): void
    {
        $user = $this->fakultasUser();
        $lab = Laboratory::where('name', 'Lab TI')->firstOrFail();
        $prodiTE = Prodi::where('kode', 'TE')->firstOrFail();

        // Alokasikan Lab TI ke TE
        $response = $this->actingAs($user)->post(route('fakultas.allocations.laboratories.store'), [
            'laboratory_id' => $lab->id,
            'study_program_id' => $prodiTE->id,
        ]);
        $response->assertRedirect(route('fakultas.laboratories.index', ['tab' => 'alokasi']));

        $this->assertDatabaseHas('study_program_laboratories', [
            'laboratory_id' => $lab->id,
            'study_program_id' => $prodiTE->id,
        ]);

        // Hapus alokasi
        $allocation = StudyProgramLaboratory::where('laboratory_id', $lab->id)
            ->where('study_program_id', $prodiTE->id)
            ->firstOrFail();

        $responseDelete = $this->actingAs($user)->delete(route('fakultas.allocations.laboratories.destroy', $allocation));
        $responseDelete->assertRedirect(route('fakultas.laboratories.index', ['tab' => 'alokasi']));

        $this->assertDatabaseMissing('study_program_laboratories', [
            'id' => $allocation->id,
        ]);
    }

    /**
     * 10. Test MKWU & MKWF tidak menampilkan status dan mendukung Bulk Delete (Select All).
     */
    public function test_mkwu_and_mkwf_do_not_display_status_column_and_supports_bulk_delete(): void
    {
        $user = $this->fakultasUser();

        // 1. Cek MKWU view
        $responseMkwu = $this->actingAs($user)->get(route('fakultas.mkwu'));
        $responseMkwu->assertOk();
        $responseMkwu->assertSee('selectAllCourses');
        $responseMkwu->assertSee('Hapus Terpilih');
        $responseMkwu->assertDontSee('Semua Status');

        // 2. Cek MKWF view
        $responseMkwf = $this->actingAs($user)->get(route('fakultas.mkwf'));
        $responseMkwf->assertOk();
        $responseMkwf->assertSee('selectAllCourses');
        $responseMkwf->assertSee('Hapus Terpilih');
        $responseMkwf->assertDontSee('Semua Status');

        // 3. Test Bulk Delete Courses
        $catUNV = CourseCategory::where('code', 'UNV')->firstOrFail();
        $c1 = Course::create([
            'faculty_id' => $user->fakultas_id,
            'course_category_id' => $catUNV->id,
            'code' => 'BULK-UNV-01',
            'name' => 'Mata Kuliah Hapus 1',
            'sks' => 2,
            'semester' => 1,
            'type' => 'THEORY',
            'status' => 'ACTIVE',
        ]);
        $c2 = Course::create([
            'faculty_id' => $user->fakultas_id,
            'course_category_id' => $catUNV->id,
            'code' => 'BULK-UNV-02',
            'name' => 'Mata Kuliah Hapus 2',
            'sks' => 2,
            'semester' => 1,
            'type' => 'THEORY',
            'status' => 'ACTIVE',
        ]);

        $responseBulk = $this->actingAs($user)->post(route('fakultas.courses.bulk-delete'), [
            'category_code' => 'UNV',
            'selected_ids' => [$c1->id, $c2->id],
        ]);

        $responseBulk->assertRedirect(route('fakultas.mkwu'));
        $this->assertDatabaseMissing('courses', ['id' => $c1->id]);
        $this->assertDatabaseMissing('courses', ['id' => $c2->id]);
    }

    /**
     * 11. Test penyatuan Ruangan & Laboratorium dalam 1 halaman dengan Kode, Tipe, Kapasitas, Alokasi Prodi, & Bulk Delete.
     */
    public function test_rooms_and_laboratories_unified_with_code_type_capacity_and_dropdown_prodi(): void
    {
        $user = $this->fakultasUser();
        $prodiTI = Prodi::where('kode', 'TI')->firstOrFail();

        // 1. Tampilan terpadu ruangan & laboratorium
        $response = $this->actingAs($user)->get(route('fakultas.rooms.index'));
        $response->assertOk();
        $response->assertSee('Kode Ruangan');
        $response->assertSee('Nama Ruangan');
        $response->assertSee('Tipe');
        $response->assertSee('Kapasitas');
        $response->assertSee('Alokasi Prodi');
        $response->assertSee('selectAllRooms');
        $response->assertSee('Hapus Terpilih');

        // 2. Tambah ruang tipe Labor
        $responseCreateLab = $this->actingAs($user)->post(route('fakultas.rooms.store'), [
            'code' => 'LAB-NEW-01',
            'name' => 'Laboratorium Multimedia & Game',
            'capacity' => 32,
            'type' => 'Labor',
            'study_program_id' => $prodiTI->id,
        ]);
        $responseCreateLab->assertRedirect(route('fakultas.rooms.index'));

        $this->assertDatabaseHas('rooms', [
            'code' => 'LAB-NEW-01',
            'name' => 'Laboratorium Multimedia & Game',
            'type' => 'Labor',
            'capacity' => 32,
        ]);

        $createdLab = Room::where('code', 'LAB-NEW-01')->firstOrFail();
        $this->assertDatabaseHas('study_program_rooms', [
            'room_id' => $createdLab->id,
            'study_program_id' => $prodiTI->id,
        ]);

        // 3. Ubah alokasi prodi via assign-prodi endpoint (dropdown di baris)
        $prodiTE = Prodi::where('kode', 'TE')->firstOrFail();
        $responseAssign = $this->actingAs($user)->post(route('fakultas.rooms.assign-prodi', $createdLab), [
            'study_program_id' => $prodiTE->id,
        ]);
        $responseAssign->assertRedirect(route('fakultas.rooms.index'));

        $this->assertDatabaseHas('study_program_rooms', [
            'room_id' => $createdLab->id,
            'study_program_id' => $prodiTE->id,
        ]);

        // 4. Test Bulk Delete Ruangan & Laboratorium
        $roomToDelete = Room::create([
            'faculty_id' => $user->fakultas_id,
            'code' => 'R-DEL-01',
            'name' => 'Ruang Hapus 1',
            'capacity' => 30,
            'type' => 'Kelas',
        ]);

        $responseBulkDelete = $this->actingAs($user)->post(route('fakultas.rooms.bulk-delete'), [
            'selected_ids' => [$createdLab->id, $roomToDelete->id],
        ]);
        $responseBulkDelete->assertRedirect(route('fakultas.rooms.index'));

        $this->assertDatabaseMissing('rooms', ['id' => $createdLab->id]);
        $this->assertDatabaseMissing('rooms', ['id' => $roomToDelete->id]);
    }

    /**
     * 12. Test dedicated Alokasi Ruangan & Alokasi Laboratorium pages and multi-Prodi allocation.
     */
    public function test_dedicated_allocation_pages_and_multi_prodi_allocation(): void
    {
        $user = $this->fakultasUser();
        $prodiTI = Prodi::where('kode', 'TI')->firstOrFail();
        $prodiTE = Prodi::where('kode', 'TE')->firstOrFail();

        // 1. Halaman Alokasi Ruangan
        $resRoomAlloc = $this->actingAs($user)->get(route('fakultas.allocations.rooms'));
        $resRoomAlloc->assertOk();
        $resRoomAlloc->assertSee('Alokasi Ruangan ke Program Studi');
        $resRoomAlloc->assertSee('Room 4');

        // Alokasikan Room 2 ke TI & TE sekaligus via study_program_ids[]
        $room2 = Room::where('name', 'Room 2')->firstOrFail();
        $resMultiRoom = $this->actingAs($user)->post(route('fakultas.allocations.rooms.store'), [
            'room_id' => $room2->id,
            'study_program_ids' => [$prodiTI->id, $prodiTE->id],
            'redirect_to' => 'allocations_page',
        ]);
        $resMultiRoom->assertRedirect(route('fakultas.allocations.rooms'));

        $this->assertDatabaseHas('study_program_rooms', ['room_id' => $room2->id, 'study_program_id' => $prodiTI->id]);
        $this->assertDatabaseHas('study_program_rooms', ['room_id' => $room2->id, 'study_program_id' => $prodiTE->id]);

        // 2. Halaman Alokasi Laboratorium + data awal Lab Energi & Lab Elektro
        $resLabAlloc = $this->actingAs($user)->get(route('fakultas.allocations.laboratories'));
        $resLabAlloc->assertOk();
        $resLabAlloc->assertSee('Alokasi Laboratorium ke Program Studi');
        $resLabAlloc->assertSee('Lab TI');
        $resLabAlloc->assertSee('Lab Simulasi');
        $resLabAlloc->assertSee('Lab Energi');
        $resLabAlloc->assertSee('Lab Elektro');
    }

    /**
     * 13. Test pencegahan hapus Ruangan / Laboratorium yang masih memiliki alokasi aktif.
     */
    public function test_cannot_delete_room_or_lab_with_active_allocations(): void
    {
        $user = $this->fakultasUser();
        $room4 = Room::where('name', 'Room 4')->firstOrFail();
        $labTI = Laboratory::where('name', 'Lab TI')->firstOrFail();

        // Room 4 masih punya alokasi ke TI & TE -> tidak boleh terhapus
        $resDelRoom = $this->actingAs($user)->delete(route('fakultas.rooms.destroy', $room4));
        $resDelRoom->assertRedirect(route('fakultas.rooms.index'));
        $resDelRoom->assertSessionHas('flash_error');
        $this->assertDatabaseHas('rooms', ['id' => $room4->id]);

        // Lab TI masih punya alokasi ke TI -> tidak boleh terhapus
        $resDelLab = $this->actingAs($user)->delete(route('fakultas.laboratories.destroy', $labTI));
        $resDelLab->assertRedirect(route('fakultas.laboratories.index'));
        $resDelLab->assertSessionHas('flash_error');
        $this->assertDatabaseHas('laboratories', ['id' => $labTI->id]);
    }

    /**
     * 14. Test validasi alokasi: tolak resource INACTIVE, prodi INACTIVE, dan alokasi duplikat.
     */
    public function test_allocation_rejects_inactive_resource_inactive_prodi_and_duplicates(): void
    {
        $user = $this->fakultasUser();
        $room3 = Room::where('name', 'Room 3')->firstOrFail();
        $prodiTI = Prodi::where('kode', 'TI')->firstOrFail();

        // 1. Jika ruangan INACTIVE -> gagal dialokasikan
        $room3->update(['status' => 'INACTIVE']);
        $resInactiveRoom = $this->actingAs($user)->post(route('fakultas.allocations.rooms.store'), [
            'room_id' => $room3->id,
            'study_program_id' => $prodiTI->id,
        ]);
        $resInactiveRoom->assertSessionHasErrors('room_id');
        $room3->update(['status' => 'ACTIVE']);

        // 2. Duplikat alokasi Room 5 -> TI (sudah ada dari seeder) -> ditolak
        $room5 = Room::where('name', 'Room 5')->firstOrFail();
        $resDuplicate = $this->actingAs($user)->post(route('fakultas.allocations.rooms.store'), [
            'room_id' => $room5->id,
            'study_program_id' => $prodiTI->id,
        ]);
        $resDuplicate->assertSessionHasErrors('study_program_id');
    }

    /**
     * 15. Test Fakultas dapat mengunduh Template Excel MKWU dan MKWF.
     */
    public function test_fakultas_can_download_mkwu_and_mkwf_templates(): void
    {
        $user = $this->fakultasUser();

        // Unduh Template MKWU
        $resMkwu = $this->actingAs($user)->get(route('fakultas.courses.template', ['category' => 'UNV']));
        $resMkwu->assertOk();
        $this->assertStringContainsString('template-mkwu', (string) $resMkwu->headers->get('content-disposition'));

        // Unduh Template MKWF
        $resMkwf = $this->actingAs($user)->get(route('fakultas.courses.template', ['category' => 'FST']));
        $resMkwf->assertOk();
        $this->assertStringContainsString('template-mkwf', (string) $resMkwf->headers->get('content-disposition'));
    }

    /**
     * 16. Test Import MKWU & MKWF dengan Smart Preview hasil edit langsung dan sinkronisasi ke mata_kuliah_details.
     */
    public function test_fakultas_can_import_and_edit_mkwu_and_mkwf_with_sync(): void
    {
        $user = $this->fakultasUser();

        $verifiedItems = [
            [
                'kode_mk' => 'UNV999',
                'nama_mk' => 'Etika Profesi Kemaritiman',
                'sks' => 2,
                'semester' => 1,
                'type' => 'THEORY',
                'butuh_lab' => false,
                'jumlah_kelas' => 2,
                'kapasitas_per_kelas' => 40,
            ],
        ];

        $resImport = $this->actingAs($user)->post(route('fakultas.courses.import'), [
            'category_code' => 'UNV',
            'verified_items' => json_encode($verifiedItems),
        ]);

        $resImport->assertRedirect(route('fakultas.mkwu'));
        $resImport->assertSessionHas('flash_success');

        // Pastikan tersimpan di courses
        $this->assertDatabaseHas('courses', [
            'code' => 'UNV999',
            'name' => 'Etika Profesi Kemaritiman',
            'faculty_id' => $user->fakultas_id,
        ]);

        // Pastikan tersinkron ke mata_kuliah_details
        $this->assertDatabaseHas('mata_kuliah_details', [
            'kode_mk' => 'UNV999',
            'nama_mk' => 'Etika Profesi Kemaritiman',
            'prodi' => 'Universitas',
            'kategori' => 'MK Universitas',
        ]);
    }

    /**
     * 17. Test Halaman Penjadwalan Fakultas dan Generate Jadwal dengan slot jam baru 15:30.
     */
    public function test_fakultas_penjadwalan_page_and_generator_with_slot_1530(): void
    {
        $user = $this->fakultasUser();

        // 1. Kunjungi halaman Penjadwalan Fakultas
        $resIndex = $this->actingAs($user)->get(route('fakultas.penjadwalan'));
        $resIndex->assertOk();
        $resIndex->assertSee('Penjadwalan Kuliah Fakultas');
        $resIndex->assertSee('15:30');
        $resIndex->assertSee('Generator Jadwal Otomatis Bebas Bentrok');

        // 2. Generate Jadwal dengan slot baru 15:30
        $resGenerate = $this->actingAs($user)->post(route('fakultas.penjadwalan.generate'), [
            'semester_mode' => '1',
            'target_mk' => 'all',
            'hari' => ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'],
            'jam_mulai' => ['07:30', '10:00', '13:00', '14:50', '15:30'],
        ]);

        $resGenerate->assertRedirect(route('fakultas.penjadwalan'));
        $resGenerate->assertSessionHas('flash_success');

        // 3. Test Reset / Hapus Semua Jadwal Fakultas
        $resDeleteAll = $this->actingAs($user)->delete(route('fakultas.penjadwalan.destroy-all'));
        $resDeleteAll->assertRedirect(route('fakultas.penjadwalan'));
        $resDeleteAll->assertSessionHas('flash_success');
    }
}
