@php
    $currentActive = $active ?? 'dashboard';
@endphp

<style>
    /* Top-right User Badge Dropdown */
    .admin-header .user-badge {
        position: relative;
        cursor: pointer;
        user-select: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: box-shadow 0.18s ease, background 0.18s ease;
    }
    .admin-header .user-badge:hover {
        background: #e2e8f0;
    }
    .user-badge-chevron {
        width: 15px;
        height: 15px;
        color: #475569;
        transition: transform 0.2s ease;
    }
    .user-badge.dropdown-open .user-badge-chevron {
        transform: rotate(180deg);
    }
    .user-profile-dropdown {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        min-width: 190px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12);
        padding: 6px;
        display: none;
        flex-direction: column;
        gap: 2px;
        z-index: 999;
    }
    .user-profile-dropdown.show {
        display: flex;
    }
    .user-dropdown-item {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        padding: 9px 12px;
        border: none;
        background: transparent;
        color: #1e293b;
        font-size: 0.88rem;
        font-weight: 600;
        font-family: inherit;
        text-decoration: none;
        border-radius: 7px;
        cursor: pointer;
        text-align: left;
        transition: background 0.15s ease, color 0.15s ease;
    }
    .user-dropdown-item:hover {
        background: #f1f5f9;
        color: #0860be;
    }
    .user-dropdown-item.logout-item {
        color: #dc2626;
    }
    .user-dropdown-item.logout-item:hover {
        background: #fef2f2;
        color: #b91c1c;
    }
    .user-dropdown-divider {
        height: 1px;
        background: #f1f5f9;
        margin: 3px 0;
    }
</style>

<aside class="admin-sidebar">
    <div class="sidebar-top">
        <div class="sidebar-brand">
            <h2>APJAD</h2>
            <span class="sidebar-role-badge" style="font-size:10px; color:#38bdf8; text-transform:uppercase; letter-spacing:1px; margin-top:2px;">
                Admin Prodi ({{ Auth::user()->prodi->kode ?? 'PRODI' }})
            </span>
        </div>
        <nav class="sidebar-nav">
            <a href="{{ route('prodi.dashboard') }}" class="sidebar-link {{ $currentActive === 'dashboard' ? 'active' : '' }}" onclick="if(typeof showSection === 'function') { showSection(event, 'section-dashboard', this); }">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('prodi.dashboard') }}#section-dosen" class="sidebar-link {{ $currentActive === 'dosen' ? 'active' : '' }}" onclick="if(typeof showSection === 'function') { showSection(event, 'section-dosen', this); }">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Data Dosen</span>
            </a>

            <a href="{{ route('prodi.dashboard') }}#section-mk" class="sidebar-link {{ $currentActive === 'mk' ? 'active' : '' }}" onclick="if(typeof showSection === 'function') { showSection(event, 'section-mk', this); }">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
                <span>Mata Kuliah</span>
            </a>

            <a href="{{ route('prodi.dashboard') }}#section-rombel" class="sidebar-link {{ $currentActive === 'rombel' ? 'active' : '' }}" onclick="if(typeof showSection === 'function') { showSection(event, 'section-rombel', this); }">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>Rombel</span>
            </a>

            <a href="{{ route('prodi.dashboard') }}#section-jadwal" class="sidebar-link {{ $currentActive === 'jadwal' ? 'active' : '' }}" onclick="if(typeof showSection === 'function') { showSection(event, 'section-jadwal', this); }">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span>Jadwal Kuliah</span>
            </a>

            <a href="{{ route('prodi.dashboard') }}#section-catatan" class="sidebar-link {{ $currentActive === 'catatan' ? 'active' : '' }}" onclick="if(typeof showSection === 'function') { showSection(event, 'section-catatan', this); }">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    <line x1="9" y1="10" x2="15" y2="10"></line>
                </svg>
                <span>Catatan Jurusan</span>
                @if(!empty($catatanPendingCount) && $catatanPendingCount > 0)
                    <span style="margin-left:auto; background:#f59e0b; color:#fff; font-size:10px; font-weight:700; padding:1px 6px; border-radius:10px;">{{ $catatanPendingCount }}</span>
                @endif
            </a>

            <a href="{{ route('penjadwalan.index') }}" class="sidebar-link {{ $currentActive === 'penjadwalan' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                </svg>
                <span>Penjadwalan Otomatis</span>
            </a>
        </nav>
    </div>

    <form id="logoutForm" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const badge = document.querySelector('.admin-header .user-badge');
    if (!badge || badge.dataset.dropdownInitialized === 'true') return;
    badge.dataset.dropdownInitialized = 'true';

    const chevron = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    chevron.setAttribute('viewBox', '0 0 24 24');
    chevron.setAttribute('fill', 'none');
    chevron.setAttribute('stroke', 'currentColor');
    chevron.setAttribute('stroke-width', '2.2');
    chevron.setAttribute('stroke-linecap', 'round');
    chevron.setAttribute('stroke-linejoin', 'round');
    chevron.setAttribute('class', 'user-badge-chevron');
    chevron.innerHTML = '<polyline points="6 9 12 15 18 9"></polyline>';
    badge.appendChild(chevron);

    const dropdown = document.createElement('div');
    dropdown.className = 'user-profile-dropdown';
    dropdown.innerHTML = `
        <button type="button" class="user-dropdown-item" id="btnTopEditProfile">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <span>Edit Profile</span>
        </button>
        <div class="user-dropdown-divider"></div>
        <button type="button" class="user-dropdown-item logout-item" id="btnTopLogout">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Logout</span>
        </button>
    `;
    badge.appendChild(dropdown);

    badge.addEventListener('click', function (e) {
        e.stopPropagation();
        const isShown = dropdown.classList.toggle('show');
        badge.classList.toggle('dropdown-open', isShown);
    });

    dropdown.querySelector('#btnTopEditProfile').addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.remove('show');
        badge.classList.remove('dropdown-open');
        if (typeof openModal === 'function') {
            openModal('modalEditProfile');
        }
    });

    dropdown.querySelector('#btnTopLogout').addEventListener('click', function (e) {
        e.stopPropagation();
        const logoutForm = document.getElementById('logoutForm');
        if (logoutForm) {
            logoutForm.submit();
        }
    });

    document.addEventListener('click', function () {
        dropdown.classList.remove('show');
        badge.classList.remove('dropdown-open');
    });
});
</script>

