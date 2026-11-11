@extends('layouts.app')

@section('title', 'APJAD - Penjadwalan Kuliah Fakultas')

@section('content')
<div class="admin-layout">
    @include('fakultas.sidebar', ['active' => 'penjadwalan'])

    <main class="admin-main">
        <header class="admin-header">
            <div class="user-badge">
                <span>{{ Auth::user()->role }}, <span class="user-name">{{ Auth::user()->nama }}</span> ({{ $faculty->kode ?? 'FTTK' }})</span>
            </div>
        </header>

        <section class="admin-content">
            {{-- Flash Messages --}}
            @if(session('flash_success'))
                <div style="background:#dcfce7; border:1px solid #86efac; color:#15803d; padding:12px 16px; border-radius:8px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
                    <span>{{ session('flash_success') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:#15803d;font-weight:700;">✕</button>
                </div>
            @endif

            @if(session('flash_error'))
                <div style="background:#fee2e2; border:1px solid #fca5a5; color:#b91c1c; padding:12px 16px; border-radius:8px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
                    <span>{{ session('flash_error') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:#b91c1c;font-weight:700;">✕</button>
                </div>
            @endif

            @if($errors->any())
                <div style="background:#fee2e2; border:1px solid #fca5a5; color:#b91c1c; padding:12px 16px; border-radius:8px; margin-bottom:20px;">
                    <ul style="margin:0; padding-left:20px;">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Header Toolbar --}}
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
                <div>
                    <h1 class="page-title" style="margin-bottom:4px;">Penjadwalan Kuliah Fakultas</h1>
                    <p style="color:#64748b; font-size:14px; margin:0;">
                        Generate jadwal kuliah otomatis bebas bentrok untuk MK Universitas (MKWU) dan MK Fakultas (MKWF).
                    </p>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                    {{-- Dropdown Unduh Template --}}
                    <div style="position:relative; display:inline-block;">
                        <button type="button" onclick="toggleDropdown('dropdownTemplateMenu')" style="display:inline-flex; align-items:center; gap:6px; background:#fff; color:#0369a1; border:1px solid #bae6fd; padding:9px 14px; border-radius:6px; font-weight:600; cursor:pointer; font-size:13px;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            <span>Unduh Template ▾</span>
                        </button>
                        <div id="dropdownTemplateMenu" style="display:none; position:absolute; right:0; top:calc(100% + 4px); background:#fff; border:1px solid #e2e8f0; border-radius:8px; box-shadow:0 8px 20px rgba(0,0,0,0.1); min-width:190px; z-index:100; padding:6px 0;">
                            <a href="{{ route('fakultas.courses.template', ['category' => 'UNV']) }}" style="display:flex; align-items:center; gap:8px; padding:8px 16px; font-size:13px; color:#1e293b; text-decoration:none; font-weight:500;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                                <span>Template MKWU (.xlsx)</span>
                            </a>
                            <a href="{{ route('fakultas.courses.template', ['category' => 'FST']) }}" style="display:flex; align-items:center; gap:8px; padding:8px 16px; font-size:13px; color:#1e293b; text-decoration:none; font-weight:500;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                                <span>Template MKWF (.xlsx)</span>
                            </a>
                        </div>
                    </div>

                    {{-- Tombol Import Excel --}}
                    <button type="button" onclick="openImportModal('UNV')" style="display:inline-flex; align-items:center; gap:6px; background:#16a34a; color:#fff; border:none; padding:9px 16px; border-radius:6px; font-weight:600; cursor:pointer; font-size:13px; box-shadow:0 2px 6px rgba(22,163,74,0.25);">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        <span>Import Spreadsheet</span>
                    </button>

                    @if($totalJadwal > 0)
                        <form action="{{ route('fakultas.penjadwalan.destroy-all') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus SEMUA jadwal MKWU & MKWF Fakultas yang telah digenerate?');" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" style="display:inline-flex; align-items:center; gap:6px; background:#fee2e2; color:#dc2626; border:1px solid #fecaca; padding:9px 14px; border-radius:6px; font-weight:600; cursor:pointer; font-size:13px;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                <span>Reset Jadwal</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- 4 KPI Cards --}}
            <div class="stats-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap:16px; margin-bottom:24px;">
                <div class="stat-card">
                    <div class="stat-icon users-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Total Jadwal Terplot</div>
                        <div class="stat-value">{{ $totalJadwal }}</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon aktif-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Total SKS Terjadwal</div>
                        <div class="stat-value">{{ $totalSks }}</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon mkwu-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Jadwal MKWU (Univ)</div>
                        <div class="stat-value">{{ $totalMkwu }} / {{ $mkwuCount }} MK</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon mkwf-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Jadwal MKWF (Fakultas)</div>
                        <div class="stat-value">{{ $totalMkwf }} / {{ $mkwfCount }} MK</div>
                    </div>
                </div>
            </div>

            {{-- Generator Section Card --}}
            <div class="card" style="background:#fff; border:1px solid #cbd5e1; border-radius:12px; padding:22px; margin-bottom:28px; box-shadow:0 2px 8px rgba(0,0,0,0.04);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; border-bottom:1px solid #f1f5f9; padding-bottom:12px;">
                    <div>
                        <h3 style="margin:0 0 4px; font-size:16px; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:8px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                            Generator Jadwal Otomatis Bebas Bentrok
                        </h3>
                        <p style="margin:0; font-size:13px; color:#64748b;">
                            Engine penjadwalan cerdas mengoptimalkan ruangan Fakultas, menghindari tabrakan jam mengajar dosen, dan mendistribusikan kelas secara merata.
                        </p>
                    </div>
                    <span style="background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; font-size:12px; font-weight:600; padding:4px 10px; border-radius:20px;">
                        Ruangan Siap: {{ $rooms->count() }}
                    </span>
                </div>

                <form action="{{ route('fakultas.penjadwalan.generate') }}" method="POST">
                    @csrf
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:18px; margin-bottom:20px;">
                        {{-- Periode Semester --}}
                        <div>
                            <label style="display:block; font-size:13px; font-weight:700; color:#334155; margin-bottom:6px;">
                                Periode Semester <span style="color:#ef4444;">*</span>
                            </label>
                            <select name="semester_mode" required style="width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; background:#fff;">
                                <option value="all">Semua Semester (1 s/d 8)</option>
                                <option value="ganjil" selected>Semester Ganjil (1, 3, 5, 7)</option>
                                <option value="genap">Semester Genap (2, 4, 6, 8)</option>
                                <option value="1">Semester 1</option>
                                <option value="2">Semester 2</option>
                                <option value="3">Semester 3</option>
                                <option value="4">Semester 4</option>
                                <option value="5">Semester 5</option>
                                <option value="6">Semester 6</option>
                                <option value="7">Semester 7</option>
                                <option value="8">Semester 8</option>
                            </select>
                        </div>

                        {{-- Target Mata Kuliah --}}
                        <div>
                            <label style="display:block; font-size:13px; font-weight:700; color:#334155; margin-bottom:6px;">
                                Target Mata Kuliah <span style="color:#ef4444;">*</span>
                            </label>
                            <select name="target_mk" required style="width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; background:#fff;">
                                <option value="all" selected>Semua (MKWU Universitas & MKWF Fakultas)</option>
                                <option value="UNV">Khusus MKWU (Mata Kuliah Wajib Universitas)</option>
                                <option value="FST">Khusus MKWF (Mata Kuliah Wajib Fakultas)</option>
                            </select>
                        </div>
                    </div>

                    {{-- Pilihan Hari Tersedia --}}
                    <div style="margin-bottom:18px;">
                        <label style="display:block; font-size:13px; font-weight:700; color:#334155; margin-bottom:8px;">
                            Pilihan Hari Kuliah:
                        </label>
                        <div style="display:flex; flex-wrap:wrap; gap:16px;">
                            @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $hari)
                                <label style="display:inline-flex; align-items:center; gap:8px; font-size:13px; font-weight:500; color:#1e293b; cursor:pointer;">
                                    <input type="checkbox" name="hari[]" value="{{ $hari }}" checked style="width:16px; height:16px; accent-color:#2563eb;">
                                    {{ $hari }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Pilihan Jam Mulai --}}
                    <div style="margin-bottom:24px;">
                        <label style="display:block; font-size:13px; font-weight:700; color:#334155; margin-bottom:8px;">
                            Slot Waktu Kuliah (Jam Mulai):
                        </label>
                        <div style="display:flex; flex-wrap:wrap; gap:14px;">
                            @foreach(['07:30', '10:00', '13:00', '14:50', '15:30'] as $jam)
                                <label style="display:inline-flex; align-items:center; gap:8px; font-size:13px; background:#f8fafc; border:1px solid #e2e8f0; padding:8px 14px; border-radius:6px; cursor:pointer;">
                                    <input type="checkbox" name="jam_mulai[]" value="{{ $jam }}" checked style="width:16px; height:16px; accent-color:#2563eb;">
                                    <span><strong>{{ $jam }}</strong></span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <button type="submit" style="background:#2563eb; color:#fff; border:none; padding:12px 24px; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:8px; box-shadow:0 2px 6px rgba(37,99,235,0.3);">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                        <span>Mulai Generate Jadwal Sekarang</span>
                    </button>
                </form>
            </div>

            {{-- Filter Bar Tabel Jadwal --}}
            <div class="card" style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:16px; margin-bottom:20px;">
                <form action="{{ route('fakultas.penjadwalan') }}" method="GET" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
                    <select name="hari" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                        <option value="">Semua Hari</option>
                        @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $h)
                            <option value="{{ $h }}" {{ request('hari') === $h ? 'selected' : '' }}>{{ $h }}</option>
                        @endforeach
                    </select>

                    <select name="ruangan_id" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                        <option value="">Semua Ruangan</option>
                        @foreach($rooms as $r)
                            <option value="{{ $r->id }}" {{ request('ruangan_id') == $r->id ? 'selected' : '' }}>{{ $r->name ?? $r->nama }}</option>
                        @endforeach
                    </select>

                    <select name="kategori" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                        <option value="">Semua Kategori MK</option>
                        <option value="UNV" {{ request('kategori') === 'UNV' ? 'selected' : '' }}>MKWU (Universitas)</option>
                        <option value="FST" {{ request('kategori') === 'FST' ? 'selected' : '' }}>MKWF (Fakultas)</option>
                    </select>

                    <button type="submit" style="padding:8px 16px; background:#0f172a; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer;">Filter</button>
                    <a href="{{ route('fakultas.penjadwalan') }}" style="padding:8px 14px; background:#f1f5f9; color:#475569; text-decoration:none; border-radius:6px; font-size:13px; font-weight:500;">Reset</a>
                </form>
            </div>

            {{-- Tabel Hasil Jadwal Terplot (Format Pak Nov) --}}
            <div class="card" style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden;">
                <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="margin:0; font-size:15px; font-weight:700; color:#0f172a;">
                        Tabel Plotting Jadwal Kuliah Fakultas & Universitas
                    </h3>
                    <span style="font-size:12px; color:#64748b;">
                        Menampilkan <strong>{{ $jadwals->count() }}</strong> plot jadwal terverifikasi
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="data-table" style="width:100%; border-collapse:collapse; font-size:13px;">
                        <thead>
                            <tr style="background:#f8fafc; text-align:left; border-bottom:1px solid #e2e8f0;">
                                <th style="padding:12px 14px; width:45px; text-align:center;">No</th>
                                <th style="padding:12px 14px; width:130px;">Hari</th>
                                <th style="padding:12px 14px; width:120px;">Waktu</th>
                                <th style="padding:12px 14px; width:100px;">Kode MK</th>
                                <th style="padding:12px 16px;">Nama Mata Kuliah</th>
                                <th style="padding:12px 10px; width:50px; text-align:center;">SKS</th>
                                <th style="padding:12px 10px; width:55px; text-align:center;">Kelas</th>
                                <th style="padding:12px 16px;">Dosen Pengampu</th>
                                <th style="padding:12px 16px;">Ruangan</th>
                                <th style="padding:12px 14px; width:130px;">Kategori</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($jadwals as $idx => $j)
                                @php
                                    $kodeMk = $j->rombel->mataKuliah->kode_mk ?? '-';
                                    $namaMk = $j->rombel->mataKuliah->nama_mk ?? '-';
                                    $sks = $j->rombel->mataKuliah->sks ?? '-';
                                    $kelas = $j->rombel->kelas ?? $j->rombel->nama ?? 'A';
                                    $dosen = $j->dosen->nama ?? ($j->rombel->dosenPengampu->nama ?? 'Tim Dosen');
                                    $ruangan = $j->ruangan->nama ?? ($j->ruangan->name ?? '-');
                                    $isUniv = str_starts_with($kodeMk, 'UNV') || ($j->rombel->mataKuliah->kategori ?? '') === 'MK Universitas';
                                @endphp
                                <tr style="border-bottom:1px solid #f1f5f9;">
                                    <td style="padding:12px 14px; text-align:center; color:#64748b;">{{ $idx + 1 }}</td>
                                    <td style="padding:12px 14px; font-weight:700; color:#1e293b;">
                                        <span style="display:inline-block; padding:3px 8px; border-radius:4px; font-size:12px; background:#f1f5f9; color:#334155;">
                                            {{ $j->hari }}
                                        </span>
                                    </td>
                                    <td style="padding:12px 14px; font-weight:600; color:#0369a1;">
                                        {{ substr($j->jam_mulai, 0, 5) }} - {{ substr($j->jam_selesai, 0, 5) }}
                                    </td>
                                    <td style="padding:12px 14px; font-weight:700; color:#1e293b;">
                                        {{ $kodeMk }}
                                    </td>
                                    <td style="padding:12px 16px; font-weight:500;">
                                        {{ $namaMk }}
                                    </td>
                                    <td style="padding:12px 10px; text-align:center; font-weight:600;">
                                        {{ $sks }}
                                    </td>
                                    <td style="padding:12px 10px; text-align:center;">
                                        <span style="display:inline-block; width:24px; height:24px; line-height:24px; border-radius:50%; background:#e0f2fe; color:#0369a1; font-weight:700; font-size:12px;">
                                            {{ $kelas }}
                                        </span>
                                    </td>
                                    <td style="padding:12px 16px; color:#334155;">
                                        {{ $dosen }}
                                    </td>
                                    <td style="padding:12px 16px; font-weight:600; color:#0f172a;">
                                        {{ $ruangan }}
                                    </td>
                                    <td style="padding:12px 14px;">
                                        <span style="display:inline-block; padding:3px 8px; border-radius:4px; font-size:11px; font-weight:700; background:{{ $isUniv ? '#e0f2fe; color:#0369a1' : '#f3e8ff; color:#7e22ce' }};">
                                            {{ $isUniv ? 'MKWU (Univ)' : 'MKWF (Fakultas)' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" style="padding:36px 20px; text-align:center;">
                                        <div style="width:48px; height:48px; border-radius:50%; background:#f1f5f9; display:inline-flex; align-items:center; justify-content:center; color:#94a3b8; margin-bottom:12px;">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                        </div>
                                        <h4 style="margin:0 0 6px; font-size:15px; color:#1e293b;">Belum ada jadwal Fakultas yang digenerate</h4>
                                        <p style="margin:0; font-size:13px; color:#64748b;">
                                            Silakan tentukan pengaturan di atas lalu klik tombol <strong>Mulai Generate Jadwal Sekarang</strong>.
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</div>

{{-- MODAL SMART IMPORT EXCEL (DENGAN CRUD & PREVIEW LANGSUNG) --}}
<div class="modal-backdrop" id="modalImportMk">
    <div class="modal-window" id="modalImportMkWindow" style="max-width: 650px; transition: max-width 0.3s ease;">
        <div class="modal-header-blue">
            <h3 id="modalImportTitle">Smart Import MK Fakultas (Excel / Spreadsheet)</h3>
        </div>
        <div class="modal-body">
            <form action="{{ route('fakultas.courses.import') }}" method="POST" enctype="multipart/form-data" class="modal-form" id="formImportFakultas">
                @csrf
                <input type="hidden" name="category_code" id="importCategoryCode" value="UNV">
                <input type="hidden" name="verified_items" id="verifiedItemsInput">
                <input type="hidden" name="redirect_to" value="penjadwalan">

                <div class="modal-form-row">
                    <label for="importTargetSelect">Jenis Mata Kuliah</label>
                    <div class="modal-input-container">
                        <select id="importTargetSelect" onchange="onCategoryChange(this.value)" style="width:100%; padding:9px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                            <option value="UNV">MKWU (Mata Kuliah Wajib Universitas)</option>
                            <option value="FST">MKWF (Mata Kuliah Wajib Fakultas)</option>
                        </select>
                    </div>
                </div>

                <div class="modal-form-row" style="align-items: flex-start;">
                    <label for="importFile" style="padding-top:8px;">File Spreadsheet</label>
                    <div class="modal-input-container" style="flex-direction:column; align-items:flex-start; gap:6px; width:100%;">
                        <input type="file" id="importFile" name="file" accept=".xlsx,.xls,.csv,.ods" style="width:100%;">
                        <div style="display:flex; justify-content:space-between; align-items:center; width:100%; flex-wrap:wrap; gap:8px;">
                            <span style="font-size:12px; color:#64748b;">Mendukung: Excel (.xlsx, .xls), CSV (.csv), dan ODS (.ods).</span>
                            <a id="btnDownloadTemplateInModal" href="{{ route('fakultas.courses.template', ['category' => 'UNV']) }}" style="color:#0284c7; font-weight:600; font-size:12px; text-decoration:underline; display:inline-flex; align-items:center; gap:4px;" download>
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                <span>Unduh Template MKWU (.xlsx)</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div id="importPreviewStatus" style="display:none; padding:12px 14px; background:#f0f9ff; border:1px solid #bae6fd; border-radius:8px; font-size:13px; margin-top:12px;"></div>

                {{-- Interactive Smart Preview CRUD Table --}}
                <div id="importPreviewContainer" style="display:none; margin-top:16px;">
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap:10px; margin-bottom:14px;">
                        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px; text-align:center;">
                            <span style="font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase;">Total Baris</span>
                            <div id="badgeTotal" style="font-size:18px; font-weight:700; color:#1e293b; margin-top:2px;">0</div>
                        </div>
                        <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:10px; text-align:center;">
                            <span style="font-size:11px; color:#166534; font-weight:600; text-transform:uppercase;">Dikenali</span>
                            <div id="badgeDikenali" style="font-size:18px; font-weight:700; color:#15803d; margin-top:2px;">0</div>
                        </div>
                        <div style="background:#fefce8; border:1px solid #fef08a; border-radius:8px; padding:10px; text-align:center;">
                            <span style="font-size:11px; color:#854d0e; font-weight:600; text-transform:uppercase;">Duplikat</span>
                            <div id="badgeDuplikat" style="font-size:18px; font-weight:700; color:#a16207; margin-top:2px;">0</div>
                        </div>
                        <div style="background:#fff7ed; border:1px solid #fed7aa; border-radius:8px; padding:10px; text-align:center;">
                            <span style="font-size:11px; color:#9a3412; font-weight:600; text-transform:uppercase;">Perlu Verifikasi</span>
                            <div id="badgePerluVerifikasi" style="font-size:18px; font-weight:700; color:#c2410c; margin-top:2px;">0</div>
                        </div>
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; flex-wrap:wrap; gap:8px;">
                        <span style="font-size:12.5px; color:#475569; font-weight:600;">
                            Pratinjau Hasil Pembacaan (Bisa diedit/dikoreksi langsung sebelum disimpan):
                        </span>
                        <button type="button" onclick="tambahBarisManualPreview()" style="background:#f8fafc; color:#0369a1; border:1px solid #bae6fd; padding:5px 12px; border-radius:6px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:5px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            Tambah Baris
                        </button>
                    </div>

                    <div style="max-height: 300px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 12px;">
                        <table class="data-table" style="font-size: 12px; margin: 0; width:100%;">
                            <thead>
                                <tr style="background:#f8fafc; position:sticky; top:0; z-index:1;">
                                    <th style="padding:8px; width:95px;">Kode MK</th>
                                    <th style="padding:8px;">Nama Mata Kuliah</th>
                                    <th style="padding:8px; width:45px; text-align:center;">SKS</th>
                                    <th style="padding:8px; width:45px; text-align:center;">Sem</th>
                                    <th style="padding:8px; width:80px; text-align:center;">Jenis</th>
                                    <th style="padding:8px; width:80px; text-align:center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="previewTableBody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer-buttons" style="margin-top:20px;">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalImportMk')">Batal</button>
                    <button type="submit" class="btn-modal-submit" id="btnSubmitImport">Import Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleDropdown(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.style.display = (el.style.display === 'block') ? 'none' : 'block';
}

document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('dropdownTemplateMenu');
    if (dropdown && !e.target.closest('#dropdownTemplateMenu') && !e.target.closest('button[onclick*="dropdownTemplateMenu"]')) {
        dropdown.style.display = 'none';
    }
});

function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('show');
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.remove('show');
        el.style.display = '';
    }
}

function openImportModal(category) {
    const select = document.getElementById('importTargetSelect');
    if (select) {
        select.value = category || 'UNV';
        onCategoryChange(select.value);
    }
    openModal('modalImportMk');
}

function onCategoryChange(cat) {
    const codeInput = document.getElementById('importCategoryCode');
    const title = document.getElementById('modalImportTitle');
    const tplLink = document.getElementById('btnDownloadTemplateInModal');

    if (codeInput) codeInput.value = cat;
    if (title) {
        title.textContent = cat === 'UNV' ? 'Smart Import MKWU Universitas (Excel / Spreadsheet)' : 'Smart Import MKWF Fakultas (Excel / Spreadsheet)';
    }
    if (tplLink) {
        tplLink.href = '{{ route("fakultas.courses.template") }}?category=' + cat;
        tplLink.innerHTML = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg><span>Unduh Template ${cat === 'UNV' ? 'MKWU' : 'MKWF'} (.xlsx)</span>`;
    }
}

// SMART PREVIEW & INLINE CRUD
window.previewItemsList = [];

function updatePreviewSummary() {
    const badgeTotal = document.getElementById('badgeTotal');
    const badgeDikenali = document.getElementById('badgeDikenali');
    const badgeDuplikat = document.getElementById('badgeDuplikat');
    const badgePerluVerifikasi = document.getElementById('badgePerluVerifikasi');
    const verifiedInput = document.getElementById('verifiedItemsInput');

    const total = window.previewItemsList.length;
    let dikenali = 0;
    let duplikat = 0;
    let perluVerif = 0;

    const seenCodes = new Set();
    window.previewItemsList.forEach(item => {
        const code = (item.kode_mk || '').trim().toUpperCase();
        if (code && seenCodes.has(code)) {
            duplikat++;
        } else if (code) {
            seenCodes.add(code);
        }

        if (!code || !item.nama_mk || !item.sks || !item.semester) {
            perluVerif++;
        } else {
            dikenali++;
        }
    });

    if (badgeTotal) badgeTotal.textContent = total;
    if (badgeDikenali) badgeDikenali.textContent = dikenali;
    if (badgeDuplikat) badgeDuplikat.textContent = duplikat;
    if (badgePerluVerifikasi) badgePerluVerifikasi.textContent = perluVerif;

    if (verifiedInput) {
        verifiedInput.value = JSON.stringify(window.previewItemsList);
    }
}

function renderInteractivePreview() {
    const tbody = document.getElementById('previewTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (window.previewItemsList.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:16px; color:#94a3b8;">Tidak ada baris data.</td></tr>';
        return;
    }

    window.previewItemsList.forEach((item, index) => {
        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid #f1f5f9';

        if (item._isEditing) {
            tr.innerHTML = `
                <td style="padding:6px;"><input type="text" id="edit_kode_${index}" value="${item.kode_mk || ''}" style="width:100%; padding:4px 6px; border:1px solid #cbd5e1; border-radius:4px; font-size:11px;"></td>
                <td style="padding:6px;"><input type="text" id="edit_nama_${index}" value="${item.nama_mk || ''}" style="width:100%; padding:4px 6px; border:1px solid #cbd5e1; border-radius:4px; font-size:11px;"></td>
                <td style="padding:6px; text-align:center;"><input type="number" id="edit_sks_${index}" value="${item.sks || 2}" min="1" max="6" style="width:40px; padding:4px; border:1px solid #cbd5e1; border-radius:4px; font-size:11px; text-align:center;"></td>
                <td style="padding:6px; text-align:center;"><input type="number" id="edit_sem_${index}" value="${item.semester || 1}" min="1" max="8" style="width:40px; padding:4px; border:1px solid #cbd5e1; border-radius:4px; font-size:11px; text-align:center;"></td>
                <td style="padding:6px; text-align:center;">
                    <select id="edit_type_${index}" style="padding:4px; border:1px solid #cbd5e1; border-radius:4px; font-size:11px;">
                        <option value="THEORY" ${item.type === 'THEORY' || !item.butuh_lab ? 'selected' : ''}>Teori</option>
                        <option value="PRACTICUM" ${item.type === 'PRACTICUM' || item.butuh_lab ? 'selected' : ''}>Praktikum</option>
                    </select>
                </td>
                <td style="padding:6px; text-align:center;">
                    <button type="button" onclick="savePreviewItem(${index})" style="background:#16a34a; color:#fff; border:none; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:700; cursor:pointer;" title="Simpan">✓</button>
                    <button type="button" onclick="cancelPreviewItem(${index})" style="background:#e2e8f0; color:#475569; border:none; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:700; cursor:pointer; margin-left:3px;" title="Batal">✕</button>
                </td>
            `;
        } else {
            const isPraktikum = (item.type === 'PRACTICUM' || item.butuh_lab === true || item.butuh_lab === 'Ya');
            tr.innerHTML = `
                <td style="padding:8px; font-weight:700; color:#1e293b;">${item.kode_mk || '<span style="color:#ef4444;">(Kosong)</span>'}</td>
                <td style="padding:8px;">${item.nama_mk || '<span style="color:#ef4444;">(Kosong)</span>'}</td>
                <td style="padding:8px; text-align:center;">${item.sks || 2}</td>
                <td style="padding:8px; text-align:center;">${item.semester || 1}</td>
                <td style="padding:8px; text-align:center;">
                    <span style="display:inline-block; padding:2px 6px; border-radius:4px; font-size:10px; font-weight:600; background:${isPraktikum ? '#fef3c7; color:#92400e' : '#e0e7ff; color:#3730a3'};">
                        ${isPraktikum ? 'Praktikum' : 'Teori'}
                    </span>
                </td>
                <td style="padding:8px; text-align:center;">
                    <button type="button" onclick="editPreviewItem(${index})" style="background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center;" title="Edit baris">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </button>
                    <button type="button" onclick="deletePreviewItem(${index})" style="background:#fef2f2; color:#dc2626; border:1px solid #fecaca; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:600; cursor:pointer; margin-left:3px; display:inline-flex; align-items:center;" title="Hapus baris">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                </td>
            `;
        }

        tbody.appendChild(tr);
    });
}

function editPreviewItem(index) {
    if (window.previewItemsList[index]) {
        window.previewItemsList[index]._isEditing = true;
        renderInteractivePreview();
    }
}

function cancelPreviewItem(index) {
    if (window.previewItemsList[index]) {
        if (window.previewItemsList[index]._isNew) {
            window.previewItemsList.splice(index, 1);
        } else {
            window.previewItemsList[index]._isEditing = false;
        }
        updatePreviewSummary();
        renderInteractivePreview();
    }
}

function savePreviewItem(index) {
    const item = window.previewItemsList[index];
    if (!item) return;

    const kodeEl = document.getElementById(`edit_kode_${index}`);
    const namaEl = document.getElementById(`edit_nama_${index}`);
    const sksEl = document.getElementById(`edit_sks_${index}`);
    const semEl = document.getElementById(`edit_sem_${index}`);
    const typeEl = document.getElementById(`edit_type_${index}`);

    if (kodeEl) item.kode_mk = kodeEl.value.trim().toUpperCase();
    if (namaEl) item.nama_mk = namaEl.value.trim();
    if (sksEl) item.sks = parseInt(sksEl.value) || 2;
    if (semEl) item.semester = parseInt(semEl.value) || 1;
    if (typeEl) {
        item.type = typeEl.value;
        item.butuh_lab = typeEl.value === 'PRACTICUM';
    }

    item._isEditing = false;
    delete item._isNew;

    updatePreviewSummary();
    renderInteractivePreview();
}

function deletePreviewItem(index) {
    window.previewItemsList.splice(index, 1);
    updatePreviewSummary();
    renderInteractivePreview();
}

function tambahBarisManualPreview() {
    const currentCat = document.getElementById('importCategoryCode')?.value || 'UNV';
    window.previewItemsList.unshift({
        kode_mk: currentCat === 'UNV' ? 'UNV' : 'FST',
        nama_mk: '',
        sks: 2,
        semester: 1,
        type: 'THEORY',
        butuh_lab: false,
        _isEditing: true,
        _isNew: true
    });
    updatePreviewSummary();
    renderInteractivePreview();
}

// Event handler saat file Excel dipilih
document.addEventListener('DOMContentLoaded', function () {
    const importFileInput = document.getElementById('importFile');
    const modalWindow = document.getElementById('modalImportMkWindow');
    const previewContainer = document.getElementById('importPreviewContainer');
    const previewStatus = document.getElementById('importPreviewStatus');

    if (importFileInput) {
        importFileInput.addEventListener('change', function () {
            if (!this.files || this.files.length === 0) return;

            const file = this.files[0];
            const currentCat = document.getElementById('importCategoryCode')?.value || 'UNV';

            const formData = new FormData();
            formData.append('file', file);
            formData.append('category_code', currentCat);
            formData.append('_token', '{{ csrf_token() }}');

            if (previewStatus) {
                previewStatus.style.display = 'block';
                previewStatus.innerHTML = '<div style="display:flex; align-items:center; gap:8px; color:#0369a1;"><strong>Menganalisis file Excel...</strong> Membaca kolom dan memverifikasi data.</div>';
            }
            if (previewContainer) previewContainer.style.display = 'none';

            fetch('{{ route('fakultas.courses.preview-import') }}', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) {
                    const data = res.data;
                    if (modalWindow) modalWindow.style.maxWidth = '850px';
                    if (previewStatus) previewStatus.style.display = 'none';
                    if (previewContainer) previewContainer.style.display = 'block';

                    window.previewItemsList = (data.items || []).map(item => ({ ...item, _isEditing: false }));
                    updatePreviewSummary();
                    renderInteractivePreview();
                } else {
                    if (previewStatus) {
                        previewStatus.innerHTML = '<div style="color:#b91c1c;">Gagal membaca pratinjau: ' + (res.message || 'Format tidak sesuai') + '. Anda tetap bisa klik Import Sekarang.</div>';
                    }
                }
            })
            .catch(err => {
                if (previewStatus) {
                    previewStatus.innerHTML = '<div style="color:#64748b;">Pratinjau otomatis tidak tersedia. Silakan klik Import Sekarang langsung.</div>';
                }
            });
        });
    }
});
</script>
@endsection

