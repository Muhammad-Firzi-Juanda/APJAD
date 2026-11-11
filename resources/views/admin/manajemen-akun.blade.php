@extends('layouts.app')

@section('title', 'APJAD - ' . ($pageTitle ?? 'Manajemen Akun'))

@section('content')
@php
    $showFakultas = in_array($pageRole, ['Fakultas', 'Jurusan', 'Prodi'], true);
    $showJurusan = in_array($pageRole, ['Jurusan', 'Prodi'], true);
    $showProdi = $pageRole === 'Prodi';
    $colCount = 5 + ($showFakultas ? 1 : 0) + ($showJurusan ? 1 : 0) + ($showProdi ? 1 : 0);
    $defaultJabatan = match ($pageRole) {
        'Jurusan' => 'Ketua Jurusan',
        'Prodi' => 'Ketua Program Studi',
        default => 'Dekan',
    };
@endphp
<div class="admin-layout">
    @include('admin.sidebar', ['active' => $activeMenu ?? 'manajemen-akun-fakultas'])

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

            @if ($errors->any())
                <div class="flash-alert error">
                    <span>{{ $errors->first() }}</span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;">✕</button>
                </div>
            @endif

            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 14px; margin-bottom: 18px;">
                <div>
                    <h1 class="page-title" style="margin-bottom: 4px;">{{ $pageTitle ?? 'Manajemen Akun Pengguna' }}</h1>
                    <p style="color: #64748b; font-size: 0.95rem; margin: 0;">
                        {{ $pageDescription ?? '' }}
                    </p>
                </div>
                @if($canCreateAccount ?? in_array($pageRole, ['Fakultas', 'Jurusan', 'Prodi'], true))
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
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
                    <button type="button" class="btn-primary btn-add" onclick="openCreateModal()" style="display: inline-flex; align-items: center; gap: 8px; border: none; background: #0860be; color: #ffffff; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; cursor: pointer; box-shadow: 0 2px 6px rgba(8, 96, 190, 0.3);">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Tambah Akun Manual</span>
                    </button>
                </div>
                @endif
            </div>

            <!-- Role Category Navigation Tabs -->
            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px;">
                <a href="{{ route('manajemen-akun.fakultas') }}" style="padding: 8px 16px; border-radius: 8px; font-size: 0.86rem; font-weight: 700; text-decoration: none; {{ $pageRole === 'Fakultas' ? 'background: #0860be; color: #ffffff;' : 'background: #ffffff; color: #475569; border: 1px solid #cbd5e1;' }}">
                    Akun Fakultas
                </a>
                <a href="{{ route('manajemen-akun.jurusan') }}" style="padding: 8px 16px; border-radius: 8px; font-size: 0.86rem; font-weight: 700; text-decoration: none; {{ $pageRole === 'Jurusan' ? 'background: #0860be; color: #ffffff;' : 'background: #ffffff; color: #475569; border: 1px solid #cbd5e1;' }}">
                    Akun Jurusan
                </a>
                <a href="{{ route('manajemen-akun.prodi') }}" style="padding: 8px 16px; border-radius: 8px; font-size: 0.86rem; font-weight: 700; text-decoration: none; {{ $pageRole === 'Prodi' ? 'background: #0860be; color: #ffffff;' : 'background: #ffffff; color: #475569; border: 1px solid #cbd5e1;' }}">
                    Akun Program Studi
                </a>
                @if($isAdmin ?? false)
                    <a href="{{ route('manajemen-akun.super-admin') }}" style="padding: 8px 16px; border-radius: 8px; font-size: 0.86rem; font-weight: 700; text-decoration: none; {{ $pageRole === 'Super Admin' ? 'background: #0860be; color: #ffffff;' : 'background: #ffffff; color: #475569; border: 1px solid #cbd5e1;' }}">
                        Akun Super Admin
                    </a>
                @endif
                <a href="{{ route('manajemen-akun.riwayat-generate') }}" style="margin-left: auto; padding: 8px 14px; border-radius: 8px; font-size: 0.84rem; font-weight: 600; text-decoration: none; background: #f8fafc; color: #0860be; border: 1px solid #bae6fd;">
                    Riwayat Generate Akun
                </a>
            </div>

            <!-- Filter Toolbar -->
            <div style="background: #ffffff; border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                <form action="{{ route($pageRouteName) }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between;">
                    <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
                        @if($showFakultas)
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <label for="filter_fakultas_id" style="font-size: 0.82rem; font-weight: 700; color: #475569; white-space: nowrap;">Fakultas:</label>
                                <select id="filter_fakultas_id" name="fakultas_id" onchange="this.form.submit()" style="padding: 7px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.83rem; background: #ffffff; color: #1e293b; font-weight: 500; cursor: pointer; outline: none;">
                                    <option value="">Semua Fakultas</option>
                                    @foreach($fakultas as $f)
                                        <option value="{{ $f->id }}" {{ (string) ($filterFakultasId ?? '') === (string) $f->id ? 'selected' : '' }}>{{ $f->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if($showJurusan)
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <label for="filter_jurusan_id" style="font-size: 0.82rem; font-weight: 700; color: #475569; white-space: nowrap;">Jurusan:</label>
                                <select id="filter_jurusan_id" name="jurusan_id" onchange="this.form.submit()" style="padding: 7px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.83rem; background: #ffffff; color: #1e293b; font-weight: 500; cursor: pointer; outline: none;">
                                    <option value="">Semua Jurusan</option>
                                    @foreach($jurusan as $j)
                                        @if(empty($filterFakultasId) || (int) $j->fakultas_id === (int) $filterFakultasId)
                                            <option value="{{ $j->id }}" {{ (string) ($filterJurusanId ?? '') === (string) $j->id ? 'selected' : '' }}>{{ $j->nama }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if($showProdi)
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <label for="filter_prodi_id" style="font-size: 0.82rem; font-weight: 700; color: #475569; white-space: nowrap;">Prodi:</label>
                                <select id="filter_prodi_id" name="prodi_id" onchange="this.form.submit()" style="padding: 7px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.83rem; background: #ffffff; color: #1e293b; font-weight: 500; cursor: pointer; outline: none;">
                                    <option value="">Semua Prodi</option>
                                    @foreach($prodi as $p)
                                        @if((empty($filterFakultasId) || (int) $p->fakultas_id === (int) $filterFakultasId) && (empty($filterJurusanId) || (int) $p->jurusan_id === (int) $filterJurusanId))
                                            <option value="{{ $p->id }}" {{ (string) ($filterProdiId ?? '') === (string) $p->id ? 'selected' : '' }}>{{ $p->nama }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div style="display: flex; align-items: center; gap: 6px;">
                            <label for="filter_status" style="font-size: 0.82rem; font-weight: 700; color: #475569; white-space: nowrap;">Status:</label>
                            <select id="filter_status" name="status" onchange="this.form.submit()" style="padding: 7px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.83rem; background: #ffffff; color: #1e293b; font-weight: 500; cursor: pointer; outline: none;">
                                <option value="">Semua Status</option>
                                <option value="aktif" {{ $filterStatus === 'aktif' ? 'selected' : '' }}>Aktif</option>
                                <option value="nonaktif" {{ $filterStatus === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: flex; gap: 8px; align-items: center;">
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari nama / email / NUPTK..." style="padding: 7px 12px 7px 32px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; width: 220px; outline: none;">
                            <svg style="position: absolute; left: 10px; width: 15px; height: 15px; color: #94a3b8;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                        </div>
                        <button type="submit" style="padding: 7px 14px; background: #0860be; color: #fff; border: none; border-radius: 6px; font-size: 0.82rem; font-weight: 600; cursor: pointer;">
                            Cari
                        </button>
                        @if(!empty($filterStatus) || !empty($filterFakultasId) || !empty($filterJurusanId) || !empty($filterProdiId) || !empty($search))
                            <a href="{{ route($pageRouteName) }}" style="padding: 6px 12px; background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.82rem; font-weight: 600; text-decoration: none;">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- User Management Table -->
            <div class="table-card" style="background: #ffffff; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); padding: 20px;">
                <div class="table-responsive">
                    <table class="user-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid #e2e8f0; text-align: left;">
                                <th style="padding: 12px 10px; color: #64748b; font-size: 0.82rem; text-transform: uppercase; width: 45px;">No.</th>
                                <th style="padding: 12px 12px; color: #64748b; font-size: 0.82rem; text-transform: uppercase;">Pengguna & Jabatan</th>
                                <th style="padding: 12px 12px; color: #64748b; font-size: 0.82rem; text-transform: uppercase;">Kontak & Identitas</th>
                                @if($showFakultas)
                                    <th style="padding: 12px 12px; color: #64748b; font-size: 0.82rem; text-transform: uppercase;">Fakultas</th>
                                @endif
                                @if($showJurusan)
                                    <th style="padding: 12px 12px; color: #64748b; font-size: 0.82rem; text-transform: uppercase;">Jurusan</th>
                                @endif
                                @if($showProdi)
                                    <th style="padding: 12px 12px; color: #64748b; font-size: 0.82rem; text-transform: uppercase;">Program Studi</th>
                                @endif
                                <th style="padding: 12px 12px; color: #64748b; font-size: 0.82rem; text-transform: uppercase;">Status</th>
                                <th style="padding: 12px 12px; color: #64748b; font-size: 0.82rem; text-transform: uppercase; text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $index => $u)
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 12px 10px; color: #64748b; font-size: 0.85rem;">
                                        {{ ($users->firstItem() ?? 1) + $index }}
                                    </td>
                                    <td style="padding: 12px;">
                                        <div style="font-weight: 600; color: #0f172a;">{{ $u->nama }}</div>
                                        @if(!empty($u->jabatan))
                                            <span style="display: inline-block; margin-top: 3px; padding: 2px 8px; border-radius: 999px; background: #e0f2fe; color: #0369a1; font-size: 0.74rem; font-weight: 700;">
                                                {{ $u->jabatan }}
                                            </span>
                                        @endif
                                    </td>
                                    <td style="padding: 12px; font-size: 0.85rem; color: #475569;">
                                        <div>{{ $u->email }}</div>
                                        @if(!empty($u->nuptk) || !empty($u->no_hp))
                                            <div style="font-size: 0.75rem; color: #64748b; margin-top: 2px;">
                                                @if(!empty($u->nuptk)) NUPTK: {{ $u->nuptk }} @endif
                                                @if(!empty($u->nuptk) && !empty($u->no_hp)) • @endif
                                                @if(!empty($u->no_hp)) HP: {{ $u->no_hp }} @endif
                                            </div>
                                        @endif
                                    </td>

                                    @if($showFakultas)
                                        <td style="padding: 12px; font-size: 0.85rem; color: #334155;">
                                            @if($u->fakultas)
                                                <span style="font-weight: 600;">{{ $u->fakultas->nama }}</span>
                                                <div style="font-size: 0.75rem; color: #64748b;">Kode: {{ $u->fakultas->kode }}</div>
                                            @else
                                                <span style="color: #94a3b8;">-</span>
                                            @endif
                                        </td>
                                    @endif

                                    @if($showJurusan)
                                        <td style="padding: 12px; font-size: 0.85rem; color: #334155;">
                                            @if($u->jurusan)
                                                <span style="font-weight: 600;">{{ $u->jurusan->nama }}</span>
                                                <div style="font-size: 0.75rem; color: #64748b;">Kode: {{ $u->jurusan->kode }}</div>
                                            @else
                                                <span style="color: #94a3b8;">-</span>
                                            @endif
                                        </td>
                                    @endif

                                    @if($showProdi)
                                        <td style="padding: 12px; font-size: 0.85rem; color: #334155;">
                                            @if($u->prodi)
                                                <span style="font-weight: 600;">{{ $u->prodi->nama }}</span>
                                                <div style="font-size: 0.75rem; color: #64748b;">Kode: {{ $u->prodi->kode }}</div>
                                            @else
                                                <span style="color: #94a3b8;">-</span>
                                            @endif
                                        </td>
                                    @endif

                                    <td style="padding: 12px;">
                                        @php
                                            $canManageThisUser = ($canManageAccounts ?? false)
                                                && Auth::user()->role === \App\Enums\UserRole::ADMIN
                                                && $u->id !== Auth::id()
                                                && in_array($u->role, [\App\Enums\UserRole::FAKULTAS, \App\Enums\UserRole::JURUSAN, \App\Enums\UserRole::PRODI], true);
                                        @endphp

                                        @if($canManageThisUser)
                                            <select onchange="handleStatusDropdownChange(this, {{ $u->id }}, '{{ addslashes($u->nama) }}', '{{ $u->status }}')"
                                                style="padding: 4px 8px; border-radius: 6px; font-size: 0.78rem; font-weight: 700; cursor: pointer; border: 1px solid {{ $u->isActive() ? '#86efac' : '#fca5a5' }}; background: {{ $u->isActive() ? '#dcfce7' : '#fee2e2' }}; color: {{ $u->isActive() ? '#15803d' : '#b91c1c' }}; outline: none;">
                                                <option value="aktif" {{ $u->isActive() ? 'selected' : '' }} style="background:#fff; color:#15803d; font-weight:600;">● Aktif</option>
                                                <option value="nonaktif" {{ ! $u->isActive() ? 'selected' : '' }} style="background:#fff; color:#b91c1c; font-weight:600;">● Nonaktif</option>
                                            </select>
                                        @else
                                            @if($u->isActive())
                                                <span style="display:inline-flex; align-items:center; gap:5px; padding: 4px 10px; border-radius: 6px; font-size: 0.78rem; font-weight: 600; background: #dcfce7; color: #15803d;">
                                                    <span style="width:6px; height:6px; border-radius:50%; background:#22c55e;"></span>
                                                    Aktif
                                                </span>
                                            @else
                                                <span style="display:inline-flex; align-items:center; gap:5px; padding: 4px 10px; border-radius: 6px; font-size: 0.78rem; font-weight: 600; background: #fee2e2; color: #b91c1c;">
                                                    <span style="width:6px; height:6px; border-radius:50%; background:#ef4444;"></span>
                                                    Nonaktif
                                                </span>
                                            @endif
                                        @endif
                                    </td>

                                    <td style="padding: 12px; text-align: right;">
                                        <div style="display: inline-flex; gap: 6px; align-items: center;">
                                            <button type="button" class="btn-action" title="Detail Akun"
                                                style="background: #f1f5f9; color: #334155;"
                                                data-nama="{{ $u->nama }}"
                                                data-email="{{ $u->email }}"
                                                data-nuptk="{{ $u->nuptk ?? '-' }}"
                                                data-no-hp="{{ $u->no_hp ?? '-' }}"
                                                data-jabatan="{{ $u->jabatan ?? '-' }}"
                                                data-role="{{ $u->role }}"
                                                data-status="{{ $u->isActive() ? 'Aktif' : 'Nonaktif' }}"
                                                data-fakultas="{{ $u->fakultas ? $u->fakultas->nama . ' (' . $u->fakultas->kode . ')' : '-' }}"
                                                data-jurusan="{{ $u->jurusan ? $u->jurusan->nama . ' (' . $u->jurusan->kode . ')' : '-' }}"
                                                data-prodi="{{ $u->prodi ? $u->prodi->nama . ' (' . $u->prodi->kode . ')' : '-' }}"
                                                data-created="{{ $u->created_at ? $u->created_at->format('d M Y, H:i') : '-' }}"
                                                onclick="openDetailModal(this)">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                            </button>

                                            @if($canManageThisUser)
                                            <button type="button" class="btn-action edit-btn" title="Edit Akun"
                                                data-id="{{ $u->id }}"
                                                data-nama="{{ $u->nama }}"
                                                data-email="{{ $u->email }}"
                                                data-nuptk="{{ $u->nuptk ?? '' }}"
                                                data-no-hp="{{ $u->no_hp ?? '' }}"
                                                data-jabatan="{{ $u->jabatan ?? '' }}"
                                                data-role="{{ $u->role }}"
                                                data-status="{{ $u->status }}"
                                                data-fakultas-id="{{ $u->fakultas_id ?? '' }}"
                                                data-jurusan-id="{{ $u->jurusan_id ?? '' }}"
                                                data-prodi-id="{{ $u->prodi_id ?? '' }}"
                                                onclick="openEditUserModal(this)">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </button>

                                            <button type="button" class="btn-action" title="Reset Password"
                                                style="background: #fef3c7; color: #b45309;"
                                                data-id="{{ $u->id }}"
                                                data-nama="{{ $u->nama }}"
                                                onclick="openResetPasswordModal(this)">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                                </svg>
                                            </button>

                                            <button type="button" class="btn-action delete-btn" title="Hapus Akun"
                                                onclick="openDeleteUserModal({{ $u->id }}, '{{ addslashes($u->nama) }}')">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $colCount }}" style="padding: 24px; text-align: center; color: #64748b;">
                                        Belum ada data akun {{ $pageRole }} yang sesuai filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(method_exists($users, 'hasPages') && $users->hasPages())
                    <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                        <div style="font-size: 0.83rem; color: #64748b;">
                            Menampilkan {{ $users->firstItem() }}–{{ $users->lastItem() }} dari {{ $users->total() }} akun {{ $pageRole }}
                        </div>
                        <div style="display: flex; gap: 6px; align-items: center;">
                            @if($users->onFirstPage())
                                <span style="padding: 6px 12px; border-radius: 6px; background: #f8fafc; color: #cbd5e1; font-size: 0.82rem; font-weight: 600;">Sebelumnya</span>
                            @else
                                <a href="{{ $users->previousPageUrl() }}" style="padding: 6px 12px; border-radius: 6px; background: #f1f5f9; color: #334155; font-size: 0.82rem; font-weight: 600; text-decoration: none;">Sebelumnya</a>
                            @endif

                            @if($users->hasMorePages())
                                <a href="{{ $users->nextPageUrl() }}" style="padding: 6px 12px; border-radius: 6px; background: #0860be; color: #ffffff; font-size: 0.82rem; font-weight: 600; text-decoration: none;">Selanjutnya</a>
                            @else
                                <span style="padding: 6px 12px; border-radius: 6px; background: #f8fafc; color: #cbd5e1; font-size: 0.82rem; font-weight: 600;">Selanjutnya</span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </main>
</div>

@if($canCreateAccount ?? in_array($pageRole, ['Fakultas', 'Jurusan', 'Prodi'], true))
<!-- MODAL 1: TAMBAH AKUN MANUAL BERDASARKAN JABATAN RESMI (Fix.md Bagian 3) -->
<div class="modal-backdrop" id="modalTambah">
    <div class="modal-window" style="max-width: 560px;">
        <div class="modal-header-blue">
            <h3>Tambah Akun Manual</h3>
        </div>
        <div class="modal-body">
            <form action="{{ route('users.store') }}" method="POST" class="modal-form">
                @csrf

                <div class="modal-form-row">
                    <label for="create_nama">Nama Lengkap</label>
                    <div class="modal-input-container">
                        <input type="text" id="create_nama" name="nama" required placeholder="Contoh: Martaleli Ibet">
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="create_email">Email</label>
                    <div class="modal-input-container">
                        <input type="email" id="create_email" name="email" required placeholder="Contoh: martaleli@fttk.ac.id">
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="create_nuptk">NUPTK <small style="color:#64748b; font-weight:400;">(Opsional)</small></label>
                    <div class="modal-input-container">
                        <input type="text" id="create_nuptk" name="nuptk" placeholder="Nomor Unik Pendidik dan Tenaga Kependidikan">
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="create_no_hp">Nomor HP <small style="color:#64748b; font-weight:400;">(Opsional)</small></label>
                    <div class="modal-input-container">
                        <input type="text" id="create_no_hp" name="no_hp" placeholder="Contoh: 082177642122">
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="create_jabatan">Jabatan Resmi</label>
                    <div class="modal-input-container">
                        <select id="create_jabatan" name="jabatan" required onchange="handleJabatanChange('create')">
                            <option value="Dekan" {{ $defaultJabatan === 'Dekan' ? 'selected' : '' }}>Dekan</option>
                            <option value="Wakil Dekan" {{ $defaultJabatan === 'Wakil Dekan' ? 'selected' : '' }}>Wakil Dekan</option>
                            <option value="Ketua Jurusan" {{ $defaultJabatan === 'Ketua Jurusan' ? 'selected' : '' }}>Ketua Jurusan</option>
                            <option value="Ketua Program Studi" {{ $defaultJabatan === 'Ketua Program Studi' ? 'selected' : '' }}>Ketua Program Studi</option>
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="create_role_display">Role Otomatis</label>
                    <div class="modal-input-container">
                        <input type="text" id="create_role_display" value="{{ $pageRole }}" readonly style="background: #f1f5f9; color: #0860be; font-weight: 700; cursor: not-allowed;">
                        <input type="hidden" id="create_role" name="role" value="{{ $pageRole }}">
                    </div>
                </div>

                <div class="modal-form-row" id="create_group_fakultas">
                    <label for="create_fakultas_id">Fakultas</label>
                    <div class="modal-input-container">
                        <select id="create_fakultas_id" name="fakultas_id" required onchange="filterJurusanDropdown('create')">
                            <option value="">-- Pilih Fakultas --</option>
                            @foreach ($fakultas as $f)
                                <option value="{{ $f->id }}">{{ $f->nama }} ({{ $f->kode }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-form-row" id="create_group_jurusan" style="{{ in_array($pageRole, ['Jurusan', 'Prodi'], true) ? '' : 'display: none;' }}">
                    <label for="create_jurusan_id">Jurusan</label>
                    <div class="modal-input-container">
                        <select id="create_jurusan_id" name="jurusan_id" {{ in_array($pageRole, ['Jurusan', 'Prodi'], true) ? 'required' : 'disabled' }} onchange="handleJurusanSelectChange('create')">
                            <option value="">-- Pilih Jurusan --</option>
                            @foreach ($jurusan as $j)
                                <option value="{{ $j->id }}" data-fakultas-id="{{ $j->fakultas_id }}">{{ $j->nama }} ({{ $j->kode }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-form-row" id="create_group_prodi" style="{{ $pageRole === 'Prodi' ? '' : 'display: none;' }}">
                    <label for="create_prodi_id">Program Studi</label>
                    <div class="modal-input-container">
                        <select id="create_prodi_id" name="prodi_id" {{ $pageRole === 'Prodi' ? 'required' : 'disabled' }} onchange="handleProdiSelectChange('create')">
                            <option value="">-- Pilih Program Studi --</option>
                            @foreach ($prodi as $p)
                                <option value="{{ $p->id }}" data-jurusan-id="{{ $p->jurusan_id }}" data-fakultas-id="{{ $p->fakultas_id }}">{{ $p->nama }} ({{ $p->kode }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="create_password">Password</label>
                    <div class="modal-input-container">
                        <input type="password" id="create_password" name="password" required placeholder="Minimal 6 karakter">
                        <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibilityModal('create_password', this)">
                            <svg id="create_password_eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="create_password_confirmation">Konfirmasi Password</label>
                    <div class="modal-input-container">
                        <input type="password" id="create_password_confirmation" name="password_confirmation" required placeholder="Ulangi password">
                        <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibilityModal('create_password_confirmation', this)">
                            <svg id="create_password_confirmation_eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="modal-footer-buttons">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalTambah')">Batal</button>
                    <button type="submit" class="btn-modal-submit">Simpan Akun</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@if($canManageAccounts ?? false)
<!-- MODAL 2: EDIT DATA AKUN (EKSKLUSIF ADMIN GLOBAL) -->
<div class="modal-backdrop" id="modalEdit">
    <div class="modal-window" style="max-width: 560px;">
        <div class="modal-header-blue">
            <h3>Edit Data Akun Operasional</h3>
        </div>
        <div class="modal-body">
            <form id="formEditUser" action="{{ route('users.update', ['user' => 'USER_ID']) }}" data-base-url="{{ url('/users') }}" method="POST" class="modal-form">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_user_id" name="user_id">

                <div class="modal-form-row">
                    <label for="edit_nama">Nama Lengkap</label>
                    <div class="modal-input-container">
                        <input type="text" id="edit_nama" name="nama" required>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="edit_email">Email</label>
                    <div class="modal-input-container">
                        <input type="email" id="edit_email" name="email" required>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="edit_nuptk">NUPTK <small style="color:#64748b; font-weight:400;">(Opsional)</small></label>
                    <div class="modal-input-container">
                        <input type="text" id="edit_nuptk" name="nuptk">
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="edit_no_hp">Nomor HP <small style="color:#64748b; font-weight:400;">(Opsional)</small></label>
                    <div class="modal-input-container">
                        <input type="text" id="edit_no_hp" name="no_hp">
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="edit_jabatan">Jabatan Resmi</label>
                    <div class="modal-input-container">
                        <select id="edit_jabatan" name="jabatan" onchange="handleJabatanChange('edit')">
                            <option value="">-- Sesuai Role --</option>
                            <option value="Dekan">Dekan</option>
                            <option value="Wakil Dekan">Wakil Dekan</option>
                            <option value="Ketua Jurusan">Ketua Jurusan</option>
                            <option value="Ketua Program Studi">Ketua Program Studi</option>
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="edit_role">Role Akun</label>
                    <div class="modal-input-container">
                        <select id="edit_role" name="role" required onchange="handleRoleScopeVisibility('edit')">
                            @foreach (['Fakultas', 'Jurusan', 'Prodi'] as $roleOption)
                                <option value="{{ $roleOption }}" {{ $pageRole === $roleOption ? 'selected' : '' }}>{{ $roleOption }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="edit_status">Status Akun</label>
                    <div class="modal-input-container">
                        <select id="edit_status" name="status" required>
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="modal-form-row" id="edit_group_fakultas">
                    <label for="edit_fakultas_id">Fakultas</label>
                    <div class="modal-input-container">
                        <select id="edit_fakultas_id" name="fakultas_id" required onchange="filterJurusanDropdown('edit')">
                            <option value="">-- Pilih Fakultas --</option>
                            @foreach ($fakultas as $f)
                                <option value="{{ $f->id }}">{{ $f->nama }} ({{ $f->kode }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-form-row" id="edit_group_jurusan" style="{{ in_array($pageRole, ['Jurusan', 'Prodi'], true) ? '' : 'display: none;' }}">
                    <label for="edit_jurusan_id">Jurusan</label>
                    <div class="modal-input-container">
                        <select id="edit_jurusan_id" name="jurusan_id" {{ in_array($pageRole, ['Jurusan', 'Prodi'], true) ? 'required' : 'disabled' }} onchange="handleJurusanSelectChange('edit')">
                            <option value="">-- Pilih Jurusan --</option>
                            @foreach ($jurusan as $j)
                                <option value="{{ $j->id }}" data-fakultas-id="{{ $j->fakultas_id }}">{{ $j->nama }} ({{ $j->kode }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-form-row" id="edit_group_prodi" style="{{ $pageRole === 'Prodi' ? '' : 'display: none;' }}">
                    <label for="edit_prodi_id">Program Studi</label>
                    <div class="modal-input-container">
                        <select id="edit_prodi_id" name="prodi_id" {{ $pageRole === 'Prodi' ? 'required' : 'disabled' }} onchange="handleProdiSelectChange('edit')">
                            <option value="">-- Pilih Program Studi --</option>
                            @foreach ($prodi as $p)
                                <option value="{{ $p->id }}" data-jurusan-id="{{ $p->jurusan_id }}" data-fakultas-id="{{ $p->fakultas_id }}">{{ $p->nama }} ({{ $p->kode }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="edit_password">Password Baru</label>
                    <div class="modal-input-container">
                        <input type="password" id="edit_password" name="password" placeholder="Kosongkan jika tidak diubah">
                        <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibilityModal('edit_password', this)">
                            <svg id="edit_password_eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="edit_password_confirmation">Konfirmasi Password</label>
                    <div class="modal-input-container">
                        <input type="password" id="edit_password_confirmation" name="password_confirmation" placeholder="Kosongkan jika tidak diubah">
                        <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibilityModal('edit_password_confirmation', this)">
                            <svg id="edit_password_confirmation_eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="modal-footer-buttons">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalEdit')">Batal</button>
                    <button type="submit" class="btn-modal-submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- MODAL 3: EDIT PROFILE -->
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

<!-- MODAL 4: DETAIL AKUN -->
<div class="modal-backdrop" id="modalDetail">
    <div class="modal-window" style="max-width: 500px;">
        <div class="modal-header-blue">
            <h3>Detail Informasi Akun {{ $pageRole }}</h3>
        </div>
        <div class="modal-body">
            <div style="display: grid; gap: 12px; font-size: 0.9rem;">
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                    <span style="color: #64748b; font-weight: 600;">Nama Lengkap</span>
                    <span id="detail_nama" style="color: #0f172a; font-weight: 700;">-</span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                    <span style="color: #64748b; font-weight: 600;">Email</span>
                    <span id="detail_email" style="color: #334155;">-</span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                    <span style="color: #64748b; font-weight: 600;">NUPTK</span>
                    <span id="detail_nuptk" style="color: #334155;">-</span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                    <span style="color: #64748b; font-weight: 600;">Nomor HP</span>
                    <span id="detail_no_hp" style="color: #334155;">-</span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                    <span style="color: #64748b; font-weight: 600;">Jabatan</span>
                    <span id="detail_jabatan" style="color: #0369a1; font-weight: 700;">-</span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                    <span style="color: #64748b; font-weight: 600;">Role Sistem</span>
                    <span id="detail_role" style="color: #0860be; font-weight: 700;">-</span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                    <span style="color: #64748b; font-weight: 600;">Status Akun</span>
                    <span id="detail_status" style="font-weight: 700;">-</span>
                </div>
                @if($showFakultas)
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                    <span style="color: #64748b; font-weight: 600;">Fakultas</span>
                    <span id="detail_fakultas" style="color: #334155; text-align: right;">-</span>
                </div>
                @endif
                @if($showJurusan)
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                    <span style="color: #64748b; font-weight: 600;">Jurusan</span>
                    <span id="detail_jurusan" style="color: #334155; text-align: right;">-</span>
                </div>
                @endif
                @if($showProdi)
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                    <span style="color: #64748b; font-weight: 600;">Program Studi</span>
                    <span id="detail_prodi" style="color: #334155; text-align: right;">-</span>
                </div>
                @endif
                <div style="display: flex; justify-content: space-between; padding-bottom: 4px;">
                    <span style="color: #64748b; font-weight: 600;">Terdaftar Pada</span>
                    <span id="detail_created" style="color: #64748b;">-</span>
                </div>
            </div>
            <div class="modal-footer-buttons" style="margin-top: 20px;">
                <button type="button" class="btn-modal-cancel" onclick="closeModal('modalDetail')">Tutup</button>
            </div>
        </div>
    </div>
</div>

@if($canManageAccounts ?? false)
<!-- MODAL 5: RESET PASSWORD AKUN -->
<div class="modal-backdrop" id="modalResetPassword">
    <div class="modal-window" style="max-width: 500px;">
        <div class="modal-header-blue">
            <h3>Reset Password Akun</h3>
        </div>
        <div class="modal-body">
            <p style="font-size: 0.88rem; color: #475569; margin-top: 0; margin-bottom: 16px;">
                Reset password untuk akun <strong id="reset_target_nama" style="color: #0f172a;">-</strong>.
            </p>
            <form id="formResetPassword" action="" data-base-url="{{ url('/users') }}" method="POST" class="modal-form">
                @csrf

                <div class="modal-form-row">
                    <label for="reset_new_password">Password Baru</label>
                    <div class="modal-input-container">
                        <input type="password" id="reset_new_password" name="new_password" required placeholder="Minimal 6 karakter">
                        <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibilityModal('reset_new_password', this)">
                            <svg id="reset_new_password_eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="reset_new_password_confirmation">Konfirmasi Password</label>
                    <div class="modal-input-container">
                        <input type="password" id="reset_new_password_confirmation" name="new_password_confirmation" required placeholder="Ulangi password baru">
                        <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibilityModal('reset_new_password_confirmation', this)">
                            <svg id="reset_new_password_confirmation_eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="modal-footer-buttons">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalResetPassword')">Batal</button>
                    <button type="submit" class="btn-modal-submit">Simpan Password Baru</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 6: KONFIRMASI STATUS AKUN -->
<div class="modal-backdrop" id="modalKonfirmasiStatus">
    <div class="modal-confirm-card" style="max-width: 420px; text-align: center;">
        <div style="width: 48px; height: 48px; border-radius: 50%; background: rgba(8, 96, 190, 0.1); color: #0860be; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
        </div>
        <h4 class="confirm-question" style="margin-bottom: 8px; font-size: 1.1rem; color: #0f172a;">Konfirmasi Status Akun</h4>
        <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 20px; line-height: 1.5;" id="confirm_status_text">
            Yakin ingin mengubah status akun ini?
        </p>
        <form id="formConfirmStatus" action="" method="POST">
            @csrf
            <div class="confirm-actions" style="display: flex; gap: 10px; justify-content: center;">
                <button type="button" class="btn-confirm-cancel" onclick="cancelChangeStatus()" style="padding: 8px 18px; border-radius: 6px; border: 1px solid #cbd5e1; background: #f8fafc; color: #475569; font-weight: 600; cursor: pointer;">Batal</button>
                <button type="submit" id="btnConfirmStatusSubmit" class="btn-confirm-yes" style="padding: 8px 18px; border-radius: 6px; border: none; background: #0860be; color: #ffffff; font-weight: 600; cursor: pointer;">Ya, Ubah Status</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 7: KONFIRMASI HAPUS -->
<div class="modal-backdrop" id="modalHapus">
    <div class="modal-confirm-card">
        <h4 class="confirm-question" id="delete_target_text">Yakin ingin menghapus user ini?</h4>
        <form id="formDeleteUser" action="{{ route('users.destroy', ['user' => 'USER_ID']) }}" data-base-url="{{ url('/users') }}" method="POST">
            @csrf
            @method('DELETE')
            <input type="hidden" id="delete_user_id" name="user_id">

            <div class="confirm-actions">
                <button type="submit" class="btn-confirm-yes">Ya, Hapus</button>
                <button type="button" class="btn-confirm-cancel" onclick="closeModal('modalHapus')">Batal</button>
            </div>
        </form>
    </div>
</div>
@endif

<script>
const JABATAN_ROLE_MAP = {
    'Dekan': 'Fakultas',
    'Wakil Dekan': 'Fakultas',
    'Ketua Jurusan': 'Jurusan',
    'Ketua Program Studi': 'Prodi'
};

function openModal(modalId) {
    const el = document.getElementById(modalId);
    if (el) el.classList.add('show');
}

function closeModal(modalId) {
    const el = document.getElementById(modalId);
    if (el) el.classList.remove('show');
}

function handleJabatanChange(prefix) {
    const jabatanEl = document.getElementById(prefix + '_jabatan');
    const roleEl = document.getElementById(prefix + '_role');
    const roleDisplayEl = document.getElementById(prefix + '_role_display');

    if (jabatanEl && jabatanEl.value && JABATAN_ROLE_MAP[jabatanEl.value]) {
        const mappedRole = JABATAN_ROLE_MAP[jabatanEl.value];
        if (roleEl) roleEl.value = mappedRole;
        if (roleDisplayEl) roleDisplayEl.value = mappedRole;
    }

    handleRoleScopeVisibility(prefix);
}

function handleRoleScopeVisibility(prefix) {
    const roleEl = document.getElementById(prefix + '_role');
    if (!roleEl) return;

    const role = roleEl.value;
    const fakGroup = document.getElementById(prefix + '_group_fakultas');
    const jurGroup = document.getElementById(prefix + '_group_jurusan');
    const prodiGroup = document.getElementById(prefix + '_group_prodi');

    const fakSelect = document.getElementById(prefix + '_fakultas_id');
    const jurSelect = document.getElementById(prefix + '_jurusan_id');
    const prodiSelect = document.getElementById(prefix + '_prodi_id');

    const needFakultas = ['Fakultas', 'Jurusan', 'Prodi'].indexOf(role) !== -1;
    const needJurusan = ['Jurusan', 'Prodi'].indexOf(role) !== -1;
    const needProdi = role === 'Prodi';

    if (fakGroup && fakSelect) {
        fakGroup.style.display = needFakultas ? '' : 'none';
        fakSelect.required = role === 'Fakultas';
        fakSelect.disabled = !needFakultas;
        if (!needFakultas) fakSelect.value = '';
    }

    if (jurGroup && jurSelect) {
        jurGroup.style.display = needJurusan ? '' : 'none';
        jurSelect.required = needJurusan;
        jurSelect.disabled = !needJurusan;
        if (!needJurusan) jurSelect.value = '';
    }

    if (prodiGroup && prodiSelect) {
        prodiGroup.style.display = needProdi ? '' : 'none';
        prodiSelect.required = needProdi;
        prodiSelect.disabled = !needProdi;
        if (!needProdi) prodiSelect.value = '';
    }

    filterJurusanDropdown(prefix);
}

function handleJurusanSelectChange(prefix) {
    const jurSelect = document.getElementById(prefix + '_jurusan_id');
    const fakSelect = document.getElementById(prefix + '_fakultas_id');
    if (jurSelect && jurSelect.value && fakSelect) {
        const selectedOpt = jurSelect.querySelector('option[value="' + jurSelect.value + '"]');
        if (selectedOpt && selectedOpt.getAttribute('data-fakultas-id')) {
            fakSelect.value = selectedOpt.getAttribute('data-fakultas-id');
        }
    }
    filterProdiDropdown(prefix);
}

function handleProdiSelectChange(prefix) {
    const prodiSelect = document.getElementById(prefix + '_prodi_id');
    const jurSelect = document.getElementById(prefix + '_jurusan_id');
    const fakSelect = document.getElementById(prefix + '_fakultas_id');
    if (prodiSelect && prodiSelect.value) {
        const selectedOpt = prodiSelect.querySelector('option[value="' + prodiSelect.value + '"]');
        if (selectedOpt) {
            if (fakSelect && selectedOpt.getAttribute('data-fakultas-id')) {
                fakSelect.value = selectedOpt.getAttribute('data-fakultas-id');
            }
            if (jurSelect && selectedOpt.getAttribute('data-jurusan-id')) {
                jurSelect.value = selectedOpt.getAttribute('data-jurusan-id');
            }
        }
    }
}

function openCreateModal() {
    const createJabatan = document.getElementById('create_jabatan');
    if (createJabatan) {
        createJabatan.value = '{{ $defaultJabatan }}';
    }
    handleJabatanChange('create');
    openModal('modalTambah');
}

function filterJurusanDropdown(prefix) {
    const fakultasEl = document.getElementById(prefix + '_fakultas_id');
    const jurusanSelect = document.getElementById(prefix + '_jurusan_id');
    if (!fakultasEl || !jurusanSelect) {
        return;
    }

    const fakultasId = fakultasEl.value;
    const options = jurusanSelect.querySelectorAll('option');

    options.forEach(opt => {
        if (!opt.value) {
            opt.style.display = 'block';
            return;
        }
        if (!fakultasId || opt.getAttribute('data-fakultas-id') === fakultasId) {
            opt.style.display = 'block';
        } else {
            opt.style.display = 'none';
        }
    });

    if (jurusanSelect.value && fakultasId) {
        const selectedOpt = jurusanSelect.querySelector('option[value="' + jurusanSelect.value + '"]');
        if (selectedOpt && selectedOpt.getAttribute('data-fakultas-id') !== fakultasId) {
            jurusanSelect.value = '';
        }
    }

    filterProdiDropdown(prefix);
}

function filterProdiDropdown(prefix) {
    const jurusanEl = document.getElementById(prefix + '_jurusan_id');
    const fakultasEl = document.getElementById(prefix + '_fakultas_id');
    const prodiSelect = document.getElementById(prefix + '_prodi_id');
    if (!prodiSelect) {
        return;
    }

    const jurusanId = jurusanEl ? jurusanEl.value : '';
    const fakultasId = fakultasEl ? fakultasEl.value : '';
    const options = prodiSelect.querySelectorAll('option');

    options.forEach(opt => {
        if (!opt.value) {
            opt.style.display = 'block';
            return;
        }
        let match = true;
        if (jurusanId && opt.getAttribute('data-jurusan-id') !== jurusanId) {
            match = false;
        }
        if (fakultasId && opt.getAttribute('data-fakultas-id') !== fakultasId) {
            match = false;
        }
        opt.style.display = match ? 'block' : 'none';
    });

    if (prodiSelect.value) {
        const selectedOpt = prodiSelect.querySelector('option[value="' + prodiSelect.value + '"]');
        if (selectedOpt && selectedOpt.style.display === 'none') {
            prodiSelect.value = '';
        }
    }
}

function openDetailModal(btn) {
    document.getElementById('detail_nama').innerText = btn.getAttribute('data-nama') || '-';
    document.getElementById('detail_email').innerText = btn.getAttribute('data-email') || '-';
    document.getElementById('detail_nuptk').innerText = btn.getAttribute('data-nuptk') || '-';
    document.getElementById('detail_no_hp').innerText = btn.getAttribute('data-no-hp') || '-';
    document.getElementById('detail_jabatan').innerText = btn.getAttribute('data-jabatan') || '-';
    document.getElementById('detail_role').innerText = btn.getAttribute('data-role') || '-';
    document.getElementById('detail_status').innerText = btn.getAttribute('data-status') || '-';

    const fakEl = document.getElementById('detail_fakultas');
    if (fakEl) fakEl.innerText = btn.getAttribute('data-fakultas') || '-';

    const jurEl = document.getElementById('detail_jurusan');
    if (jurEl) jurEl.innerText = btn.getAttribute('data-jurusan') || '-';

    const prodiEl = document.getElementById('detail_prodi');
    if (prodiEl) prodiEl.innerText = btn.getAttribute('data-prodi') || '-';

    document.getElementById('detail_created').innerText = btn.getAttribute('data-created') || '-';
    openModal('modalDetail');
}

@if($canManageAccounts ?? false)
function openEditUserModal(btn) {
    const userId = btn.getAttribute ? btn.getAttribute('data-id') : (btn.id || '');
    const form = document.getElementById('formEditUser');
    if (!form) return;
    const baseUrl = form.getAttribute('data-base-url').replace(/\/$/, '');
    form.action = baseUrl + '/' + userId;

    document.getElementById('edit_user_id').value = userId;
    document.getElementById('edit_nama').value = (btn.getAttribute ? btn.getAttribute('data-nama') : btn.nama) || '';
    document.getElementById('edit_email').value = (btn.getAttribute ? btn.getAttribute('data-email') : btn.email) || '';
    document.getElementById('edit_nuptk').value = (btn.getAttribute ? btn.getAttribute('data-nuptk') : btn.nuptk) || '';
    document.getElementById('edit_no_hp').value = (btn.getAttribute ? btn.getAttribute('data-no-hp') : btn.no_hp) || '';
    document.getElementById('edit_jabatan').value = (btn.getAttribute ? btn.getAttribute('data-jabatan') : btn.jabatan) || '';
    document.getElementById('edit_role').value = (btn.getAttribute ? btn.getAttribute('data-role') : btn.role) || '{{ $pageRole }}';
    document.getElementById('edit_status').value = (btn.getAttribute ? btn.getAttribute('data-status') : btn.status) || 'aktif';

    document.getElementById('edit_password').value = '';
    document.getElementById('edit_password_confirmation').value = '';

    handleRoleScopeVisibility('edit');

    const fakSelect = document.getElementById('edit_fakultas_id');
    if (fakSelect) {
        fakSelect.value = (btn.getAttribute ? btn.getAttribute('data-fakultas-id') : btn.fakultas_id) || '';
        filterJurusanDropdown('edit');
    }

    const jurSelect = document.getElementById('edit_jurusan_id');
    if (jurSelect) {
        jurSelect.value = (btn.getAttribute ? btn.getAttribute('data-jurusan-id') : btn.jurusan_id) || '';
        filterProdiDropdown('edit');
    }

    const prodiSelect = document.getElementById('edit_prodi_id');
    if (prodiSelect) {
        prodiSelect.value = (btn.getAttribute ? btn.getAttribute('data-prodi-id') : btn.prodi_id) || '';
    }

    openModal('modalEdit');
}
window.openEditModal = openEditUserModal;

function openResetPasswordModal(btn) {
    const userId = btn.getAttribute('data-id');
    const userName = btn.getAttribute('data-nama');
    const form = document.getElementById('formResetPassword');
    const baseUrl = form.getAttribute('data-base-url');
    form.action = baseUrl + '/' + userId + '/reset-password';

    document.getElementById('reset_target_nama').innerText = userName;
    document.getElementById('reset_new_password').value = '';
    document.getElementById('reset_new_password_confirmation').value = '';
    openModal('modalResetPassword');
}

let currentStatusSelect = null;
let currentOldStatus = null;

function handleStatusDropdownChange(selectElement, userId, userName, oldStatus) {
    const newStatus = selectElement.value;
    if (newStatus === oldStatus) return;

    currentStatusSelect = selectElement;
    currentOldStatus = oldStatus;

    const form = document.getElementById('formConfirmStatus');
    form.action = '{{ url("/users") }}/' + userId + '/toggle-status';

    const statusBadge = newStatus === 'aktif'
        ? '<strong style="color:#16a34a;">Aktif</strong>'
        : '<strong style="color:#dc2626;">Nonaktif</strong>';

    document.getElementById('confirm_status_text').innerHTML =
        'Yakin ingin mengubah status akun <strong>"' + userName + '"</strong> menjadi ' + statusBadge + '?';

    const submitBtn = document.getElementById('btnConfirmStatusSubmit');
    if (newStatus === 'aktif') {
        submitBtn.style.background = '#16a34a';
        submitBtn.innerText = 'Ya, Aktifkan';
    } else {
        submitBtn.style.background = '#dc2626';
        submitBtn.innerText = 'Ya, Nonaktifkan';
    }

    openModal('modalKonfirmasiStatus');
}

function cancelChangeStatus() {
    if (currentStatusSelect && currentOldStatus) {
        currentStatusSelect.value = currentOldStatus;
    }
    closeModal('modalKonfirmasiStatus');
}

function openDeleteUserModal(userId, userName) {
    const form = document.getElementById('formDeleteUser');
    const baseUrl = form.getAttribute('data-base-url').replace(/\/$/, '');
    form.action = baseUrl + '/' + userId;
    document.getElementById('delete_user_id').value = userId;
    document.getElementById('delete_target_text').innerText = 'Yakin ingin menghapus akun "' + (userName || '') + '"?';
    openModal('modalHapus');
}
window.openDeleteModal = openDeleteUserModal;

document.addEventListener('DOMContentLoaded', function () {
    const editForm = document.getElementById('formEditUser');
    if (editForm) {
        editForm.addEventListener('submit', function () {
            const uid = document.getElementById('edit_user_id').value;
            const baseUrl = editForm.getAttribute('data-base-url').replace(/\/$/, '');
            if (uid && editForm.action.indexOf('USER_ID') !== -1) {
                editForm.action = baseUrl + '/' + uid;
            }
        });
    }
});
@endif

@if($autoOpenManualModal ?? false)
document.addEventListener('DOMContentLoaded', function () {
    openCreateModal();
});
@endif

function togglePasswordVisibilityModal(fieldId, button) {
    const passwordInput = document.getElementById(fieldId);
    const eyeIcon = document.getElementById(fieldId + '_eye');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
    } else {
        passwordInput.type = 'password';
        eyeIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
    }
}
</script>
@endsection
