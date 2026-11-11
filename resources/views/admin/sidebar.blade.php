@php
    $currentActive = $active ?? '';
    $isMasterOpen = str_starts_with($currentActive, 'master-');
    $isAkunOpen = str_starts_with($currentActive, 'manajemen-akun');
@endphp

<style>
    .sidebar-dropdown-toggle {
        width: 100%;
        border: none;
        background: transparent;
        text-align: left;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-family: inherit;
    }
    .sidebar-dropdown-toggle .toggle-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .sidebar-dropdown-toggle .sidebar-chevron {
        width: 16px;
        height: 16px;
        transition: transform 0.22s ease;
        opacity: 0.85;
        flex-shrink: 0;
    }
    .sidebar-dropdown-toggle.open .sidebar-chevron {
        transform: rotate(180deg);
    }
    .sidebar-submenu {
        display: none;
        flex-direction: column;
        gap: 4px;
        padding-left: 14px;
        margin-top: 2px;
        margin-bottom: 6px;
        border-left: 2px solid rgba(255, 255, 255, 0.18);
        margin-left: 18px;
    }
    .sidebar-submenu.open {
        display: flex;
    }
    .sidebar-submenu .sidebar-link {
        padding: 9px 12px;
        font-size: 0.88rem;
    }

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
            @if(Auth::user()->role === \App\Enums\UserRole::SUPER_ADMIN)
                <span class="sidebar-role-badge" style="font-size:10px; color:#38bdf8; text-transform:uppercase; letter-spacing:1px; margin-top:2px;">Super Admin</span>
            @else
                <span class="sidebar-role-badge" style="font-size:10px; color:#38bdf8; text-transform:uppercase; letter-spacing:1px; margin-top:2px;">Admin</span>
            @endif
        </div>
        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ $currentActive === 'dashboard' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                <span>Dashboard</span>
            </a>

            @if(Auth::user()->role === \App\Enums\UserRole::ADMIN)
                <!-- Dropdown Group: Master Data Organisasi (Eksklusif Admin Global) -->
                <button type="button" class="sidebar-link sidebar-dropdown-toggle {{ $isMasterOpen ? 'open active' : '' }}" onclick="toggleSidebarSubmenu('submenuMasterData', this)">
                    <span class="toggle-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                            <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                            <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                        </svg>
                        <span>Master Data</span>
                    </span>
                    <svg class="sidebar-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>

                <div id="submenuMasterData" class="sidebar-submenu {{ $isMasterOpen ? 'open' : '' }}">
                    <a href="{{ route('master-data.fakultas.index') }}" class="sidebar-link {{ $currentActive === 'master-fakultas' ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 21h18M3 7v14M21 7v14M6 11h2M6 15h2M11 11h2M11 15h2M16 11h2M16 15h2M3 7l9-4 9 4"></path>
                        </svg>
                        <span>Fakultas</span>
                    </a>

                    <a href="{{ route('master-data.jurusan.index') }}" class="sidebar-link {{ $currentActive === 'master-jurusan' ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                        <span>Jurusan</span>
                    </a>

                    <a href="{{ route('master-data.prodi.index') }}" class="sidebar-link {{ $currentActive === 'master-prodi' ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <span>Program Studi</span>
                    </a>
                </div>
            @endif

            <!-- Menu Tunggal: Manajemen Akun (mencakup seluruh tab dan fitur di dalam halaman) -->
            <a href="{{ route('manajemen-akun') }}" class="sidebar-link {{ $isAkunOpen ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>Manajemen Akun</span>
            </a>

            <!-- Menu Penjadwalan Otomatis -->
            <a href="{{ route('penjadwalan.index') }}" class="sidebar-link {{ $currentActive === 'penjadwalan' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                </svg>
                <span>Penjadwalan Kuliah</span>
            </a>
        </nav>
    </div>

    <form id="logoutForm" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>
</aside>

<script>
function toggleSidebarSubmenu(submenuId, btn) {
    const submenu = document.getElementById(submenuId);
    if (!submenu) return;
    const isOpen = submenu.classList.toggle('open');
    btn.classList.toggle('open', isOpen);
}

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
