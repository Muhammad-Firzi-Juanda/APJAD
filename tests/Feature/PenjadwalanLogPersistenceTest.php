<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PenjadwalanLogPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function superAdmin(): User
    {
        return User::where('role', 'Fakultas')->firstOrFail();
    }

    public function test_log_generate_persists_across_page_refreshes(): void
    {
        $admin = $this->superAdmin();

        // 1. Simulasikan proses generate
        $response = $this->actingAs($admin)->post(route('penjadwalan.generate'), [
            'semester' => ['2'],
            'prodi' => 'Teknik Informatika',
            'algoritma' => 'greedy',
        ]);

        $response->assertRedirect(route('penjadwalan.index'));

        // Pastikan cache dan persistent session terisi
        $this->assertTrue(Cache::has('penjadwalan_last_log'));
        $cachedLog = Cache::get('penjadwalan_last_log');
        $this->assertNotEmpty($cachedLog);

        // 2. Kunjungan pertama (setelah redirect)
        $firstVisit = $this->actingAs($admin)->get(route('penjadwalan.index'));
        $firstVisit->assertOk();
        $firstVisit->assertViewHas('logGenerate');
        $firstVisitLog = $firstVisit->viewData('logGenerate');
        $this->assertNotEmpty($firstVisitLog);

        // 3. Kunjungan kedua (simulasi Refresh / F5 - tanpa session flash)
        // Session flash biasanya hilang di request berikutnya
        $refreshVisit = $this->actingAs($admin)->get(route('penjadwalan.index'));
        $refreshVisit->assertOk();
        $refreshVisit->assertViewHas('logGenerate');
        $refreshLog = $refreshVisit->viewData('logGenerate');
        $this->assertNotEmpty($refreshLog, 'Log generate tidak boleh hilang saat halaman di-refresh!');
        $this->assertEquals($firstVisitLog, $refreshLog);
    }

    public function test_can_clear_log_generate_via_clear_log_route(): void
    {
        $admin = $this->superAdmin();

        // Set log awal di cache dan session
        $dummyLog = [
            [
                'semester' => 2,
                'jumlah' => 5,
                'log' => ['OK IF101 - Pemrograman | 1/1 kelas | Senin 07:30-10:00'],
                'peringatan' => [],
                'kegagalan' => [],
            ],
        ];
        Cache::forever('penjadwalan_last_log', $dummyLog);
        session(['log_generate' => $dummyLog]);

        $this->assertTrue(Cache::has('penjadwalan_last_log'));

        // Panggil endpoint clear-log
        $response = $this->actingAs($admin)->post(route('penjadwalan.clear-log'));
        $response->assertRedirect(route('penjadwalan.index'));
        $response->assertSessionHas('flash_success');

        // Pastikan cache dan session sudah bersih
        $this->assertFalse(Cache::has('penjadwalan_last_log'));
        $this->assertEmpty(session('log_generate'));

        // Cek halaman setelah dibersihkan
        $visit = $this->actingAs($admin)->get(route('penjadwalan.index'));
        $visit->assertOk();
        $this->assertEmpty($visit->viewData('logGenerate'));
    }
}
