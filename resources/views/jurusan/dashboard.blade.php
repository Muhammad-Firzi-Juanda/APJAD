@extends('layouts.app')

@section('title', 'APJAD - Dashboard Jurusan')

@section('content')
<div class="admin-layout">
    @include('jurusan.sidebar', ['active' => 'dashboard'])

    <main class="admin-main">
        <header class="admin-header">
            <div class="user-badge">
                <span>{{ Auth::user()->role }}, <span class="user-name">{{ Auth::user()->nama }}</span> ({{ $jurusan->kode ?? 'JURUSAN' }})</span>
            </div>
        </header>

        <section class="admin-content">
            @if (session('flash_success'))
                <div class="flash-alert success">
                    <span>{{ session('flash_success') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;">✕</button>
                </div>
            @endif

            @if (session('flash_error'))
                <div class="flash-alert error" style="background:#fee2e2; border-color:#fca5a5; color:#991b1b; padding:12px 16px; border-radius:8px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
                    <span>{{ session('flash_error') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;">✕</button>
                </div>
            @endif

            <div style="margin-bottom: 24px; display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
                <div>
                    <h1 class="page-title" style="margin-bottom: 4px;">Dashboard Jurusan</h1>
                    <p style="color: #64748b; font-size: 0.95rem; margin: 0;">
                        <strong>{{ $jurusan->nama }} ({{ $jurusan->kode }})</strong> &middot; {{ $jurusan->fakultas->nama ?? 'Fakultas Teknik dan Teknologi Kemaritiman' }}
                    </p>
                </div>
                <div style="display:flex; gap:10px;">
                    <a href="{{ route('jurusan.jadwal') }}" class="btn-primary" style="display:inline-flex; align-items:center; gap:8px; padding:9px 16px; text-decoration:none; font-weight:600; border-radius:8px; font-size:0.9rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <span>Monitoring Jadwal Terpadu</span>
                    </a>
                </div>
            </div>

            {{-- Alert Bentrok Dosen Lintas-Prodi jika ditemukan --}}
            @if (!empty($conflicts))
                <div style="background:#fffbeb; border:1px solid #fde68a; border-left:5px solid #f59e0b; padding:16px 20px; border-radius:8px; margin-bottom:24px;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                            <div>
                                <h4 style="margin:0; font-size:1rem; color:#92400e; font-weight:700;">Perhatian: Terdeteksi {{ count($conflicts) }} Potensi Jadwal Mengajar Dosen Tumpang-Tindih</h4>
                                <p style="margin:4px 0 0; font-size:0.875rem; color:#b45309;">
                                    Terdapat dosen yang terplot di lebih dari satu kelas/prodi pada hari dan interval jam yang sama. Silakan tinjau dan beri catatan evaluasi.
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('jurusan.jadwal') }}" style="padding:6px 12px; background:#f59e0b; color:#fff; border-radius:6px; font-weight:600; font-size:0.82rem; text-decoration:none; white-space:nowrap;">
                            Lihat Rincian Bentrok &rarr;
                        </a>
                    </div>
                </div>
            @endif

            {{-- ================================================================ --}}
            {{-- SECTION UTAMA: DASHBOARD (STATISTIK + PRODI + CATATAN TERKINI)   --}}
            {{-- ================================================================ --}}
            <div id="section-dashboard" class="content-section">
                {{-- KPI Stats Cards --}}
                <div class="stats-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap:18px; margin-bottom:28px;">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#eff6ff; color:#2563eb;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-label">Program Studi</p>
                            <p class="stat-value">{{ $stats['total_prodi'] }}</p>
                        </div>
                    </div>

                    <div class="stat-card" style="cursor:pointer;" onclick="showSection(event, 'section-dosen', document.querySelector('.sidebar-nav a[href*=\'section-dosen\']'))">
                        <div class="stat-icon dosen-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-label">Total Dosen Jurusan</p>
                            <p class="stat-value">{{ $stats['total_dosen'] }}</p>
                        </div>
                    </div>

                    <a href="{{ route('jurusan.jadwal') }}" class="stat-card" style="text-decoration:none; color:inherit; cursor:pointer;" title="Lihat selengkapnya di Monitoring Jadwal">
                        <div class="stat-icon mata-kuliah-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-label">Total Mata Kuliah</p>
                            <p class="stat-value">{{ $stats['total_mata_kuliah'] }}</p>
                        </div>
                    </a>

                    <a href="{{ route('jurusan.jadwal') }}" class="stat-card" style="text-decoration:none; color:inherit; cursor:pointer;" title="Lihat selengkapnya di Monitoring Jadwal">
                        <div class="stat-icon rombel-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-label">Total Rombel</p>
                            <p class="stat-value">{{ $stats['total_rombel'] }}</p>
                        </div>
                    </a>

                    <div class="stat-card">
                        <div class="stat-icon aktif-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-label">Jadwal Terplot</p>
                            <p class="stat-value">{{ $stats['total_jadwal'] }}</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="background:#fef3c7; color:#d97706;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-label">Catatan Pending</p>
                            <p class="stat-value" style="color:{{ $stats['total_catatan_pending'] > 0 ? '#d97706' : '#10b981' }};">
                                {{ $stats['total_catatan_pending'] }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Kartu Ringkasan Program Studi di bawah Jurusan --}}
                <div style="margin-bottom: 32px;">
                    <div class="tab-header" style="margin-bottom:16px;">
                        <h2>Program Studi di Bawah {{ $jurusan->kode }}</h2>
                    </div>

                    @if($prodis->isEmpty())
                        <div class="table-card" style="text-align:center; padding:48px 24px; border-style:dashed; border-color:#cbd5e1; background:#f8fafc; border-radius:12px;">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin:0 auto 12px;">
                                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                            </svg>
                            <h3 style="font-size:1.1rem; color:#475569; margin:0 0 6px;">Belum Ada Program Studi Terdaftar</h3>
                            <p style="color:#64748b; font-size:0.92rem; margin:0 auto; max-width:500px;">
                                Jurusan {{ $jurusan->nama }} saat ini belum memiliki data Program Studi yang terhubung pada Master Data. Seluruh penghitungan metrik akademik ditampilkan 0 secara aman.
                            </p>
                        </div>
                    @else
                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap:20px;">
                            @foreach($stats['prodi_summaries'] as $sum)
                                <div class="table-card" style="padding:20px; border-radius:10px; border:1px solid #e2e8f0; background:#ffffff; box-shadow:0 2px 4px rgba(0,0,0,0.03);">
                                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:14px;">
                                        <div>
                                            <span style="display:inline-block; font-size:0.75rem; font-weight:700; color:#0860be; background:#e0f2fe; padding:2px 8px; border-radius:4px; margin-bottom:6px;">
                                                KODE: {{ $sum['prodi']->kode ?? '-' }}
                                            </span>
                                            <h3 style="margin:0; font-size:1.1rem; color:#0f172a; font-weight:700;">{{ $sum['prodi']->nama }}</h3>
                                            <p style="margin:4px 0 0; font-size:0.85rem; color:#64748b;">
                                                Kaprodi: <strong>{{ $sum['prodi']->kaprodi ?? 'Belum Ditentukan' }}</strong>
                                            </p>
                                        </div>
                                        <span style="font-size:0.75rem; padding:3px 10px; border-radius:12px; font-weight:600; {{ $sum['prodi']->isActive() ? 'background:#dcfce7; color:#15803d;' : 'background:#fee2e2; color:#b91c1c;' }}">
                                            {{ $sum['prodi']->status }}
                                        </span>
                                    </div>

                                    <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:8px; padding:12px; background:#f8fafc; border-radius:8px; text-align:center; margin-bottom:16px;">
                                        <div>
                                            <span style="font-size:0.75rem; color:#64748b; display:block;">Dosen</span>
                                            <strong style="font-size:1.05rem; color:#1e293b;">{{ $sum['dosen_count'] }}</strong>
                                        </div>
                                        <div>
                                            <span style="font-size:0.75rem; color:#64748b; display:block;">MK</span>
                                            <strong style="font-size:1.05rem; color:#1e293b;">{{ $sum['mk_count'] }}</strong>
                                        </div>
                                        <div>
                                            <span style="font-size:0.75rem; color:#64748b; display:block;">Rombel</span>
                                            <strong style="font-size:1.05rem; color:#1e293b;">{{ $sum['rombel_count'] }}</strong>
                                        </div>
                                        <div>
                                            <span style="font-size:0.75rem; color:#64748b; display:block;">Jadwal</span>
                                            <strong style="font-size:1.05rem; color:#0860be;">{{ $sum['jadwal_count'] }}</strong>
                                        </div>
                                    </div>

                                    <div style="display:flex; justify-content:space-between; align-items:center;">
                                        <span style="font-size:0.82rem; color:{{ $sum['catatan_pending_count'] > 0 ? '#d97706' : '#64748b' }};">
                                            Catatan Menunggu: <strong>{{ $sum['catatan_pending_count'] }}</strong>
                                        </span>
                                        <a href="{{ route('jurusan.jadwal', ['prodi_id' => $sum['prodi']->id]) }}" style="font-size:0.85rem; font-weight:600; color:#0860be; text-decoration:none;">
                                            Filter Jadwal &rarr;
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Ringkasan Catatan Evaluasi Terakhir --}}
                @if($recentCatatan->isNotEmpty())
                    <div style="margin-bottom:32px;">
                        <div class="tab-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                            <h2>Catatan & Rekomendasi Evaluasi Terkini</h2>
                            <a href="{{ route('jurusan.evaluasi') }}" style="font-size:0.88rem; font-weight:600; color:#0860be; text-decoration:none;">
                                Kelola Semua Catatan &rarr;
                            </a>
                        </div>
                        <div class="table-card">
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Waktu</th>
                                            <th>Target Prodi</th>
                                            <th>Kategori</th>
                                            <th>Judul & Rekomendasi</th>
                                            <th style="text-align:center;">Status</th>
                                            <th>Tanggapan Admin Prodi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentCatatan as $c)
                                            <tr>
                                                <td style="font-size:0.82rem; color:#64748b; white-space:nowrap;">
                                                    {{ $c->created_at->format('d/m/Y H:i') }}
                                                </td>
                                                <td style="font-weight:600; color:#0f172a;">
                                                    {{ $c->prodi->nama ?? '-' }}
                                                </td>
                                                <td>
                                                    <span style="display:inline-block; font-size:0.75rem; padding:2px 8px; border-radius:6px; background:#f1f5f9; color:#334155; font-weight:600; border:1px solid #cbd5e1;">
                                                        {{ $c->kategori }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong style="color:#1e3a5f;">{{ $c->judul }}</strong>
                                                    <p style="margin:2px 0 0; font-size:0.85rem; color:#475569;">{{ Str::limit($c->catatan_rekomendasi, 90) }}</p>
                                                </td>
                                                <td style="text-align:center;">
                                                    @php
                                                        $statusColors = [
                                                            'Menunggu tanggapan' => 'background:#fef3c7; color:#b45309; border:1px solid #fde68a;',
                                                            'Sedang ditinjau' => 'background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd;',
                                                            'Ditanggapi' => 'background:#f3e8ff; color:#7e22ce; border:1px solid #e9d5ff;',
                                                            'Selesai' => 'background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;',
                                                        ];
                                                    @endphp
                                                    <span style="display:inline-block; font-size:0.75rem; padding:3px 8px; border-radius:12px; font-weight:700; {{ $statusColors[$c->status] ?? '' }}">
                                                        {{ $c->status }}
                                                    </span>
                                                </td>
                                                <td style="font-size:0.85rem; color:#334155;">
                                                    {{ $c->tanggapan_prodi ? Str::limit($c->tanggapan_prodi, 70) : '-' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- ================================================================ --}}
            {{-- SECTION: DATA DOSEN JURUSAN                                      --}}
            {{-- ================================================================ --}}
            <div id="section-dosen" class="content-section" style="margin-top: 24px; display: none;">
                <div class="tab-header" style="margin-bottom:16px;">
                    <h2>Data Dosen Jurusan ({{ $dosens->count() }} Dosen)</h2>
                </div>
                <div class="table-card">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">No</th>
                                    <th style="width: 120px;">Kode Dosen</th>
                                    <th>NIDN</th>
                                    <th>Nama Dosen</th>
                                    <th>Program Studi</th>
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
                                        <td>
                                            <span style="font-size:0.75rem; padding:2px 8px; border-radius:10px; font-weight:600; background:#f1f5f9; color:#334155; border:1px solid #cbd5e1;">
                                                {{ $d->prodi ?? '-' }}
                                            </span>
                                        </td>
                                        <td>{{ $d->email ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" style="text-align:center; padding:24px;">Belum ada data dosen pada jurusan ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
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
                    </div>
                </div>
                <div class="modal-form-row">
                    <label for="profile_password_baru">Password Baru</label>
                    <div class="modal-input-container">
                        <input type="password" id="profile_password_baru" name="password_baru" placeholder="Kosongkan jika tidak diubah">
                    </div>
                </div>
                <div class="modal-form-row">
                    <label for="profile_password_baru_confirmation">Konfirmasi Password Baru</label>
                    <div class="modal-input-container">
                        <input type="password" id="profile_password_baru_confirmation" name="password_baru_confirmation" placeholder="Ulangi password baru">
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
    
    // Sembunyikan semua section konten
    document.querySelectorAll('.content-section').forEach(function(sec) {
        sec.style.display = 'none';
    });
    
    // Tampilkan section yang dituju
    let target = document.getElementById(id);
    if (target) {
        target.style.display = 'block';
    }
    
    // Tandai sidebar link yang aktif
    if (element) {
        document.querySelectorAll('.sidebar-nav .sidebar-link').forEach(function(link) {
            link.classList.remove('active');
        });
        element.classList.add('active');
    }

    // Perbarui URL hash tanpa reload
    if (id && history.pushState) {
        history.pushState(null, null, '#' + id);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    let hash = window.location.hash ? window.location.hash.substring(1) : 'section-dashboard';
    let validSections = ['section-dashboard', 'section-dosen'];
    if (validSections.includes(hash)) {
        let matchingLink = document.querySelector(`.sidebar-nav a[href*="${hash}"]`) || document.querySelector('.sidebar-nav a');
        showSection(null, hash, matchingLink);
    }
});
</script>
@endsection
