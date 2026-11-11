<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MataKuliahImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function facultyUser(): User
    {
        return User::where('role', 'Fakultas')->firstOrFail();
    }

    public function test_super_admin_is_forbidden_from_academic_import(): void
    {
        $superAdmin = User::where('role', 'Super Admin')->firstOrFail();
        $file = UploadedFile::fake()->createWithContent('matkul.csv', "Kode MK,Nama MK,SKS,Semester\nTIF101,Test,3,1\n");

        $response = $this->actingAs($superAdmin)
            ->post('/penjadwalan/import', [
                'file' => $file,
            ]);

        $response->assertForbidden();
    }

    public function test_can_automatically_import_spreadsheet_with_standard_headers(): void
    {
        $csvContent = "Kode MK,Nama MK,SKS,Semester,Dosen\n";
        $csvContent .= "TIF101,Pemrograman Web,3,3,Budi Santoso\n";
        $csvContent .= "TIF102,Kalkulus,2,1,Siti Aminah\n";

        $file = UploadedFile::fake()->createWithContent('matkul.csv', $csvContent);

        $response = $this->actingAs($this->facultyUser())
            ->post('/penjadwalan/import', [
                'file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('flash_success');

        $this->assertDatabaseHas('mata_kuliah_details', [
            'kode_mk' => 'TIF101',
            'nama_mk' => 'Pemrograman Web',
            'sks' => 3,
            'semester' => 3,
        ]);

        $this->assertDatabaseHas('mata_kuliah_details', [
            'kode_mk' => 'TIF102',
            'nama_mk' => 'Kalkulus',
            'sks' => 2,
            'semester' => 1,
        ]);
    }

    public function test_can_automatically_import_spreadsheet_with_title_rows_and_varied_headers(): void
    {
        // Simulasi file Excel kampus nyata dengan judul di baris 1-3 dan variasi nama kolom
        $csvContent = "UNIVERSITAS MARITIM RAJA ALI HAJI\n";
        $csvContent .= "DAFTAR MATA KULIAH PRODI INFORMATIKA\n";
        $csvContent .= "\n";
        $csvContent .= "NO,KD MKK,MATA KULIAH,KREDIT SKS,SMSTR,DOSEN PENGAMPU\n";
        $csvContent .= "1,TIF301,Kecerdasan Buatan,3 SKS,5,Dr. John Doe\n";
        $csvContent .= "2,TIF302,Jaringan Komputer,3 SKS,5,Jane Smith\n";

        $file = UploadedFile::fake()->createWithContent('daftar_mk_prodi.csv', $csvContent);

        $response = $this->actingAs($this->facultyUser())
            ->post('/penjadwalan/import', [
                'file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('flash_success');

        $this->assertDatabaseHas('mata_kuliah_details', [
            'kode_mk' => 'TIF301',
            'nama_mk' => 'Kecerdasan Buatan',
            'sks' => 3,
            'semester' => 5,
            'tipe' => 'Wajib',
            'prodi' => 'Informatika',
        ]);

        $this->assertDatabaseHas('mata_kuliah_details', [
            'kode_mk' => 'TIF302',
            'nama_mk' => 'Jaringan Komputer',
            'sks' => 3,
            'semester' => 5,
        ]);
    }

    public function test_can_automatically_import_even_if_headers_are_minimal(): void
    {
        // Hanya ada kode dan nama mata kuliah, kolom lain otomatis terinferensi dan default aman
        $csvContent = "Kode,Matakuliah\n";
        $csvContent .= "MK-101,Pengantar Teknologi Informasi\n";

        $file = UploadedFile::fake()->createWithContent('minimal.csv', $csvContent);

        $response = $this->actingAs($this->facultyUser())
            ->post('/penjadwalan/import', [
                'file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('flash_success');

        $this->assertDatabaseHas('mata_kuliah_details', [
            'kode_mk' => 'MK-101',
            'nama_mk' => 'Pengantar Teknologi Informasi',
            'sks' => 3,
            'semester' => 1,
            'tipe' => 'Wajib',
            'prodi' => 'Informatika',
        ]);
    }

    public function test_can_automatically_import_semicolon_delimited_csv(): void
    {
        $csvContent = "Kode MK;Nama MK;SKS;Semester;Dosen\n";
        $csvContent .= "TIF201;Struktur Data;3;3;Budi Santoso\n";

        $file = UploadedFile::fake()->createWithContent('matkul_semicolon.csv', $csvContent);

        $response = $this->actingAs($this->facultyUser())
            ->post('/penjadwalan/import', [
                'file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('flash_success');

        $this->assertDatabaseHas('mata_kuliah_details', [
            'kode_mk' => 'TIF201',
            'nama_mk' => 'Struktur Data',
            'sks' => 3,
            'semester' => 3,
        ]);
    }
}
