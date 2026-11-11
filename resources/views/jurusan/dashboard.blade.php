@extends('layouts.app')

@section('title', 'APJAD - Dashboard Jurusan')

@section('content')
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="sidebar-top">
            <div class="sidebar-brand">
                <h2>APJAD</h2>
                <span class="sidebar-role-badge" style="font-size:10px; color:#94a3b8; text-transform:uppercase; letter-spacing:1px; margin-top:2px;">Admin Jurusan</span>
            </div>
            <nav class="sidebar-nav">
                <a href="#section-dashboard" class="sidebar-link active" onclick="showSection(event, 'section-dashboard', this)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <a href="#section-dosen" class="sidebar-link" onclick="showSection(event, 'section-dosen', this)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>Data Dosen</span>
                </a>
                <a href="#section-mk" class="sidebar-link" onclick="showSection(event, 'section-mk', this)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5h16M6.5 15.5l5-3 5 3m-10 0l5-3 5 3M12 12l-9-5h18l-9 5"></path>
                    </svg>
                    <span>Mata Kuliah</span>
                </a>
                <a href="#section-rombel" class="sidebar-link" onclick="showSection(event, 'section-rombel', this)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span>Data Rombel</span>
                </a>
                <a href="#section-jadwal" class="sidebar-link" onclick="showSection(event, 'section-jadwal', this)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span>Jadwal Kuliah</span>
                </a>
            </nav>
        </div>

        <div class="sidebar-bottom">
            <button type="button" class="sidebar-link" onclick="openModal('modalEditProfile')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Edit Profile</span>
            </button>
            <form id="logoutForm" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
            <a href="{{ route('logout') }}" class="sidebar-link" onclick="event.preventDefault(); document.getElementById('logoutForm').submit();">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-header">
            <div class="user-badge">
                <span>{{ Auth::user()->role }}, <span class="user-name">{{ Auth::user()->nama }}</span></span>
            </div>
        </header>

        <section class="admin-content">
            @if (session('flash_success'))
                <div class="flash-alert success">
                    <span>{{ session('flash_success') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;">✕</button>
                </div>
            @endif

            <h1 class="page-title">Dashboard Jurusan</h1>

            {{-- Stats Cards --}}
            <div class="stats-grid content-section" id="section-dashboard">
                <div class="stat-card">
                    <div class="stat-icon dosen-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Total Dosen</p>
                        <p class="stat-value">{{ $stats['total_dosen'] }}</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon mata-kuliah-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 19.5h16M6.5 15.5l5-3 5 3m-10 0l5-3 5 3M12 12l-9-5h18l-9 5"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Total Mata Kuliah</p>
                        <p class="stat-value">{{ $stats['total_mata_kuliah'] }}</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon kelas-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                            <path d="M3 9h18M9 3v18"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Total Rombel</p>
                        <p class="stat-value">{{ $stats['total_rombel'] }}</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon fakultas-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                            <path d="M3 9h18"></path>
                            <circle cx="12" cy="15" r="2"></circle>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Jadwal Aktif</p>
                        <p class="stat-value">{{ $stats['total_jadwal'] }}</p>
                    </div>
                </div>
            </div>

            {{-- Data Dosen --}}
            <div id="section-dosen" class="content-section" style="margin-top: 32px; display: none;">
                <div class="tab-header">
                    <h2>Data Dosen</h2>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th style="width: 120px;">Kode Dosen</th>
                            <th>NIDN</th>
                            <th>Nama Dosen</th>
                            <th>Jabatan</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dosens as $i => $d)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <span class="badge-kode" style="display:inline-block; font-weight:700; color:#0369a1; background:#e0f2fe; padding:3px 10px; border-radius:6px; font-size:0.85rem; border:1px solid #bae6fd; letter-spacing:0.5px;">
                                        {{ $d->inisial ?? '-' }}
                                    </span>
                                </td>
                                <td><strong>{{ $d->nidn ?? '-' }}</strong></td>
                                <td>{{ $d->nama }}</td>
                                <td>{{ $d->jabatan ?? '-' }}</td>
                                <td>{{ $d->email ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="text-align:center; padding:24px;">Belum ada data dosen.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mata Kuliah per Semester --}}
            <div id="section-mk" class="content-section" style="margin-top: 40px; display: none;">
                <div class="tab-header" style="flex-wrap:wrap; gap:12px;">
                    <h2>Mata Kuliah per Semester</h2>
                    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                        <select id="filterSemester" onchange="filterMk()" class="filter-select">
                            <option value="">Semua Semester</option>
                            @foreach($mataKuliahs->keys()->sort() as $smt)
                                <option value="{{ $smt }}">Semester {{ $smt }}</option>
                            @endforeach
                        </select>
                        <select id="filterTipe" onchange="filterMk()" class="filter-select">
                            <option value="">Semua Tipe</option>
                            <option value="Wajib">Wajib</option>
                            <option value="Pilihan">Pilihan</option>
                        </select>
                        <input type="text" id="searchMk" placeholder="Cari kode / nama MK..." oninput="filterMk()" class="filter-input">
                    </div>
                </div>
                @forelse ($mataKuliahs as $semester => $listMk)
                    <div class="mk-semester-card table-card" data-semester="{{ $semester }}">
                        <div class="semester-header">
                            <h3>Semester {{ $semester }}</h3>
                            <span class="semester-stats">{{ $listMk->count() }} MK &middot; {{ $listMk->sum('sks') }} SKS &middot; {{ $listMk->sum('jumlah_kelas') }} kelas</span>
                        </div>
                        <div class="table-responsive">
                        <table class="data-table mk-table">
                            <thead>
                                <tr>
                                    <th style="white-space:nowrap;">Kode MK</th>
                                    <th>Nama Mata Kuliah</th>
                                    <th style="text-align:center;">SKS</th>
                                    <th style="text-align:center;">Tipe</th>
                                    <th>Dosen Ketua</th>
                                    <th>Dosen Anggota</th>
                                    <th style="text-align:center;">Kelas</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($listMk as $mk)
                                    <tr data-tipe="{{ $mk->tipe }}" data-kode="{{ strtolower($mk->clean_kode_mk) }}" data-nama="{{ strtolower($mk->nama_mk) }}">
                                        <td><span class="badge-kode">{{ $mk->clean_kode_mk }}</span></td>
                                        <td style="font-weight:600; color:#0f172a;">{{ $mk->nama_mk }}</td>
                                        <td style="text-align:center;"><span class="badge-sks">{{ $mk->sks }} SKS</span></td>
                                        <td style="text-align:center;"><span class="badge-tipe {{ strtolower($mk->tipe) }}">{{ $mk->tipe }}</span></td>
                                        <td>{{ $mk->dosenKetua->nama ?? '-' }}</td>
                                        <td>{{ $mk->dosenAnggota->nama ?? '-' }}</td>
                                        <td style="text-align:center;">
                                            <span style="display:inline-block; padding:2px 8px; background:#dbeafe; color:#1e40af; border-radius:12px; font-weight:700; font-size:0.8rem;">
                                                {{ $mk->kelas_label }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                    </div>
                @empty
                    <div class="table-card" style="text-align:center; padding:40px 20px; border-style:dashed; border-color:#cbd5e1;">
                        <p style="font-size:15px; color:#64748b;">Belum ada data mata kuliah.</p>
                    </div>
                @endforelse
                <p id="mkNoResult" class="no-result" style="display:none;">Tidak ada mata kuliah sesuai filter.</p>
            </div>

            {{-- ============ SECTION: ROMBEL ============ --}}
            <div id="section-rombel" class="content-section" style="margin-top: 40px; display: none;">
                <div class="tab-header">
                    <h2>Data Rombel / Kelas (Seluruh Jurusan)</h2>
                </div>
                <div class="table-card">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">No</th>
                                    <th>Kode MK</th>
                                    <th>Mata Kuliah</th>
                                    <th style="text-align:center; width: 80px;">Kelas</th>
                                    <th style="text-align:center; width: 90px;">Semester</th>
                                    <th style="text-align:center; width: 70px;">SKS</th>
                                    <th>Dosen Pengampu</th>
                                    <th style="text-align:center;">Peran</th>
                                    <th style="text-align:center;">Kapasitas</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rombels as $i => $r)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td style="white-space:nowrap;"><strong>{{ $r->clean_kode_mk }}</strong></td>
                                        <td>{{ $r->mataKuliah->nama_mk ?? '-' }}</td>
                                        <td style="text-align:center;">
                                            <span style="display:inline-block; padding:2px 8px; background:#dbeafe; color:#1e40af; border-radius:12px; font-weight:700; font-size:0.8rem;">
                                                {{ $r->kelas_label }}
                                            </span>
                                        </td>
                                        <td style="text-align:center;">{{ $r->mataKuliah->semester ?? '-' }}</td>
                                        <td style="text-align:center;">{{ $r->mataKuliah->sks ?? '-' }}</td>
                                        <td>{{ $r->dosenPengampu->nama ?? '-' }}</td>
                                        <td style="text-align:center;">
                                            <span class="badge-tipe {{ strtolower($r->peran_dosen) }}">
                                                {{ $r->peran_dosen }}
                                            </span>
                                        </td>
                                        <td style="text-align:center;">{{ $r->kapasitas ?? $r->mataKuliah->kapasitas_per_kelas ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" style="text-align:center; padding:24px;">Belum ada data rombel.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ============ SECTION: JADWAL ============ --}}
            <div id="section-jadwal" class="content-section" style="margin-top: 40px; display: none;">
                <div class="tab-header">
                    <h2>Jadwal Kuliah (Seluruh Jurusan)</h2>
                </div>

                @foreach ($hariList as $hari)
                    @if (isset($perHari[$hari]) && $perHari[$hari]->isNotEmpty())
                        <h3 style="margin: 18px 0 8px; font-size:15px; color:#1e3a5f;">{{ $hari }}</h3>
                        <div class="table-card">
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Jam</th>
                                            <th>Kode MK</th>
                                            <th>Mata Kuliah</th>
                                            <th style="text-align:center;">Semester</th>
                                            <th style="text-align:center;">SKS</th>
                                            <th style="text-align:center;">Kelas</th>
                                            <th>Dosen</th>
                                            <th>Ruangan</th>
                                            <th>Prodi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($perHari[$hari] as $j)
                                            <tr>
                                                <td style="white-space:nowrap; font-weight:600; color:#0860be;">{{ substr($j->jam_mulai, 0, 5) }} &ndash; {{ substr($j->jam_selesai, 0, 5) }}</td>
                                                <td style="white-space:nowrap;"><strong>{{ $j->clean_kode_mk }}</strong></td>
                                                <td style="font-weight:600; color:#0f172a;">{{ $j->rombel->mataKuliah->nama_mk ?? '-' }}</td>
                                                <td style="text-align:center;">{{ $j->rombel->mataKuliah->semester ?? '-' }}</td>
                                                <td style="text-align:center;"><span style="display:inline-block; padding:2px 8px; background:#e2e8f0; border-radius:12px; font-size:0.85rem; font-weight:600;">{{ $j->rombel->mataKuliah->sks ?? '-' }}</span></td>
                                                <td style="text-align:center;">
                                                    <span style="display:inline-block; padding:2px 8px; background:#dbeafe; color:#1e40af; border-radius:12px; font-weight:700; font-size:0.8rem;">
                                                        {{ $j->kelas_label }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span title="{{ $j->dosen->nama ?? '-' }}" style="font-weight:700; color:#1e3a5f; cursor:help; background:#f8fafc; padding:3px 8px; border-radius:6px; font-size:0.85rem; display:inline-block; border:1px solid #cbd5e1;">
                                                        {{ $j->dosen_inisial }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($j->ruangan?->kode)
                                                        <span style="display:inline-block; padding:3px 8px; background:#ecfdf5; color:#047857; font-weight:600; border-radius:6px; font-size:0.85rem;">{{ $j->ruangan->kode }}</span>
                                                    @else
                                                        <span style="color:#94a3b8; font-style:italic;">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span style="font-size:0.75rem; padding:2px 8px; border-radius:10px; font-weight:600; white-space:nowrap; {{ str_contains(strtolower($j->rombel->mataKuliah->prodi ?? ''), 'informatika') ? 'background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe;' : 'background:#fef3c7; color:#b45309; border:1px solid #fde68a;' }}">
                                                        {{ $j->rombel->mataKuliah->prodi ?? '-' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                @endforeach

                @if ($jadwals->isEmpty())
                    <div class="table-card" style="text-align:center; padding:40px 20px; border-style:dashed; border-color:#cbd5e1;">
                        <p style="font-size:15px; color:#64748b;">Belum ada jadwal aktif.</p>
                    </div>
                @endif
            </div>
        </section>
    </main>
</div>

{{-- MODAL: EDIT PROFILE --}}
<div class="modal-backdrop" id="modalEditProfile">
    <div class="modal-window">
        <div class="modal-header-blue">
            <h3>Edit Profile</h3>
        </div>
        <div class="modal-body">
            <form action="{{ route('profile.update') }}" method="POST" class="modal-form">
                @csrf
                <div class="modal-form-row">
                    <label for="profile_nama">Nama</label>
                    <div class="modal-input-container">
                        <input type="text" id="profile_nama" name="nama" value="{{ Auth::user()->nama }}" required>
                    </div>
                </div>
                <div class="modal-form-row">
                    <label for="profile_email">E-mail</label>
                    <div class="modal-input-container">
                        <input type="email" id="profile_email" name="email" value="{{ Auth::user()->email }}" required>
                    </div>
                </div>
                <div class="modal-form-row">
                    <label for="profile_password_lama">Password lama</label>
                    <div class="modal-input-container">
                        <input type="password" id="profile_password_lama" name="password_lama" placeholder="Isi jika ingin ganti password">
                        <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibilityModal('profile_password_lama', this)">
                            <svg id="profile_password_lama_eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="modal-form-row">
                    <label for="profile_password_baru">Password Baru</label>
                    <div class="modal-input-container">
                        <input type="password" id="profile_password_baru" name="password_baru" placeholder="Kosongkan jika tidak diubah">
                        <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibilityModal('profile_password_baru', this)">
                            <svg id="profile_password_baru_eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="modal-form-row">
                    <label for="profile_password_baru_confirmation">Konfirmasi Password Baru</label>
                    <div class="modal-input-container">
                        <input type="password" id="profile_password_baru_confirmation" name="password_baru_confirmation" placeholder="Ulangi password baru">
                        <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibilityModal('profile_password_baru_confirmation', this)">
                            <svg id="profile_password_baru_confirmation_eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="modal-footer-buttons">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalEditProfile')">Batal</button>
                    <button type="submit" class="btn-modal-submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
function showSection(event, id, element) {
    if (event) event.preventDefault();
    document.querySelectorAll('.content-section').forEach(function(sec) { sec.style.display = 'none'; });
    let target = document.getElementById(id);
    if(target) target.style.display = target.classList.contains('stats-grid') ? 'grid' : 'block';
    if (element) {
        document.querySelectorAll('.sidebar-nav .sidebar-link').forEach(function(link) { link.classList.remove('active'); });
        element.classList.add('active');
    }
}
function filterMk() {
    let smt = document.getElementById('filterSemester').value;
    let tipe = document.getElementById('filterTipe').value;
    let q = document.getElementById('searchMk').value.toLowerCase().trim();
    let visibleCards = 0;
    document.querySelectorAll('.mk-semester-card').forEach(function(card) {
        let cardSmt = card.dataset.semester;
        let matchSmt = !smt || cardSmt === smt;
        let rows = card.querySelectorAll('tbody tr');
        let visibleRows = 0;
        rows.forEach(function(tr) {
            let matchTipe = !tipe || tr.dataset.tipe === tipe;
            let matchSearch = !q || tr.dataset.kode.includes(q) || tr.dataset.nama.includes(q);
            let show = matchTipe && matchSearch;
            tr.style.display = show ? '' : 'none';
            if(show) visibleRows++;
        });
        let showCard = matchSmt && visibleRows > 0;
        card.style.display = showCard ? '' : 'none';
        if(showCard) visibleCards++;
    });
    document.getElementById('mkNoResult').style.display = visibleCards === 0 ? 'block' : 'none';
}
</script>
@endsection
