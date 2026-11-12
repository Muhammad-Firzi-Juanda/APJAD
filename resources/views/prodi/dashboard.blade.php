@extends('layouts.app')

@section('title', 'APJAD - Dashboard Prodi')

@section('content')
<div class="admin-layout">
    @include('prodi.sidebar', ['active' => 'dashboard'])

    <main class="admin-main">
        <header class="admin-header">
            <div class="user-badge">
                <span>{{ Auth::user()->role }}, <span class="user-name">{{ Auth::user()->nama }}</span> ({{ Auth::user()->prodi->kode ?? 'PRODI' }})</span>
            </div>
        </header>

        <section class="admin-content">
            @if (session('flash_success'))
                <div class="flash-alert success">
                    <span>{{ session('flash_success') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;">✕</button>
                </div>
            @endif

            {{-- ============ SECTION: DASHBOARD ============ --}}
            <div id="section-dashboard" class="content-section">
                <div style="margin-bottom: 24px;">
                    <h1 class="page-title" style="margin-bottom: 4px;">Dashboard Program Studi</h1>
                    <p style="color: #64748b; font-size: 0.95rem; margin: 0;">
                        Program Studi {{ $prodiName }} — Pusat pengelolaan data dosen, mata kuliah, rombel, dan pemantauan jadwal kuliah.
                    </p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon dosen-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-label">Total Mata Kuliah</p>
                            <p class="stat-value">{{ $stats['total_mata_kuliah'] }}</p>
                        </div>
                    </div>

                    <div class="stat-card">
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
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon aktif-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-label">Jadwal Aktif</p>
                            <p class="stat-value">{{ $stats['total_jadwal'] }}</p>
                        </div>
                    </div>

                    <div class="stat-card" style="cursor:pointer;" onclick="showSection(event, 'section-catatan', document.querySelector('a[href*=\'section-catatan\']'))">
                        <div class="stat-icon" style="background:#fef3c7; color:#d97706;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-label">Catatan Jurusan</p>
                            <p class="stat-value" style="color:{{ ($stats['total_catatan_pending'] ?? 0) > 0 ? '#d97706' : '#10b981' }};">
                                {{ $stats['total_catatan_pending'] ?? 0 }} <span style="font-size:0.75rem; color:#64748b; font-weight:normal;">/ {{ $stats['total_catatan'] ?? 0 }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============ SECTION: DATA DOSEN ============ --}}
            <div id="section-dosen" class="content-section" style="margin-top: 32px; display: none;">
                <div class="tab-header">
                    <h2>Data Dosen - {{ $prodiName }}</h2>
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
                            <th>Telepon</th>
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
                                <td>{{ $d->telepon ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" style="text-align:center; padding:24px;">Belum ada data dosen.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ============ SECTION: MATA KULIAH ============ --}}
            <div id="section-mk" class="content-section" style="margin-top: 40px; display: none;">
                <div class="tab-header" style="flex-wrap:wrap; gap:12px;">
                    <h2>Mata Kuliah - {{ $prodiName }}</h2>
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
                                    <th style="text-align:center;">Kapasitas</th>
                                    <th style="text-align:center;">Lab</th>
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
                                        <td style="text-align:center;">{{ $mk->kapasitas_per_kelas }}</td>
                                        <td style="text-align:center;">
                                            @if($mk->butuh_lab)
                                                <span class="badge-tipe pilihan">Ya</span>
                                            @else
                                                <span class="badge-tipe wajib">Tidak</span>
                                            @endif
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
                    <h2>Data Rombel / Kelas - {{ $prodiName }}</h2>
                </div>
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

            {{-- ============ SECTION: JADWAL ============ --}}
            <div id="section-jadwal" class="content-section" style="margin-top: 32px; display: none;">
                <div class="tab-header">
                    <h2>Jadwal Kuliah - {{ $prodiName }}</h2>
                    <button type="button" class="btn-add" onclick="openModal('modalTambahJadwal')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Tambah Jadwal
                    </button>
                </div>

                {{-- Filter Semester --}}
                <div style="margin-bottom: 16px; display:flex; gap:10px; flex-wrap:wrap;">
                    <button type="button" class="btn-add semester-filter active" data-sem="all"
                        onclick="filterSemester('all', this)" style="padding:6px 16px; font-size:13px;">
                        Semua
                    </button>
                    @foreach ($semesterList as $sem)
                        <button type="button" class="btn-add semester-filter" data-sem="{{ $sem }}"
                            onclick="filterSemester('{{ $sem }}', this)"
                            style="padding:6px 16px; font-size:13px; background:#475569;">
                            Semester {{ $sem }}
                        </button>
                    @endforeach
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
                                        <th>Dosen</th>
                                        <th>Ruangan</th>
                                        <th style="text-align:center;">Kelas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($perHari[$hari] as $j)
                                        <tr class="jadwal-row" data-sem="{{ $j->rombel->mataKuliah->semester ?? '' }}">
                                            <td style="white-space:nowrap; font-weight:600; color:#0860be;">{{ substr($j->jam_mulai, 0, 5) }} &ndash; {{ substr($j->jam_selesai, 0, 5) }}</td>
                                            <td style="white-space:nowrap;"><strong>{{ $j->clean_kode_mk }}</strong></td>
                                            <td style="font-weight:600; color:#0f172a;">{{ $j->rombel->mataKuliah->nama_mk ?? '-' }}</td>
                                            <td style="text-align:center;">{{ $j->rombel->mataKuliah->semester ?? '-' }}</td>
                                            <td style="text-align:center;"><span style="display:inline-block; padding:2px 8px; background:#e2e8f0; border-radius:12px; font-size:0.85rem; font-weight:600;">{{ $j->rombel->mataKuliah->sks ?? '-' }}</span></td>
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
                                            <td style="text-align:center;">
                                                <span style="display:inline-block; padding:2px 8px; background:#dbeafe; color:#1e40af; border-radius:12px; font-weight:700; font-size:0.8rem;">
                                                    {{ $j->kelas_label }}
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

            {{-- ============ SECTION: CATATAN JURUSAN ============ --}}
            <div id="section-catatan" class="content-section" style="margin-top: 32px; display: none;">
                <div class="tab-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <div>
                        <h2>Catatan & Rekomendasi dari Admin Jurusan</h2>
                        <p style="margin:2px 0 0; font-size:0.88rem; color:#64748b;">
                            Daftar catatan evaluasi dan rekomendasi perbaikan jadwal dari Admin Jurusan yang perlu ditindaklanjuti.
                        </p>
                    </div>
                    @if(!empty($catatanPendingCount) && $catatanPendingCount > 0)
                        <span style="display:inline-block; padding:4px 12px; background:#fef3c7; color:#b45309; border-radius:20px; font-weight:700; font-size:0.82rem; border:1px solid #fde68a;">
                            {{ $catatanPendingCount }} Catatan Menunggu Tindak Lanjut
                        </span>
                    @endif
                </div>

                @forelse($catatanList as $c)
                    <div class="table-card" style="margin-bottom:20px; padding:20px; border-radius:10px; border:1px solid #e2e8f0; background:#ffffff;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:8px; margin-bottom:12px; border-bottom:1px solid #f1f5f9; padding-bottom:10px;">
                            <div>
                                <span style="display:inline-block; font-size:0.75rem; font-weight:700; padding:2px 8px; border-radius:4px; background:#f1f5f9; color:#334155; border:1px solid #cbd5e1; margin-bottom:6px;">
                                    {{ $c->kategori }}
                                </span>
                                <h3 style="margin:0; font-size:1.05rem; color:#0f172a; font-weight:700;">{{ $c->judul }}</h3>
                                <p style="margin:2px 0 0; font-size:0.8rem; color:#64748b;">
                                    Dikirim oleh: <strong>{{ $c->pengirim->nama ?? 'Admin Jurusan' }}</strong> pada {{ $c->created_at->format('d/m/Y H:i') }} WIB
                                </p>
                            </div>
                            <div>
                                @php
                                    $stColor = match($c->status) {
                                        'Menunggu tanggapan' => 'background:#fef3c7; color:#b45309; border:1px solid #fde68a;',
                                        'Sedang ditinjau' => 'background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd;',
                                        'Ditanggapi' => 'background:#f3e8ff; color:#7e22ce; border:1px solid #e9d5ff;',
                                        'Selesai' => 'background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;',
                                        default => '',
                                    };
                                @endphp
                                <span style="display:inline-block; font-size:0.8rem; padding:4px 10px; border-radius:12px; font-weight:700; {{ $stColor }}">
                                    {{ $c->status }}
                                </span>
                            </div>
                        </div>

                        {{-- Isi Rekomendasi Jurusan --}}
                        <div style="background:#f8fafc; border-left:4px solid #0860be; padding:12px 14px; border-radius:0 6px 6px 0; margin-bottom:14px;">
                            <strong style="display:block; font-size:0.82rem; color:#0860be; margin-bottom:4px; text-transform:uppercase;">Catatan & Rekomendasi Jurusan:</strong>
                            <p style="margin:0; font-size:0.9rem; color:#1e293b; line-height:1.5;">{{ $c->catatan_rekomendasi }}</p>
                            @if($c->jadwal)
                                <div style="margin-top:8px; font-size:0.8rem; color:#475569;">
                                    <strong>Jadwal Terkait:</strong> {{ $c->jadwal->rombel->mataKuliah->nama_mk ?? '-' }} ({{ $c->jadwal->kelas_label }}) &middot; {{ $c->jadwal->hari }}, {{ substr($c->jadwal->jam_mulai,0,5) }}-{{ substr($c->jadwal->jam_selesai,0,5) }} &middot; Dosen: {{ $c->jadwal->dosen->nama ?? '-' }}
                                </div>
                            @endif
                        </div>

                        {{-- Tanggapan yang Sudah Ada --}}
                        @if($c->tanggapan_prodi)
                            <div style="background:#f0fdf4; border:1px solid #bbf7d0; padding:12px 14px; border-radius:6px; margin-bottom:14px;">
                                <strong style="display:block; font-size:0.82rem; color:#15803d; margin-bottom:4px; text-transform:uppercase;">Tanggapan Admin Prodi:</strong>
                                <p style="margin:0; font-size:0.9rem; color:#14532d; line-height:1.5;">{{ $c->tanggapan_prodi }}</p>
                                <span style="font-size:0.75rem; color:#166534; display:block; margin-top:4px;">
                                    Oleh: {{ $c->penanggap->nama ?? 'Admin Prodi' }} ({{ $c->ditanggapi_pada ? $c->ditanggapi_pada->format('d/m/Y H:i') : '-' }})
                                </span>
                            </div>
                        @endif

                        {{-- Form Tanggapan Admin Prodi --}}
                        <div style="margin-top:14px; background:#ffffff; border:1px dashed #cbd5e1; border-radius:6px; padding:14px;">
                            <h4 style="margin:0 0 10px; font-size:0.88rem; color:#1e293b;">
                                {{ $c->tanggapan_prodi ? 'Perbarui Tanggapan / Status Catatan:' : 'Beri Tanggapan / Tindak Lanjut:' }}
                            </h4>
                            <form action="{{ route('prodi.evaluasi.tanggapi', ['catatan' => $c->id]) }}" method="POST">
                                @csrf
                                <div style="display:grid; grid-template-columns: 1fr 200px; gap:12px; margin-bottom:10px;">
                                    <div>
                                        <textarea name="tanggapan_prodi" rows="2" placeholder="Tuliskan tindak lanjut perbaikan atau penjelasan klarifikasi..." required style="width:100%; border-radius:6px; border:1px solid #cbd5e1; padding:8px 10px; font-family:inherit; font-size:0.88rem;">{{ $c->tanggapan_prodi }}</textarea>
                                    </div>
                                    <div style="display:flex; flex-direction:column; justify-content:space-between;">
                                        <div>
                                            <label style="display:block; font-size:0.75rem; font-weight:700; color:#475569; margin-bottom:4px;">Ubah Status:</label>
                                            <select name="status" style="width:100%; height:34px; border-radius:6px; border:1px solid #cbd5e1; padding:0 8px; font-size:0.82rem;">
                                                <option value="Sedang ditinjau" {{ $c->status === 'Sedang ditinjau' ? 'selected' : '' }}>Sedang ditinjau</option>
                                                <option value="Ditanggapi" {{ $c->status === 'Ditanggapi' ? 'selected' : '' }}>Ditanggapi</option>
                                                <option value="Selesai" {{ $c->status === 'Selesai' ? 'selected' : '' }}>Selesai</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn-primary" style="height:34px; font-size:0.82rem; font-weight:600; padding:0 12px; border-radius:6px;">
                                            Kirim Tanggapan
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="table-card" style="text-align:center; padding:40px 20px; border-style:dashed; border-color:#cbd5e1; background:#f8fafc; border-radius:10px;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin:0 auto 10px;">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <h4 style="margin:0 0 4px; color:#475569;">Belum Ada Catatan Evaluasi dari Jurusan</h4>
                        <p style="margin:0; font-size:0.88rem; color:#64748b;">
                            Jadwal Anda saat ini tidak memiliki catatan atau rekomendasi evaluasi dari Admin Jurusan.
                        </p>
                    </div>
                @endforelse
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

{{-- MODAL: TAMBAH JADWAL --}}
<div class="modal-backdrop" id="modalTambahJadwal">
    <div class="modal-window">
        <div class="modal-header-blue">
            <h3>Tambah Jadwal Kuliah</h3>
        </div>
        <div class="modal-body">
            <form action="{{ route('prodi.jadwal.store') }}" method="POST" class="modal-form">
                @csrf
                <div class="modal-form-row">
                    <label for="jadwal_rombel">Rombel</label>
                    <div class="modal-input-container">
                        <select id="jadwal_rombel" name="rombel_id" required>
                            <option value="">Pilih Rombel</option>
                            @foreach ($rombelProdi as $r)
                                <option value="{{ $r->id }}">{{ $r->kode_rombel }} @if($r->kelas) ({{ $r->kelas }})@endif - {{ $r->mataKuliah->nama_mk ?? '' }} ({{ $r->mataKuliah->kode_mk ?? '' }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="jadwal_hari">Hari</label>
                    <div class="modal-input-container">
                        <select id="jadwal_hari" name="hari" required>
                            <option value="">Pilih Hari</option>
                            <option value="Senin">Senin</option>
                            <option value="Selasa">Selasa</option>
                            <option value="Rabu">Rabu</option>
                            <option value="Kamis">Kamis</option>
                            <option value="Jumat">Jumat</option>
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="jadwal_jam_mulai">Jam Mulai</label>
                    <div class="modal-input-container">
                        <select id="jadwal_jam_mulai" name="jam_mulai" required>
                            <option value="">Pilih Jam</option>
                            <option value="07:30">07:30</option>
                            <option value="08:20">08:20</option>
                            <option value="09:10">09:10</option>
                            <option value="10:00">10:00</option>
                            <option value="10:50">10:50</option>
                            <option value="11:40">11:40</option>
                            <option value="13:00">13:00</option>
                            <option value="13:50">13:50</option>
                            <option value="14:40">14:40</option>
                            <option value="15:30">15:30</option>
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="jadwal_jam_selesai">Jam Selesai</label>
                    <div class="modal-input-container">
                        <select id="jadwal_jam_selesai" name="jam_selesai" required>
                            <option value="">Pilih Jam</option>
                            <option value="08:20">08:20</option>
                            <option value="09:10">09:10</option>
                            <option value="10:00">10:00</option>
                            <option value="10:50">10:50</option>
                            <option value="11:40">11:40</option>
                            <option value="12:30">12:30</option>
                            <option value="13:50">13:50</option>
                            <option value="14:40">14:40</option>
                            <option value="15:30">15:30</option>
                            <option value="16:20">16:20</option>
                            <option value="17:10">17:10</option>
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="jadwal_ruangan">Ruangan</label>
                    <div class="modal-input-container">
                        <select id="jadwal_ruangan" name="ruangan_id" required>
                            <option value="">Pilih Ruangan</option>
                            @foreach ($ruangans as $r)
                                <option value="{{ $r->id }}">{{ $r->kode }} ({{ $r->nama ?? '' }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="jadwal_dosen">Dosen Pengampu</label>
                    <div class="modal-input-container">
                        <select id="jadwal_dosen" name="dosen_id" required>
                            <option value="">Pilih Dosen</option>
                            @foreach ($dosenProdi as $d)
                                <option value="{{ $d->id }}">{{ $d->nama }} ({{ $d->jabatan ?? '' }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-footer-buttons">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalTambahJadwal')">Batal</button>
                    <button type="submit" class="btn-modal-submit">Simpan Jadwal</button>
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
    if(target) target.style.display = 'block';
    if (element) {
        document.querySelectorAll('.sidebar-nav .sidebar-link').forEach(function(link) { link.classList.remove('active'); });
        element.classList.add('active');
    }
    if (id) {
        history.replaceState(null, null, '#' + id);
    }
}

function applyHashSection() {
    let hash = window.location.hash.replace('#', '');
    if (hash && document.getElementById(hash)) {
        let targetLink = document.querySelector(`.sidebar-nav a[href*="#${hash}"]`);
        showSection(null, hash, targetLink);
    } else {
        let defaultLink = document.querySelector('.sidebar-nav a[href="{{ route('prodi.dashboard') }}"]');
        showSection(null, 'section-dashboard', defaultLink);
    }
}

window.addEventListener('DOMContentLoaded', applyHashSection);
window.addEventListener('hashchange', applyHashSection);

function filterSemester(sem, btn) {
    document.querySelectorAll('.semester-filter').forEach(b => {
        b.classList.remove('active');
        b.style.background = '#475569';
    });
    btn.classList.add('active');
    btn.style.background = '';

    document.querySelectorAll('.jadwal-row').forEach(row => {
        if (sem === 'all' || row.dataset.sem == sem) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
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