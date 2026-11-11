@extends('layouts.app')

@section('title', 'APJAD - Generate Akun Massal via Excel')

@section('content')
@php
    $officialJabatanMap = \App\Services\AccountAuthorizationService::OFFICIAL_JABATAN_ROLE_MAP;

    $fakultasJson = $fakultas->map(fn($f) => [
        'id' => $f->id,
        'kode' => $f->kode,
        'nama' => $f->nama,
    ])->values();

    $jurusanJson = $jurusan->map(fn($j) => [
        'id' => $j->id,
        'fakultas_id' => $j->fakultas_id,
        'kode' => $j->kode,
        'nama' => $j->nama,
    ])->values();

    $prodiJson = $prodi->map(fn($p) => [
        'id' => $p->id,
        'fakultas_id' => $p->fakultas_id,
        'jurusan_id' => $p->jurusan_id,
        'kode' => $p->kode,
        'nama' => $p->nama,
    ])->values();

    $hasPreview = is_array($previewData) && isset($previewData['rows']) && is_array($previewData['rows']);
    $previewRows = $hasPreview ? $previewData['rows'] : [];
    $previewSummary = $hasPreview ? ($previewData['summary'] ?? []) : [];
@endphp

<div class="admin-layout">
    @include('admin.sidebar', ['active' => 'manajemen-akun-generate-excel'])

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

            {{-- Page Header --}}
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px; margin-bottom:24px;">
                <div>
                    <h1 class="page-title" style="margin-bottom:4px;">Generate Akun Massal via Excel</h1>
                    <p style="color:#64748b; font-size:0.92rem; margin:0;">
                        Unggah file Excel/CSV sesuai template resmi APJAD. Sistem memetakan <strong>Jabatan &rarr; Role Otomatis</strong> serta hierarki <strong>Fakultas / Jurusan / Program Studi</strong> di tabel Preview sebelum tombol <strong>ACC</strong> ditekan.
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
                    <a href="{{ route('manajemen-akun.riwayat-generate') }}" style="display: inline-flex; align-items: center; gap: 7px; border: 1px solid #bae6fd; background: #f8fafc; color: #0860be; padding: 9px 14px; border-radius: 8px; font-weight: 600; font-size: 0.86rem; text-decoration: none;">
                        <span>Riwayat Generate Akun</span>
                    </a>
                    <a href="{{ route('manajemen-akun.daftar') }}" class="btn-primary" style="display: inline-flex; align-items: center; gap: 7px; border: none; background: #0860be; color: #ffffff; padding: 9px 16px; border-radius: 8px; font-weight: 600; font-size: 0.86rem; text-decoration: none;">
                        <span>&larr; Kembali ke Manajemen Akun</span>
                    </a>
                </div>
            </div>

            {{-- One-Time Temporary Credentials Banner (After Auto-Password Commit) --}}
            @if(is_array($commitResult) && !empty($commitResult['credentials_token']))
                <div style="background:#fffbeb; border:2px solid #f59e0b; border-radius:12px; padding:18px 20px; margin-bottom:24px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
                    <div style="max-width:720px;">
                        <div style="font-weight:700; color:#92400e; font-size:0.98rem; margin-bottom:4px;">
                            Kredensial Sementara Siap Diunduh (Sekali Unduh / One-Time Download)
                        </div>
                        <p style="margin:0; font-size:0.86rem; color:#78350f; line-height:1.45;">
                            Sebanyak <strong>{{ $commitResult['created_count'] ?? 0 }}</strong> akun berhasil dibuat menggunakan metode <strong>Password Otomatis</strong>.
                            Password plaintext <u>tidak disimpan di database maupun riwayat</u>. Unduh file kredensial sementara sekarang untuk didistribusikan kepada pemilik akun.
                        </p>
                    </div>
                    <div>
                        <a href="{{ route('manajemen-akun.generate-excel.credentials', ['token' => $commitResult['credentials_token']]) }}"
                           style="display:inline-flex; align-items:center; gap:8px; background:#d97706; color:#fff; font-weight:700; font-size:0.88rem; padding:10px 18px; border-radius:10px; text-decoration:none;">
                            Unduh Kredensial Sementara (.xlsx)
                        </a>
                    </div>
                </div>
            @endif

            {{-- Step 1: Upload File & Aturan Mapping --}}
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:20px; margin-bottom:24px;">
                <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:20px;">
                    <h3 style="margin:0 0 8px 0; font-size:1rem; color:#1e293b;">1. Unggah File Excel / CSV untuk Preview</h3>
                    <p style="margin:0 0 16px 0; font-size:0.85rem; color:#64748b;">
                        Format yang didukung: <code>.xlsx</code>, <code>.xls</code>, <code>.csv</code> (maks. 10 MB). Mengunggah file hanya menampilkan <strong>Preview</strong> dan belum membuat akun.
                    </p>
                    <form action="{{ route('manajemen-akun.generate-excel.preview') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div style="border:2px dashed #cbd5e1; border-radius:10px; padding:18px; background:#f8fafc; margin-bottom:14px;">
                            <label for="file" style="display:block; font-weight:600; font-size:0.88rem; color:#334155; margin-bottom:8px;">
                                Pilih File Template Excel / CSV
                            </label>
                            <input type="file" id="file" name="file" accept=".xlsx,.xls,.csv" required
                                   style="width:100%; font-size:0.85rem; color:#334155;">
                        </div>
                        <div style="display:flex; justify-content:flex-end;">
                            <button type="submit" class="btn-primary" style="border:none; cursor:pointer; padding:10px 18px; border-radius:8px; background:#0860be; color:#ffffff; font-weight:600; font-size:0.88rem;">
                                Proses &amp; Tampilkan Preview
                            </button>
                        </div>
                    </form>
                </div>

                <div style="background:#1e293b; color:#f8fafc; border-radius:12px; padding:20px;">
                    <h3 style="margin:0 0 8px 0; font-size:0.95rem; color:#38bdf8; text-transform:uppercase; letter-spacing:0.5px;">
                        Aturan Pemetaan Jabatan &rarr; Role Otomatis
                    </h3>
                    <p style="margin:0 0 12px 0; font-size:0.83rem; color:#cbd5e1; line-height:1.45;">
                        File Excel tidak memiliki kolom <strong>Role</strong> maupun <strong>Password</strong>. Role ditentukan otomatis dari kolom <strong>Jabatan</strong>:
                    </p>
                    <div style="display:flex; flex-direction:column; gap:8px; font-size:0.83rem;">
                        <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.08); padding:8px 12px; border-radius:8px;">
                            <span><code>Dekan</code> / <code>Wakil Dekan</code></span>
                            <span style="background:#3b82f6; color:#fff; padding:2px 8px; border-radius:6px; font-weight:700; font-size:0.76rem;">Role: Fakultas</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.08); padding:8px 12px; border-radius:8px;">
                            <span><code>Ketua Jurusan</code></span>
                            <span style="background:#0ea5e9; color:#fff; padding:2px 8px; border-radius:6px; font-weight:700; font-size:0.76rem;">Role: Jurusan</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.08); padding:8px 12px; border-radius:8px;">
                            <span><code>Ketua Program Studi</code></span>
                            <span style="background:#10b981; color:#fff; padding:2px 8px; border-radius:6px; font-weight:700; font-size:0.76rem;">Role: Prodi</span>
                        </div>
                    </div>
                    <p style="margin:12px 0 0 0; font-size:0.78rem; color:#fcd34d;">
                        Singkatan tidak baku (<em>Kajur</em>, <em>Kaprodi</em>, <em>WD 1</em>, <em>Staff</em>) ditandai <strong>ERROR</strong> dan dapat langsung dikoreksi pada tabel Preview.
                    </p>
                </div>
            </div>

            {{-- Step 2 & 3: Editable Preview Table + Password Configuration + ACC --}}
            @if($hasPreview)
                <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:20px;" id="previewSection">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; padding-bottom:16px; border-bottom:1px solid #e2e8f0; margin-bottom:16px;">
                        <div>
                            <h2 style="margin:0; font-size:1.1rem; color:#1e293b;">
                                2. Tabel Preview &amp; Koreksi Data ({{ $previewData['file_name'] ?? 'upload.xlsx' }})
                            </h2>
                            <p style="margin:4px 0 0 0; font-size:0.83rem; color:#64748b;">
                                Anda dapat mengedit langsung data pada baris Preview atau mengecualikan baris bermasalah sebelum menekan tombol <strong>ACC</strong>.
                            </p>
                        </div>

                        <div style="display:flex; flex-wrap:wrap; gap:8px; font-size:0.8rem; font-weight:700;">
                            <span style="padding:6px 12px; border-radius:8px; background:#f1f5f9; color:#334155;">
                                Baris Aktif: <span id="badgeActiveRows">{{ $previewSummary['included'] ?? 0 }}</span> / {{ $previewSummary['total'] ?? count($previewRows) }}
                            </span>
                            <span style="padding:6px 12px; border-radius:8px; background:#dcfce7; color:#166534;">
                                VALID: <span id="badgeValidCount">{{ $previewSummary['valid'] ?? 0 }}</span>
                            </span>
                            <span style="padding:6px 12px; border-radius:8px; background:#fef3c7; color:#92400e;">
                                WARNING: <span id="badgeWarningCount">{{ $previewSummary['warning'] ?? 0 }}</span>
                            </span>
                            <span style="padding:6px 12px; border-radius:8px; background:#fee2e2; color:#991b1b;">
                                ERROR: <span id="badgeErrorCount">{{ $previewSummary['error'] ?? 0 }}</span>
                            </span>
                        </div>
                    </div>

                    {{-- Breakdown Role Cards --}}
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px; margin-bottom:18px;">
                        <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; padding:12px 16px; display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <div style="font-size:0.75rem; font-weight:700; color:#1d4ed8; text-transform:uppercase;">Akun Fakultas</div>
                                <div style="font-size:0.72rem; color:#64748b;">Dekan &amp; Wakil Dekan</div>
                            </div>
                            <div style="font-size:1.4rem; font-weight:800; color:#1e40af;" id="countRoleFakultas">
                                {{ $previewSummary['count_fakultas'] ?? 0 }}
                            </div>
                        </div>
                        <div style="background:#f0f9ff; border:1px solid #bae6fd; border-radius:10px; padding:12px 16px; display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <div style="font-size:0.75rem; font-weight:700; color:#0369a1; text-transform:uppercase;">Akun Jurusan</div>
                                <div style="font-size:0.72rem; color:#64748b;">Ketua Jurusan</div>
                            </div>
                            <div style="font-size:1.4rem; font-weight:800; color:#075985;" id="countRoleJurusan">
                                {{ $previewSummary['count_jurusan'] ?? 0 }}
                            </div>
                        </div>
                        <div style="background:#ecfdf5; border:1px solid #a7f3d0; border-radius:10px; padding:12px 16px; display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <div style="font-size:0.75rem; font-weight:700; color:#047857; text-transform:uppercase;">Akun Prodi</div>
                                <div style="font-size:0.72rem; color:#64748b;">Ketua Program Studi</div>
                            </div>
                            <div style="font-size:1.4rem; font-weight:800; color:#065f46;" id="countRoleProdi">
                                {{ $previewSummary['count_prodi'] ?? 0 }}
                            </div>
                        </div>
                    </div>

                    <form id="bulkCommitForm" action="{{ route('manajemen-akun.generate-excel.commit') }}" method="POST">
                        @csrf
                        <input type="hidden" name="file_name" value="{{ $previewData['file_name'] ?? 'Upload Excel Massal' }}">
                        <input type="hidden" name="idempotency_key" value="{{ $previewData['idempotency_key'] ?? (string) \Illuminate\Support\Str::uuid() }}">

                        {{-- Step 3: Password Method Configuration --}}
                        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:16px; margin-bottom:18px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                                <div>
                                    <h3 style="margin:0; font-size:0.95rem; color:#1e293b;">3. Pengaturan Password Setelah Preview</h3>
                                    <p style="margin:2px 0 0 0; font-size:0.8rem; color:#64748b;">
                                        Pilih metode pembuatan password untuk akun yang akan di-ACC:
                                    </p>
                                </div>
                                <div style="display:flex; gap:12px; flex-wrap:wrap;">
                                    <label style="display:inline-flex; align-items:center; gap:8px; background:#fff; border:1px solid #cbd5e1; padding:8px 14px; border-radius:8px; cursor:pointer; font-size:0.83rem; font-weight:700; color:#1e293b;">
                                        <input type="radio" name="password_method" value="auto"
                                               {{ old('password_method', 'auto') === 'auto' ? 'checked' : '' }}
                                               onchange="togglePasswordMethod(this.value)">
                                        <span>Opsi 1 — Password Otomatis (Direkomendasikan)</span>
                                    </label>
                                    <label style="display:inline-flex; align-items:center; gap:8px; background:#fff; border:1px solid #cbd5e1; padding:8px 14px; border-radius:8px; cursor:pointer; font-size:0.83rem; font-weight:700; color:#1e293b;">
                                        <input type="radio" name="password_method" value="manual"
                                               {{ old('password_method') === 'manual' ? 'checked' : '' }}
                                               onchange="togglePasswordMethod(this.value)">
                                        <span>Opsi 2 — Password Manual per Akun</span>
                                    </label>
                                </div>
                            </div>
                            <div id="passwordAutoHint" style="margin-top:10px; font-size:0.8rem; color:#475569; background:#fff; border:1px solid #e2e8f0; padding:10px 12px; border-radius:8px;">
                                <strong>Password Otomatis:</strong> Sistem men-generate password acak yang kuat dan unik per akun, menyimpannya dalam bentuk hash, mengaktifkan wajib ganti password saat login pertama, dan menyediakan unduhan kredensial sementara sekali unduh setelah ACC.
                            </div>
                            <div id="passwordManualHint" style="display:none; margin-top:10px; font-size:0.8rem; color:#475569; background:#fff; border:1px solid #e2e8f0; padding:10px 12px; border-radius:8px;">
                                <strong>Password Manual:</strong> Isi kolom <em>Password Manual</em> dan <em>Konfirmasi Password</em> (minimal 6 karakter) pada setiap baris aktif di tabel Preview di bawah.
                            </div>
                        </div>

                        {{-- Editable Table --}}
                        <div class="table-container" style="overflow-x:auto; margin-bottom:18px;">
                            <table class="data-table" id="previewTable" style="min-width:1280px; font-size:0.82rem;">
                                <thead>
                                    <tr>
                                        <th style="width:45px;">No</th>
                                        <th style="min-width:165px;">Nama Lengkap</th>
                                        <th style="min-width:175px;">Email</th>
                                        <th style="min-width:135px;">NUPTK &amp; No. HP</th>
                                        <th style="min-width:165px;">Jabatan Resmi</th>
                                        <th style="min-width:170px;">Fakultas</th>
                                        <th style="min-width:185px;">Jurusan</th>
                                        <th style="min-width:190px;">Program Studi</th>
                                        <th style="min-width:105px;">Role Otomatis</th>
                                        <th class="manual-password-col" style="display:none; min-width:185px;">Password Manual</th>
                                        <th style="min-width:220px;">Status Validasi</th>
                                        <th style="width:85px; text-align:center;">Kecualikan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($previewRows as $idx => $row)
                                        @php
                                            $status = $row['status'] ?? 'VALID';
                                            $isExcluded = !empty($row['excluded']);
                                            $rowStyle = $isExcluded
                                                ? 'background:#f1f5f9; opacity:0.6;'
                                                : ($status === 'ERROR' ? 'background:#fff1f2;' : ($status === 'WARNING' ? 'background:#fffbeb;' : ''));
                                            $currentJabatan = $row['jabatan'] ?? ($row['jabatan_raw'] ?? '');
                                            $isOfficialJabatan = in_array($currentJabatan, $officialJabatan, true);
                                        @endphp
                                        <tr class="preview-row" data-row-index="{{ $idx }}" style="{{ $rowStyle }}">
                                            <td style="vertical-align:top; font-weight:700;">
                                                {{ $row['row_number'] ?? ($idx + 1) }}
                                                <input type="hidden" name="rows[{{ $idx }}][row_number]" value="{{ $row['row_number'] ?? ($idx + 1) }}">
                                                <input type="hidden" name="rows[{{ $idx }}][_changed_field]" class="row-changed-field" value="">
                                            </td>

                                            <td style="vertical-align:top;">
                                                <input type="text"
                                                       name="rows[{{ $idx }}][nama]"
                                                       value="{{ $row['nama'] ?? '' }}"
                                                       placeholder="Nama lengkap"
                                                       class="row-input-nama"
                                                       style="width:100%; padding:6px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.8rem;">
                                            </td>

                                            <td style="vertical-align:top;">
                                                <input type="email"
                                                       name="rows[{{ $idx }}][email]"
                                                       value="{{ $row['email'] ?? '' }}"
                                                       placeholder="email@kampus.ac.id"
                                                       class="row-input-email"
                                                       style="width:100%; padding:6px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.8rem;">
                                            </td>

                                            <td style="vertical-align:top;">
                                                <input type="text"
                                                       name="rows[{{ $idx }}][nuptk]"
                                                       value="{{ $row['nuptk'] ?? '' }}"
                                                       placeholder="NUPTK (opsional)"
                                                       class="row-input-nuptk"
                                                       style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.78rem; margin-bottom:4px;">
                                                <input type="text"
                                                       name="rows[{{ $idx }}][no_hp]"
                                                       value="{{ $row['no_hp'] ?? '' }}"
                                                       placeholder="No. HP (opsional)"
                                                       class="row-input-nohp"
                                                       style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.78rem;">
                                            </td>

                                            <td style="vertical-align:top;">
                                                <select name="rows[{{ $idx }}][jabatan]"
                                                        class="row-select-jabatan"
                                                        onchange="handleRowJabatanChange({{ $idx }})"
                                                        style="width:100%; padding:6px 8px; border:1px solid {{ $isOfficialJabatan ? '#cbd5e1' : '#ef4444' }}; border-radius:6px; font-size:0.8rem;">
                                                    <option value="">-- Pilih Jabatan Resmi --</option>
                                                    @if(!$isOfficialJabatan && $currentJabatan !== '')
                                                        <option value="{{ $currentJabatan }}" selected>[Tidak Valid] {{ $currentJabatan }}</option>
                                                    @endif
                                                    @foreach($officialJabatan as $jabatanOption)
                                                        <option value="{{ $jabatanOption }}" {{ $currentJabatan === $jabatanOption ? 'selected' : '' }}>
                                                            {{ $jabatanOption }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>

                                            <td style="vertical-align:top;">
                                                <select name="rows[{{ $idx }}][fakultas_id]"
                                                        class="row-select-fakultas"
                                                        onchange="handleRowFakultasChange({{ $idx }})"
                                                        style="width:100%; padding:6px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.8rem;">
                                                    <option value="">-- Pilih Fakultas --</option>
                                                    @foreach($fakultas as $fak)
                                                        <option value="{{ $fak->id }}" {{ (int) ($row['fakultas_id'] ?? 0) === (int) $fak->id ? 'selected' : '' }}>
                                                            {{ $fak->nama }} ({{ $fak->kode }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>

                                            <td style="vertical-align:top;">
                                                <select name="rows[{{ $idx }}][jurusan_id]"
                                                        class="row-select-jurusan"
                                                        onchange="handleRowJurusanChange({{ $idx }})"
                                                        style="width:100%; padding:6px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.8rem;">
                                                    <option value="">-- Pilih Jurusan --</option>
                                                    @foreach($jurusan as $jur)
                                                        <option value="{{ $jur->id }}"
                                                                data-fakultas-id="{{ $jur->fakultas_id }}"
                                                                {{ (int) ($row['jurusan_id'] ?? 0) === (int) $jur->id ? 'selected' : '' }}>
                                                            {{ $jur->nama }} ({{ $jur->kode }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>

                                            <td style="vertical-align:top;">
                                                <select name="rows[{{ $idx }}][prodi_id]"
                                                        class="row-select-prodi"
                                                        onchange="handleRowProdiChange({{ $idx }})"
                                                        style="width:100%; padding:6px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.8rem;">
                                                    <option value="">-- Pilih Program Studi --</option>
                                                    @foreach($prodi as $prd)
                                                        <option value="{{ $prd->id }}"
                                                                data-jurusan-id="{{ $prd->jurusan_id }}"
                                                                data-fakultas-id="{{ $prd->fakultas_id }}"
                                                                {{ (int) ($row['prodi_id'] ?? 0) === (int) $prd->id ? 'selected' : '' }}>
                                                            {{ $prd->nama }} ({{ $prd->kode }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>

                                            <td style="vertical-align:top;">
                                                <span class="row-role-badge" style="display:inline-block; padding:4px 10px; border-radius:6px; font-weight:700; font-size:0.76rem; background:#e0f2fe; color:#0369a1;">
                                                    {{ $row['role'] ?? '-' }}
                                                </span>
                                            </td>

                                            <td class="manual-password-col" style="display:none; vertical-align:top;">
                                                <input type="password"
                                                       name="rows[{{ $idx }}][manual_password]"
                                                       value="{{ $row['manual_password'] ?? '' }}"
                                                       placeholder="Password (min. 6)"
                                                       class="row-input-pass"
                                                       style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.78rem; margin-bottom:4px;">
                                                <input type="password"
                                                       name="rows[{{ $idx }}][manual_password_confirmation]"
                                                       value="{{ $row['manual_password_confirmation'] ?? '' }}"
                                                       placeholder="Konfirmasi Password"
                                                       class="row-input-pass-confirm"
                                                       style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.78rem;">
                                            </td>

                                            <td style="vertical-align:top;">
                                                @if($isExcluded)
                                                    <span style="display:inline-block; padding:3px 8px; border-radius:6px; background:#cbd5e1; color:#334155; font-weight:700; font-size:0.74rem;">
                                                        DIKECUALIKAN
                                                    </span>
                                                @elseif($status === 'ERROR')
                                                    <span style="display:inline-block; padding:3px 8px; border-radius:6px; background:#fee2e2; color:#991b1b; font-weight:700; font-size:0.74rem;">
                                                        ERROR
                                                    </span>
                                                @elseif($status === 'WARNING')
                                                    <span style="display:inline-block; padding:3px 8px; border-radius:6px; background:#fef3c7; color:#92400e; font-weight:700; font-size:0.74rem;">
                                                        WARNING
                                                    </span>
                                                @else
                                                    <span style="display:inline-block; padding:3px 8px; border-radius:6px; background:#dcfce7; color:#166534; font-weight:700; font-size:0.74rem;">
                                                        VALID
                                                    </span>
                                                @endif

                                                @if(!empty($row['errors']))
                                                    <ul style="margin:6px 0 0 16px; padding:0; color:#b91c1c; font-size:0.75rem;">
                                                        @foreach($row['errors'] as $errText)
                                                            <li>{{ $errText }}</li>
                                                        @endforeach
                                                    </ul>
                                                @endif

                                                @if(!empty($row['warnings']))
                                                    <ul style="margin:6px 0 0 16px; padding:0; color:#b45309; font-size:0.75rem;">
                                                        @foreach($row['warnings'] as $warnText)
                                                            <li>{{ $warnText }}</li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </td>

                                            <td style="vertical-align:top; text-align:center;">
                                                <input type="hidden" name="rows[{{ $idx }}][excluded]" value="0">
                                                <label style="display:inline-flex; flex-direction:column; align-items:center; gap:4px; cursor:pointer; font-size:0.72rem; color:#64748b;">
                                                    <input type="checkbox"
                                                           name="rows[{{ $idx }}][excluded]"
                                                           value="1"
                                                           class="row-checkbox-exclude"
                                                           {{ $isExcluded ? 'checked' : '' }}
                                                           onchange="recalculatePreviewClientSide()">
                                                    <span>Lewati</span>
                                                </label>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                            <button type="submit"
                                    formaction="{{ route('manajemen-akun.generate-excel.revalidate') }}"
                                    style="padding:10px 16px; border-radius:8px; border:1px solid #cbd5e1; background:#f8fafc; color:#1e293b; font-weight:700; font-size:0.85rem; cursor:pointer;">
                                Validasi Ulang Data Preview
                            </button>

                            <button type="button"
                                    id="btnOpenConfirmModal"
                                    onclick="openAccConfirmModal()"
                                    class="btn-primary"
                                    style="border:none; cursor:pointer; padding:11px 22px; border-radius:8px; background:#16a34a; color:#ffffff; font-weight:700; font-size:0.9rem;">
                                ACC &amp; Buat Akun
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Modal Konfirmasi Sebelum Eksekusi Akhir (ACC) --}}
                <div id="accConfirmModal" class="modal-backdrop">
                    <div class="modal-window" style="max-width:480px;">
                        <div class="modal-header-blue">
                            <h3>Konfirmasi Pembuatan Akun Massal (ACC)</h3>
                        </div>
                        <div class="modal-body" style="padding:20px;">
                            <p style="margin:0 0 14px 0; font-size:0.88rem; color:#475569;">
                                Periksa ringkasan akun yang akan dibuat sebelum melanjutkan eksekusi:
                            </p>
                            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:14px; margin-bottom:18px; font-size:0.86rem;">
                                <div style="display:flex; justify-content:space-between; margin-bottom:10px; padding-bottom:8px; border-bottom:1px solid #e2e8f0;">
                                    <span>Total Akun yang Akan Dibuat:</span>
                                    <strong id="modalTotalAccounts" style="font-size:1rem; color:#0860be;">0</strong>
                                </div>
                                <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:8px; text-align:center; margin-bottom:10px;">
                                    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:8px;">
                                        <div style="font-size:0.72rem; color:#64748b;">FAKULTAS</div>
                                        <strong id="modalCountFakultas" style="font-size:0.95rem; color:#1d4ed8;">0</strong>
                                    </div>
                                    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:8px;">
                                        <div style="font-size:0.72rem; color:#64748b;">JURUSAN</div>
                                        <strong id="modalCountJurusan" style="font-size:0.95rem; color:#0369a1;">0</strong>
                                    </div>
                                    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:8px;">
                                        <div style="font-size:0.72rem; color:#64748b;">PRODI</div>
                                        <strong id="modalCountProdi" style="font-size:0.95rem; color:#047857;">0</strong>
                                    </div>
                                </div>
                                <div style="display:flex; justify-content:space-between; padding-top:8px; border-top:1px solid #e2e8f0;">
                                    <span>Metode Pengaturan Password:</span>
                                    <strong id="modalPasswordMethod">Password Otomatis</strong>
                                </div>
                            </div>

                            <div class="modal-footer-buttons">
                                <button type="button" class="btn-modal-cancel" onclick="closeAccConfirmModal()">Batal</button>
                                <button type="button" id="btnFinalSubmitAcc" class="btn-modal-submit" onclick="submitBulkCommitForm()">
                                    Ya, ACC &amp; Buat Akun
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </section>
    </main>
</div>

<script>
    const OFFICIAL_JABATAN_MAP = @json($officialJabatanMap);
    const FAKULTAS_LIST = @json($fakultasJson);

    function togglePasswordMethod(method) {
        const cols = document.querySelectorAll('.manual-password-col');
        const autoHint = document.getElementById('passwordAutoHint');
        const manualHint = document.getElementById('passwordManualHint');

        if (method === 'manual') {
            cols.forEach(el => el.style.display = '');
            if (autoHint) autoHint.style.display = 'none';
            if (manualHint) manualHint.style.display = 'block';
        } else {
            cols.forEach(el => el.style.display = 'none');
            if (autoHint) autoHint.style.display = 'block';
            if (manualHint) manualHint.style.display = 'none';
        }
    }

    function getRowElements(rowIndex) {
        const row = document.querySelector(`.preview-row[data-row-index="${rowIndex}"]`);
        if (!row) return null;
        return {
            row,
            jabatan: row.querySelector('.row-select-jabatan'),
            fakultas: row.querySelector('.row-select-fakultas'),
            jurusan: row.querySelector('.row-select-jurusan'),
            prodi: row.querySelector('.row-select-prodi'),
            roleBadge: row.querySelector('.row-role-badge'),
            exclude: row.querySelector('.row-checkbox-exclude'),
            changedField: row.querySelector('.row-changed-field'),
        };
    }

    function handleRowJabatanChange(rowIndex) {
        const els = getRowElements(rowIndex);
        if (!els) return;

        if (els.changedField) els.changedField.value = 'jabatan';

        const jabatanVal = els.jabatan.value;
        const role = OFFICIAL_JABATAN_MAP[jabatanVal] || null;

        if (role) {
            els.roleBadge.textContent = role;
            if (role === 'Fakultas') {
                els.jurusan.value = '';
                els.prodi.value = '';
                if (!els.fakultas.value && FAKULTAS_LIST.length === 1) {
                    els.fakultas.value = String(FAKULTAS_LIST[0].id);
                }
            } else if (role === 'Jurusan') {
                els.prodi.value = '';
                if (els.jurusan.value) {
                    handleRowJurusanChange(rowIndex);
                }
            } else if (role === 'Prodi') {
                if (els.prodi.value) {
                    handleRowProdiChange(rowIndex);
                }
            }
        } else {
            els.roleBadge.textContent = '-';
        }

        filterRowProdiOptions(rowIndex);
        recalculatePreviewClientSide();
    }

    function handleRowFakultasChange(rowIndex) {
        const els = getRowElements(rowIndex);
        if (els && els.changedField) els.changedField.value = 'fakultas';
        filterRowProdiOptions(rowIndex);
        recalculatePreviewClientSide();
    }

    function handleRowJurusanChange(rowIndex) {
        const els = getRowElements(rowIndex);
        if (!els) return;

        if (els.changedField) els.changedField.value = 'jurusan';

        const selectedOption = els.jurusan.options[els.jurusan.selectedIndex];
        const parentFakultasId = selectedOption ? selectedOption.getAttribute('data-fakultas-id') : null;
        if (parentFakultasId) {
            els.fakultas.value = String(parentFakultasId);
        }

        filterRowProdiOptions(rowIndex);
        recalculatePreviewClientSide();
    }

    function handleRowProdiChange(rowIndex) {
        const els = getRowElements(rowIndex);
        if (!els) return;

        if (els.changedField) els.changedField.value = 'prodi';

        const selectedOption = els.prodi.options[els.prodi.selectedIndex];
        if (selectedOption && selectedOption.value) {
            const parentJurusanId = selectedOption.getAttribute('data-jurusan-id');
            const parentFakultasId = selectedOption.getAttribute('data-fakultas-id');
            if (parentJurusanId) {
                els.jurusan.value = String(parentJurusanId);
            }
            if (parentFakultasId) {
                els.fakultas.value = String(parentFakultasId);
            }
        }

        filterRowProdiOptions(rowIndex);
        recalculatePreviewClientSide();
    }

    function filterRowProdiOptions(rowIndex) {
        const els = getRowElements(rowIndex);
        if (!els) return;

        const selectedJurusanId = els.jurusan.value;
        const currentProdiId = els.prodi.value;

        Array.from(els.prodi.options).forEach(opt => {
            if (!opt.value) return;
            const prodiJurusanId = opt.getAttribute('data-jurusan-id');
            opt.hidden = Boolean(selectedJurusanId && prodiJurusanId !== selectedJurusanId);
        });

        if (selectedJurusanId && currentProdiId) {
            const selectedOpt = els.prodi.querySelector(`option[value="${currentProdiId}"]`);
            if (selectedOpt && selectedOpt.getAttribute('data-jurusan-id') !== selectedJurusanId) {
                els.prodi.value = '';
            }
        }
    }

    function recalculatePreviewClientSide() {
        const rows = document.querySelectorAll('.preview-row');
        let activeCount = 0;
        let roleFakultas = 0;
        let roleJurusan = 0;
        let roleProdi = 0;

        rows.forEach((row) => {
            const idx = row.getAttribute('data-row-index');
            const els = getRowElements(idx);
            if (!els) return;

            const isExcluded = els.exclude && els.exclude.checked;
            if (isExcluded) {
                row.style.opacity = '0.6';
                return;
            } else {
                row.style.opacity = '1';
            }

            activeCount++;
            const role = OFFICIAL_JABATAN_MAP[els.jabatan.value] || null;
            if (role === 'Fakultas') roleFakultas++;
            if (role === 'Jurusan') roleJurusan++;
            if (role === 'Prodi') roleProdi++;
        });

        const elActive = document.getElementById('badgeActiveRows');
        if (elActive) elActive.textContent = String(activeCount);

        const elFak = document.getElementById('countRoleFakultas');
        const elJur = document.getElementById('countRoleJurusan');
        const elPro = document.getElementById('countRoleProdi');
        if (elFak) elFak.textContent = String(roleFakultas);
        if (elJur) elJur.textContent = String(roleJurusan);
        if (elPro) elPro.textContent = String(roleProdi);
    }

    function openAccConfirmModal() {
        recalculatePreviewClientSide();
        const activeCount = parseInt(document.getElementById('badgeActiveRows')?.textContent || '0', 10);
        if (activeCount <= 0) {
            alert('Tidak ada baris aktif yang dipilih untuk diproses.');
            return;
        }

        document.getElementById('modalTotalAccounts').textContent = String(activeCount);
        document.getElementById('modalCountFakultas').textContent = document.getElementById('countRoleFakultas')?.textContent || '0';
        document.getElementById('modalCountJurusan').textContent = document.getElementById('countRoleJurusan')?.textContent || '0';
        document.getElementById('modalCountProdi').textContent = document.getElementById('countRoleProdi')?.textContent || '0';

        const passMethod = document.querySelector('input[name="password_method"]:checked')?.value || 'auto';
        document.getElementById('modalPasswordMethod').textContent =
            passMethod === 'manual' ? 'Password Manual per Akun' : 'Password Otomatis (Wajib Ganti Saat Login Pertama)';

        const modal = document.getElementById('accConfirmModal');
        if (modal) modal.classList.add('show');
    }

    function closeAccConfirmModal() {
        const modal = document.getElementById('accConfirmModal');
        if (modal) modal.classList.remove('show');
    }

    function submitBulkCommitForm() {
        const form = document.getElementById('bulkCommitForm');
        if (!form) return;
        const btn = document.getElementById('btnFinalSubmitAcc');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Memproses...';
        }
        form.submit();
    }

    document.addEventListener('DOMContentLoaded', () => {
        const checkedMethod = document.querySelector('input[name="password_method"]:checked')?.value || 'auto';
        togglePasswordMethod(checkedMethod);

        document.querySelectorAll('.preview-row').forEach(row => {
            const idx = row.getAttribute('data-row-index');
            filterRowProdiOptions(idx);
        });
        recalculatePreviewClientSide();
    });
</script>
@endsection
