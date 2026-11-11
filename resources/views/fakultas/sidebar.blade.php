@php
    $isCoursesGroup = in_array(($active ?? ''), ['mkwu', 'mkwf'], true);
    $isResourcesGroup = in_array(($active ?? ''), ['rooms', 'laboratories', 'allocations_rooms', 'allocations_laboratories'], true);
@endphp

<style>
    .sidebar-group {
        margin-top: 6px;
    }
    .sidebar-group-toggle {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 16px;
        background: transparent;
        border: none;
        border-radius: 10px;
        color: rgba(255, 255, 255, 0.85);
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        cursor: pointer;
        transition: background 0.2s ease, color 0.2s ease;
        text-align: left;
    }
    .sidebar-group-toggle:hover {
        background: rgba(255, 255, 255, 0.1);
        color: #ffffff;
    }
    .sidebar-group-toggle.open {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.08);
    }
    .sidebar-group-toggle .group-label {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .sidebar-group-toggle .chevron-icon {
        width: 16px;
        height: 16px;
        transition: transform 0.22s ease;
        opacity: 0.85;
    }
    .sidebar-group-toggle.open .chevron-icon {
        transform: rotate(180deg);
    }
    .sidebar-group-items {
        display: none;
        flex-direction: column;
        gap: 4px;
        padding: 6px 0 6px 12px;
        margin-left: 14px;
        margin-top: 4px;
        border-left: 1.5px solid rgba(255, 255, 255, 0.22);
    }
    .sidebar-group-items.show {
        display: flex;
    }
    .sidebar-group-items .sidebar-link {
        padding: 9px 14px;
        font-size: 13.5px;
    }

    /* Top-right User Badge Dropdown */
    .admin-header .user-badge {
        position: relative;
        cursor: pointer;
        user-select: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: box-shadow 0.2s ease, background 0.2s ease;
    }
    .admin-header .user-badge:hover {
        background: #e2e8f0;
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
        z-index: 1200;
        display: none;
        flex-direction: column;
        gap: 2px;
    }
    .user-profile-dropdown.show {
        display: flex;
    }
    .user-profile-dropdown-item {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 12px;
        border: none;
        background: transparent;
        border-radius: 7px;
        font-size: 13px;
        font-weight: 600;
        color: #1e293b;
        text-decoration: none;
        cursor: pointer;
        text-align: left;
        transition: background 0.15s ease, color 0.15s ease;
    }
    .user-profile-dropdown-item:hover {
        background: #f1f5f9;
        color: #0284c7;
    }
    .user-profile-dropdown-item.logout-item {
        color: #dc2626;
    }
    .user-profile-dropdown-item.logout-item:hover {
        background: #fef2f2;
        color: #b91c1c;
    }
    .user-profile-dropdown-divider {
        height: 1px;
        background: #f1f5f9;
        margin: 4px 0;
    }
</style>

<aside class="admin-sidebar">
    <div class="sidebar-top">
        <div class="sidebar-brand">
            <h2>APJAD</h2>
            <span class="sidebar-role-badge" style="font-size:10px; color:#38bdf8; text-transform:uppercase; letter-spacing:1px; margin-top:2px;">
                Fakultas ({{ Auth::user()->fakultas->kode ?? 'FTTK' }})
            </span>
        </div>

        <nav class="sidebar-nav">
            <a href="{{ route('fakultas.dashboard') }}" class="sidebar-link {{ ($active ?? '') === 'dashboard' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                <span>Dashboard</span>
            </a>

            {{-- Collapsible Group: Mata Kuliah Fakultas --}}
            <div class="sidebar-group">
                <button type="button"
                        class="sidebar-group-toggle {{ $isCoursesGroup ? 'open' : '' }}"
                        onclick="toggleSidebarGroup('groupCoursesFakultas', this)">
                    <span class="group-label">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                        <span>Mata Kuliah Fakultas</span>
                    </span>
                    <svg class="chevron-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>

                <div id="groupCoursesFakultas" class="sidebar-group-items {{ $isCoursesGroup ? 'show' : '' }}">
                    <a href="{{ route('fakultas.mkwu') }}" class="sidebar-link {{ ($active ?? '') === 'mkwu' ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                        <span>MKWU (Universitas)</span>
                    </a>

                    <a href="{{ route('fakultas.mkwf') }}" class="sidebar-link {{ ($active ?? '') === 'mkwf' ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                        </svg>
                        <span>MKWF (Fakultas)</span>
                    </a>
                </div>
            </div>

            {{-- Collapsible Group: Resources --}}
            <div class="sidebar-group">
                <button type="button"
                        class="sidebar-group-toggle {{ $isResourcesGroup ? 'open' : '' }}"
                        onclick="toggleSidebarGroup('groupResourcesFakultas', this)">
                    <span class="group-label">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="9" y1="3" x2="9" y2="21"></line>
                        </svg>
                        <span>Resources</span>
                    </span>
                    <svg class="chevron-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>

                <div id="groupResourcesFakultas" class="sidebar-group-items {{ $isResourcesGroup ? 'show' : '' }}">
                    <a href="{{ route('fakultas.rooms.index') }}" class="sidebar-link {{ ($active ?? '') === 'rooms' ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        <span>Ruangan</span>
                    </a>

                    <a href="{{ route('fakultas.laboratories.index') }}" class="sidebar-link {{ ($active ?? '') === 'laboratories' ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10 2v7.31"></path>
                            <path d="M14 9.3V1.99"></path>
                            <path d="M8.5 2h7"></path>
                            <path d="M14 9.3a6.5 6.5 0 1 1-4 0"></path>
                        </svg>
                        <span>Laboratorium</span>
                    </a>

                    <a href="{{ route('fakultas.allocations.rooms') }}" class="sidebar-link {{ ($active ?? '') === 'allocations_rooms' ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="16 3 21 3 21 8"></polyline>
                            <line x1="4" y1="20" x2="21" y2="3"></line>
                            <polyline points="21 16 21 21 16 21"></polyline>
                            <line x1="15" y1="15" x2="21" y2="21"></line>
                            <line x1="4" y1="4" x2="9" y2="9"></line>
                        </svg>
                        <span>Alokasi Ruangan</span>
                    </a>

                    <a href="{{ route('fakultas.allocations.laboratories') }}" class="sidebar-link {{ ($active ?? '') === 'allocations_laboratories' ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="18" cy="5" r="3"></circle>
                            <circle cx="6" cy="12" r="3"></circle>
                            <circle cx="18" cy="19" r="3"></circle>
                            <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                            <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                        </svg>
                        <span>Alokasi Laboratorium</span>
                    </a>
                </div>
            </div>

            {{-- Penjadwalan Kuliah Fakultas --}}
            <a href="{{ route('fakultas.penjadwalan') }}" class="sidebar-link {{ ($active ?? '') === 'penjadwalan' ? 'active' : '' }}" style="margin-top: 6px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
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
    function toggleSidebarGroup(groupId, btnEl) {
        const groupEl = document.getElementById(groupId);
        if (!groupEl) return;
        const isOpen = groupEl.classList.toggle('show');
        if (btnEl) {
            btnEl.classList.toggle('open', isOpen);
        }
    }

    function openProfileModalSafe() {
        const modal = document.getElementById('modalEditProfile');
        if (!modal) return;
        if (typeof openModal === 'function') {
            openModal('modalEditProfile');
        } else {
            modal.classList.add('show');
            modal.style.display = 'flex';
        }
    }

    function closeProfileModalSafe() {
        const modal = document.getElementById('modalEditProfile');
        if (!modal) return;
        if (typeof closeModal === 'function') {
            closeModal('modalEditProfile');
        } else {
            modal.classList.remove('show');
            modal.style.display = 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (!document.getElementById('modalEditProfile')) {
            const modalWrapper = document.createElement('div');
            modalWrapper.innerHTML = `
                <div id="modalEditProfile" class="modal-backdrop">
                    <div class="modal-window">
                        <div class="modal-header-blue">
                            <h3>Edit Profile</h3>
                        </div>
                        <div class="modal-body">
                            <form action="{{ route('profile.update') }}" method="POST" class="modal-form">
                                @csrf
                                <div class="modal-form-row">
                                    <label>Nama Lengkap</label>
                                    <div class="modal-input-container">
                                        <input type="text" name="nama" value="{{ Auth::user()->nama ?? '' }}" required>
                                    </div>
                                </div>
                                <div class="modal-form-row">
                                    <label>Email</label>
                                    <div class="modal-input-container">
                                        <input type="email" value="{{ Auth::user()->email ?? '' }}" disabled style="background:#f8fafc; color:#64748b;">
                                    </div>
                                </div>
                                <div class="modal-form-row">
                                    <label>Password Lama</label>
                                    <div class="modal-input-container">
                                        <input type="password" name="password_lama" placeholder="Kosongkan jika tidak diubah">
                                    </div>
                                </div>
                                <div class="modal-form-row">
                                    <label>Password Baru</label>
                                    <div class="modal-input-container">
                                        <input type="password" name="password_baru" placeholder="Kosongkan jika tidak diubah">
                                    </div>
                                </div>
                                <div class="modal-form-row">
                                    <label>Konfirmasi</label>
                                    <div class="modal-input-container">
                                        <input type="password" name="password_baru_confirmation" placeholder="Ulangi password baru">
                                    </div>
                                </div>
                                <div class="modal-footer-buttons" style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                                    <button type="button" class="btn-modal-cancel" onclick="closeProfileModalSafe()">Batal</button>
                                    <button type="submit" class="btn-modal-submit" style="background:#0860be; color:#fff; border:none; padding:8px 18px; border-radius:6px; font-weight:600; cursor:pointer;">Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modalWrapper.firstElementChild);
        }

        const badge = document.querySelector('.admin-header .user-badge');
        if (!badge || badge.dataset.dropdownInitialized === '1') return;
        badge.dataset.dropdownInitialized = '1';

        const caret = document.createElement('span');
        caret.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="display:block; color:#64748b;"><polyline points="6 9 12 15 18 9"></polyline></svg>`;
        badge.appendChild(caret);

        const dropdown = document.createElement('div');
        dropdown.className = 'user-profile-dropdown';
        dropdown.id = 'topUserProfileDropdown';
        dropdown.innerHTML = `
            <button type="button" class="user-profile-dropdown-item" id="btnTopEditProfile">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Edit Profile</span>
            </button>
            <div class="user-profile-dropdown-divider"></div>
            <button type="button" class="user-profile-dropdown-item logout-item" id="btnTopLogout">
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
            dropdown.classList.toggle('show');
        });

        const btnEdit = dropdown.querySelector('#btnTopEditProfile');
        if (btnEdit) {
            btnEdit.addEventListener('click', function (e) {
                e.stopPropagation();
                dropdown.classList.remove('show');
                openProfileModalSafe();
            });
        }

        const btnLogout = dropdown.querySelector('#btnTopLogout');
        if (btnLogout) {
            btnLogout.addEventListener('click', function (e) {
                e.stopPropagation();
                dropdown.classList.remove('show');
                const form = document.getElementById('logoutForm');
                if (form) form.submit();
            });
        }

        document.addEventListener('click', function () {
            dropdown.classList.remove('show');
        });
    });
</script>
