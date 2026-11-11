@extends('layouts.app')

@section('title', 'APJAD - Dashboard Manajemen Akun')

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

            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px; margin-bottom: 24px;">
                <div>
                    <h1 class="page-title" style="margin-bottom: 4px;">Dashboard Manajemen Akun</h1>
                    <p style="color: #64748b; font-size: 0.95rem; margin: 0;">
                        {{ $isSuperAdmin ? 'Pusat pengelolaan akun operasional Fakultas, Jurusan, dan Program Studi (CRUD Manual & Generate Massal via Excel).' : 'Pusat tata kelola Master Data Organisasi, akun pengguna, otorisasi role, dan generate akun massal via Excel.' }}
                    </p>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                    <a href="{{ route('manajemen-akun.template-excel') }}" style="display: inline-flex; align-items: center; gap: 7px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; padding: 9px 14px; border-radius: 8px; font-weight: 600; font-size: 0.86rem; text-decoration: none;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        <span>Download Template Excel</span>
                    </a>
                    <a href="{{ route('manajemen-akun.generate-excel') }}" style="display: inline-flex; align-items: center; gap: 7px; border: none; background: #16a34a; color: #ffffff; padding: 10px 16px; border-radius: 8px; font-weight: 600; font-size: 0.88rem; text-decoration: none; box-shadow: 0 2px 6px rgba(22, 163, 74, 0.25);">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="12" y1="18" x2="12" y2="12"></line>
                            <line x1="9" y1="15" x2="15" y2="15"></line>
                        </svg>
                        <span>Generate Akun Excel</span>
                    </a>
                    <a href="{{ route('manajemen-akun.tambah-manual') }}" class="btn-primary btn-add" style="display: inline-flex; align-items: center; gap: 8px; border: none; background: #0860be; color: #ffffff; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; text-decoration: none; box-shadow: 0 2px 6px rgba(8, 96, 190, 0.3);">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Tambah Akun Manual</span>
                    </a>
                </div>
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
                    <div class="stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #d97706;">
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
                    <div class="stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #d97706;">
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
                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #059669;">
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
                    <div class="stat-icon" style="background: rgba(239, 68, 68, 0.15); color: #dc2626;">
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
