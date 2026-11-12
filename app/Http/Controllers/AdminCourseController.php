<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\MataKuliah;
use App\Models\MataKuliahDetail;
use App\Models\Prodi;
use App\Models\Ruangan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminCourseController extends Controller
{
    /**
     * Helper to get prodi aliases.
     *
     * @return list<string>
     */
    protected function getProdiNames(Prodi $prodi): array
    {
        $prodiName = $prodi->nama;
        $cleanName = trim(preg_replace('/^(S1|D3|D4|S2)\s+/i', '', $prodiName));
        $names = [$prodiName, $cleanName];

        if (str_contains(strtolower($prodiName), 'informatik')) {
            $names = array_merge($names, ['Teknik Informatika', 'S1 Teknik Informatika', 'Teknik Informatik']);
        }
        if (str_contains(strtolower($prodiName), 'elektro')) {
            $names = array_merge($names, ['Teknik Elektro', 'S1 Teknik Elektro']);
        }

        return array_values(array_unique(array_filter($names)));
    }

    /**
     * Halaman Pengelolaan Mata Kuliah per Program Studi untuk Admin.
     */
    public function prodiIndex(Prodi $prodi, Request $request): View
    {
        $prodi->load(['jurusan', 'fakultas']);
        $prodiNames = $this->getProdiNames($prodi);

        // Data dosen KHUSUS prodi ini (tidak digabung dengan prodi lain)
        $dosens = Dosen::whereIn('prodi', $prodiNames)
            ->where('is_active', true)
            ->orderBy('nama')
            ->get();

        // Mata kuliah terdaftar untuk prodi ini
        $query = MataKuliahDetail::with(['dosenKetua', 'dosenAnggota', 'masterMk'])
            ->whereIn('prodi', $prodiNames)
            ->where('is_active', true)
            ->orderBy('semester')
            ->orderBy('kode_mk');

        if ($request->filled('semester') && $request->semester !== 'all') {
            $query->where('semester', (int) $request->semester);
        }

        if ($request->filled('tipe') && $request->tipe !== 'all') {
            $query->where('tipe', $request->tipe);
        }

        if ($request->filled('q')) {
            $keyword = trim($request->q);
            $query->where(function ($q) use ($keyword) {
                $q->where('kode_mk', 'like', "%{$keyword}%")
                    ->orWhere('nama_mk', 'like', "%{$keyword}%");
            });
        }

        $allMks = $query->get();
        $mataKuliahs = $allMks->groupBy('semester');

        // Navigasi semua prodi aktif untuk selector / switcher
        $allProdis = Prodi::with('jurusan')
            ->where('status', 'ACTIVE')
            ->orderBy('nama')
            ->get();

        $stats = [
            'total_mk' => $allMks->count(),
            'total_sks' => $allMks->sum('sks'),
            'total_dosen' => $dosens->count(),
            'total_kelas' => $allMks->sum('jumlah_kelas'),
        ];

        return view('admin.mata-kuliah-prodi', compact(
            'prodi',
            'prodiNames',
            'dosens',
            'mataKuliahs',
            'allProdis',
            'stats'
        ));
    }

    /**
     * Simpan Mata Kuliah Baru untuk Program Studi tertentu.
     */
    public function prodiStore(Request $request, Prodi $prodi): RedirectResponse
    {
        $prodiNames = $this->getProdiNames($prodi);

        $validated = $request->validate([
            'kode_mk' => ['required', 'string', 'max:50'],
            'nama_mk' => ['required', 'string', 'max:150'],
            'sks' => ['required', 'integer', 'min:1', 'max:6'],
            'semester' => ['required', 'integer', 'min:1', 'max:8'],
            'tipe' => ['required', 'in:Wajib,Pilihan'],
            'jumlah_kelas' => ['required', 'integer', 'min:1', 'max:10'],
            'kapasitas_per_kelas' => ['required', 'integer', 'min:1', 'max:200'],
            'dosen_ketua_id' => ['nullable', 'exists:dosens,id'],
            'dosen_anggota_id' => ['nullable', 'exists:dosens,id'],
            'butuh_lab' => ['nullable', 'boolean'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ], [
            'kode_mk.required' => 'Kode Mata Kuliah wajib diisi!',
            'nama_mk.required' => 'Nama Mata Kuliah wajib diisi!',
            'sks.required' => 'SKS wajib diisi!',
            'semester.required' => 'Semester wajib dipilih!',
        ]);

        // Verifikasi dosen ketua & anggota hanya milik prodi ini
        if (! empty($validated['dosen_ketua_id'])) {
            $dosen = Dosen::find($validated['dosen_ketua_id']);
            if ($dosen && ! in_array($dosen->prodi, $prodiNames, true)) {
                return back()->withInput()
                    ->withErrors(['dosen_ketua_id' => 'Dosen Ketua yang dipilih bukan dosen dari Program Studi ini!'])
                    ->with('flash_error', 'Dosen Ketua yang dipilih bukan dosen dari Program Studi ini!');
            }
        }

        if (! empty($validated['dosen_anggota_id'])) {
            $dosen = Dosen::find($validated['dosen_anggota_id']);
            if ($dosen && ! in_array($dosen->prodi, $prodiNames, true)) {
                return back()->withInput()
                    ->withErrors(['dosen_anggota_id' => 'Dosen Anggota yang dipilih bukan dosen dari Program Studi ini!'])
                    ->with('flash_error', 'Dosen Anggota yang dipilih bukan dosen dari Program Studi ini!');
            }
        }

        $cleanKode = preg_replace('/-[A-Za-z0-9]+$/', '', $validated['kode_mk']);
        $master = MataKuliah::firstOrCreate(
            ['kode' => $cleanKode],
            [
                'nama' => $validated['nama_mk'],
                'sks' => $validated['sks'],
                'semester' => $validated['semester'],
                'tipe' => 'Prodi',
                'kategori' => 'MK Prodi Wajib',
                'pemilik' => $prodi->nama,
                'level_id' => $prodi->id,
            ]
        );

        $validated['master_mk_id'] = $master->id;
        $validated['prodi'] = $prodi->nama;
        $validated['kategori'] = 'MK Prodi '.$validated['tipe'];
        $validated['butuh_lab'] = $request->boolean('butuh_lab');
        $validated['is_active'] = true;
        $validated['status_tawar'] = true;

        MataKuliahDetail::create($validated);

        return redirect()->route('admin.mata-kuliah.prodi', ['prodi' => $prodi->id])
            ->with('flash_success', 'Mata kuliah '.$validated['nama_mk'].' berhasil ditambahkan ke '.$prodi->nama.'!');
    }

    /**
     * Update Mata Kuliah Program Studi.
     */
    public function prodiUpdate(Request $request, Prodi $prodi, MataKuliahDetail $mataKuliah): RedirectResponse
    {
        $prodiNames = $this->getProdiNames($prodi);

        $validated = $request->validate([
            'kode_mk' => ['required', 'string', 'max:50'],
            'nama_mk' => ['required', 'string', 'max:150'],
            'sks' => ['required', 'integer', 'min:1', 'max:6'],
            'semester' => ['required', 'integer', 'min:1', 'max:8'],
            'tipe' => ['required', 'in:Wajib,Pilihan'],
            'jumlah_kelas' => ['required', 'integer', 'min:1', 'max:10'],
            'kapasitas_per_kelas' => ['required', 'integer', 'min:1', 'max:200'],
            'dosen_ketua_id' => ['nullable', 'exists:dosens,id'],
            'dosen_anggota_id' => ['nullable', 'exists:dosens,id'],
            'butuh_lab' => ['nullable', 'boolean'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        if (! empty($validated['dosen_ketua_id'])) {
            $dosen = Dosen::find($validated['dosen_ketua_id']);
            if ($dosen && ! in_array($dosen->prodi, $prodiNames, true)) {
                return back()->withInput()
                    ->withErrors(['dosen_ketua_id' => 'Dosen Ketua yang dipilih bukan dosen dari Program Studi ini!'])
                    ->with('flash_error', 'Dosen Ketua yang dipilih bukan dosen dari Program Studi ini!');
            }
        }

        if (! empty($validated['dosen_anggota_id'])) {
            $dosen = Dosen::find($validated['dosen_anggota_id']);
            if ($dosen && ! in_array($dosen->prodi, $prodiNames, true)) {
                return back()->withInput()
                    ->withErrors(['dosen_anggota_id' => 'Dosen Anggota yang dipilih bukan dosen dari Program Studi ini!'])
                    ->with('flash_error', 'Dosen Anggota yang dipilih bukan dosen dari Program Studi ini!');
            }
        }

        $validated['prodi'] = $prodi->nama;
        $validated['kategori'] = 'MK Prodi '.$validated['tipe'];
        $validated['butuh_lab'] = $request->boolean('butuh_lab');

        $cleanKode = preg_replace('/-[A-Za-z0-9]+$/', '', $validated['kode_mk']);
        $master = MataKuliah::firstOrCreate(
            ['kode' => $cleanKode],
            [
                'nama' => $validated['nama_mk'],
                'sks' => $validated['sks'],
                'semester' => $validated['semester'],
                'tipe' => 'Prodi',
                'kategori' => 'MK Prodi Wajib',
                'pemilik' => $prodi->nama,
                'level_id' => $prodi->id,
            ]
        );
        $validated['master_mk_id'] = $master->id;

        $mataKuliah->update($validated);

        return redirect()->route('admin.mata-kuliah.prodi', ['prodi' => $prodi->id])
            ->with('flash_success', 'Mata kuliah '.$validated['nama_mk'].' berhasil diperbarui!');
    }

    /**
     * Hapus Mata Kuliah Program Studi.
     */
    public function prodiDestroy(Request $request, Prodi $prodi, MataKuliahDetail $mataKuliah): RedirectResponse
    {
        $rombelIds = $mataKuliah->rombels()->pluck('id');
        if ($rombelIds->isNotEmpty()) {
            DB::table('jadwals')->whereIn('rombel_id', $rombelIds)->delete();
            DB::table('rombels')->whereIn('id', $rombelIds)->delete();
        }

        $namaMk = $mataKuliah->nama_mk;
        $mataKuliah->delete();

        return redirect()->route('admin.mata-kuliah.prodi', ['prodi' => $prodi->id])
            ->with('flash_success', 'Mata kuliah '.$namaMk.' berhasil dihapus.');
    }

    /**
     * Halaman Monitoring Jadwal Program Studi untuk Admin.
     */
    public function jadwalProdi(Prodi $prodi, Request $request): View
    {
        $prodi->load(['jurusan', 'fakultas']);
        $prodiNames = $this->getProdiNames($prodi);

        $jadwals = Jadwal::with(['rombel.mataKuliah', 'dosen', 'ruangan'])
            ->whereHas('rombel.mataKuliah', function ($q) use ($prodiNames) {
                $q->whereIn('prodi', $prodiNames);
            })
            ->where('is_active', true)
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();

        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $perHari = $jadwals->groupBy('hari');

        $allProdis = Prodi::with('jurusan')
            ->where('status', 'ACTIVE')
            ->orderBy('nama')
            ->get();

        $ruangans = Ruangan::where('is_active', true)->orderBy('kode')->get();
        $dosens = Dosen::whereIn('prodi', $prodiNames)->where('is_active', true)->orderBy('nama')->get();

        return view('admin.jadwal-prodi', compact(
            'prodi',
            'jadwals',
            'perHari',
            'hariList',
            'allProdis',
            'ruangans',
            'dosens'
        ));
    }
}
