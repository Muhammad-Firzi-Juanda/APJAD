@extends('layouts.app')

@section('title', 'APJAD - Dashboard ' . ($isSuperAdmin ? 'Super Admin' : 'Admin'))

@section('content')
<div class="admin-layout">
    @include('admin.sidebar', ['active' => 'dashboard'])

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

            @if (session('flash_error'))
                <div class="flash-alert error">
                    <span>{{ session('flash_error') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;">✕</button>
                </div>
            @endif

            <div style="margin-bottom: 24px;">
                <h1 class="page-title" style="margin-bottom: 4px;">Dashboard {{ $isSuperAdmin ? 'Super Admin' : 'Admin' }}</h1>
                <p style="color: #64748b; font-size: 0.95rem; margin: 0;">
                    {{ $isSuperAdmin ? 'Ringkasan operasional akun pengguna dan statistik sistem APJAD.' : 'Pusat tata kelola Master Data Organisasi dan statistik akun pengguna sistem APJAD.' }}
                </p>
            </div>

            @if(!$isSuperAdmin)
            <!-- Ringkasan Master Data Organisasi (Khusus Admin Akun) -->
            <div style="margin-bottom: 12px; font-size: 0.85rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">
                Ringkasan Master Data Organisasi
            </div>
            <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 28px;">
                <a href="{{ route('master-data.fakultas.index') }}" class="stat-card" style="text-decoration: none; color: inherit;">
                    <div class="stat-icon fakultas-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 21h18M3 7v14M21 7v14M6 11h2M6 15h2M11 11h2M11 15h2M16 11h2M16 15h2M3 7l9-4 9 4"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Total Fakultas</p>
                        <p class="stat-value">{{ $stats['org_fakultas'] ?? 0 }}</p>
                    </div>
                </a>

                <a href="{{ route('master-data.jurusan.index') }}" class="stat-card" style="text-decoration: none; color: inherit;">
                    <div class="stat-icon jurusan-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Total Jurusan</p>
                        <p class="stat-value">{{ $stats['org_jurusan'] ?? 0 }}</p>
                    </div>
                </a>

                <a href="{{ route('master-data.prodi.index') }}" class="stat-card" style="text-decoration: none; color: inherit;">
                    <div class="stat-icon prodi-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Total Program Studi</p>
                        <p class="stat-value">{{ $stats['org_prodi'] ?? 0 }}</p>
                    </div>
                </a>
            </div>
            @endif

            @if(!$isSuperAdmin)
                <!-- Ringkasan Akademik & Penjadwalan (Khusus Admin) -->
                <div style="margin-bottom: 12px; font-size: 0.85rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">
                    Ringkasan Akademik &amp; Penjadwalan
                </div>
                <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 28px;">
                    <a href="{{ route('fakultas.mkwu') }}" class="stat-card" style="text-decoration: none; color: inherit;">
                        <div class="stat-icon mata-kuliah-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-label">Total Mata Kuliah</p>
                            <p class="stat-value">{{ $stats['total_mata_kuliah'] ?? 0 }}</p>
                        </div>
                    </a>

                    <div class="stat-card">
                        <div class="stat-icon dosen-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-label">Total Dosen Aktif</p>
                            <p class="stat-value">{{ $stats['total_dosen'] ?? 0 }}</p>
                        </div>
                    </div>

                    <a href="{{ route('ruangan.index') }}" class="stat-card" style="text-decoration: none; color: inherit;">
                        <div class="stat-icon ruangan-icon" style="background:#eff6ff; color:#2563eb;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-label">Total Ruangan</p>
                            <p class="stat-value">{{ $stats['total_ruangan'] ?? 0 }}</p>
                        </div>
                    </a>

                    <a href="{{ route('penjadwalan.index') }}" class="stat-card" style="text-decoration: none; color: inherit;">
                        <div class="stat-icon jadwal-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-label">Jadwal Kuliah Aktif</p>
                            <p class="stat-value">{{ $stats['total_jadwal'] ?? 0 }}</p>
                        </div>
                    </a>
                </div>
            @endif

            @if($isSuperAdmin)
            <div style="margin-bottom: 12px; font-size: 0.85rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">
                Ringkasan Akun Pengguna
            </div>

            <!-- KPI Cards Murni Akun -->
            <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 28px;">
                <div class="stat-card">
                    <div class="stat-icon users-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Total Akun</p>
                        <p class="stat-value">{{ $stats['total_users'] }}</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon fakultas-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 21h18M3 7v14M21 7v14M6 11h2M6 15h2M11 11h2M11 15h2M16 11h2M16 15h2M3 7l9-4 9 4"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Akun Fakultas</p>
                        <p class="stat-value">{{ $stats['total_fakultas'] }}</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon jurusan-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Akun Jurusan</p>
                        <p class="stat-value">{{ $stats['total_jurusan'] }}</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon prodi-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Akun Prodi</p>
                        <p class="stat-value">{{ $stats['total_prodi'] }}</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon aktif-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Akun Aktif</p>
                        <p class="stat-value" style="color: #059669;">{{ $stats['total_aktif'] }}</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon nonaktif-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Akun Nonaktif</p>
                        <p class="stat-value" style="color: #dc2626;">{{ $stats['total_nonaktif'] }}</p>
                    </div>
                </div>
            </div>
            @endif


        </section>
    </main>
</div>

<!-- Modal Edit Profile -->
<div id="modalEditProfile" class="modal-backdrop">
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
function openModal(modalId) {
    document.getElementById(modalId).classList.add('show');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}
</script>
@endsection
