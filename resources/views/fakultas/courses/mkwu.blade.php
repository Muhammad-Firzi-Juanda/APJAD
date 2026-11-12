@extends('layouts.app')

@section('title', 'MKWU (Mata Kuliah Wajib Universitas) - Fakultas')

@section('content')
<div class="admin-layout">
    @include('partials.smart-sidebar', ['active' => 'mkwu', 'activeFakultas' => 'mkwu'])

    <main class="admin-main">
        <header class="admin-header">
            @include('partials.smart-user-badge')
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
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
                <div>
                    <h1 class="page-title" style="margin-bottom:4px;">MKWU (Mata Kuliah Wajib Universitas)</h1>
                    <p style="color:#64748b; font-size:0.95rem; margin:0;">
                        Kelola data mata kuliah kurikulum tingkat universitas (Kode kategori: <strong>UNV</strong>).
                    </p>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                    <a href="{{ route('fakultas.courses.template', ['category' => 'UNV']) }}" style="display:inline-flex; align-items:center; gap:6px; background:#fff; color:#0369a1; border:1px solid #bae6fd; padding:9px 14px; border-radius:6px; font-weight:600; text-decoration:none; font-size:13px;" download>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        <span>Unduh Template</span>
                    </a>
                    <button type="button" onclick="openModal('modalImportMk')" style="display:inline-flex; align-items:center; gap:6px; background:#16a34a; color:#fff; border:none; padding:9px 14px; border-radius:6px; font-weight:600; cursor:pointer; font-size:13px; box-shadow:0 2px 6px rgba(22,163,74,0.25);">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        <span>Import Excel</span>
                    </button>
                    <a href="{{ route('fakultas.penjadwalan') }}" style="display:inline-flex; align-items:center; gap:6px; background:#7c3aed; color:#fff; text-decoration:none; padding:9px 14px; border-radius:6px; font-weight:600; font-size:13px; box-shadow:0 2px 6px rgba(124,58,237,0.25);">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        <span>Generate Jadwal</span>
                    </a>
                    <button type="button" onclick="openModal('modalAddCourse')" style="display:inline-flex; align-items:center; gap:6px; background:#2563eb; color:#fff; padding:9px 16px; border-radius:6px; border:none; font-weight:600; cursor:pointer; font-size:13px; box-shadow:0 2px 6px rgba(37,99,235,0.25);">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        <span>Tambah MKWU</span>
                    </button>
                </div>
            </div>

            {{-- Filter Bar --}}
            <div class="card" style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:16px; margin-bottom:20px;">
                <form action="{{ route('fakultas.mkwu') }}" method="GET" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode atau nama..." style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; min-width:220px;">
                    
                    <select name="semester" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                        <option value="">Semua Semester</option>
                        @for($s = 1; $s <= 8; $s++)
                            <option value="{{ $s }}" {{ request('semester') == $s ? 'selected' : '' }}>Semester {{ $s }}</option>
                        @endfor
                    </select>

                    <select name="type" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                        <option value="">Semua Jenis</option>
                        <option value="THEORY" {{ request('type') === 'THEORY' ? 'selected' : '' }}>Teori (THEORY)</option>
                        <option value="PRACTICUM" {{ request('type') === 'PRACTICUM' ? 'selected' : '' }}>Praktikum (PRACTICUM)</option>
                    </select>

                    <select name="status" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                        <option value="">Filter Status: Semua</option>
                        <option value="ACTIVE" {{ request('status') === 'ACTIVE' ? 'selected' : '' }}>ACTIVE</option>
                        <option value="INACTIVE" {{ request('status') === 'INACTIVE' ? 'selected' : '' }}>INACTIVE</option>
                    </select>

                    <button type="submit" style="padding:8px 16px; background:#0f172a; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer;">Filter</button>
                    <a href="{{ route('fakultas.mkwu') }}" style="padding:8px 14px; background:#f1f5f9; color:#475569; text-decoration:none; border-radius:6px; font-size:13px; font-weight:500;">Reset</a>
                </form>
            </div>

            {{-- Batch Action Bar (Bulk Delete) --}}
            <div id="batchActionBar" style="display:none; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:10px 16px; margin-bottom:14px; align-items:center; justify-content:space-between;">
                <div style="font-size:13px; font-weight:600; color:#1e40af; display:flex; align-items:center; gap:8px;">
                    <span id="selectedCountBadge" style="background:#2563eb; color:#fff; border-radius:12px; padding:2px 8px; font-size:11px;">0</span>
                    <span>data MKWU dipilih</span>
                </div>
                <button type="button" onclick="submitBulkDelete()" style="background:#ef4444; color:#fff; border:none; padding:7px 14px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    <span>Hapus Terpilih</span>
                </button>
            </div>

            {{-- Form Tersembunyi Bulk Delete --}}
            <form id="formBulkDelete" action="{{ route('fakultas.courses.bulk-delete') }}" method="POST" style="display:none;">
                @csrf
                <input type="hidden" name="category_code" value="UNV">
                <div id="bulkDeleteInputsContainer"></div>
            </form>

            {{-- Table MKWU --}}
            <div class="card" style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden;">
                <div class="table-responsive">
                    <table class="data-table" style="width:100%; border-collapse:collapse; font-size:13px;">
                        <thead>
                            <tr style="background:#f8fafc; text-align:left; border-bottom:1px solid #e2e8f0;">
                                <th style="padding:12px 14px; width:40px; text-align:center;">
                                    <input type="checkbox" id="selectAllCourses" onchange="toggleSelectAll(this)" style="cursor:pointer; width:16px; height:16px;">
                                </th>
                                <th style="padding:12px 16px;">Kode</th>
                                <th style="padding:12px 16px;">Nama Mata Kuliah</th>
                                <th style="padding:12px 16px;">SKS</th>
                                <th style="padding:12px 16px;">Semester</th>
                                <th style="padding:12px 16px;">Jenis</th>
                                <th style="padding:12px 16px;">Status</th>
                                <th style="padding:12px 16px; text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($courses as $c)
                                <tr style="border-bottom:1px solid #f1f5f9;">
                                    <td style="padding:12px 14px; text-align:center;">
                                        <input type="checkbox" class="course-checkbox" value="{{ $c->id }}" onchange="updateBatchActionBar()" style="cursor:pointer; width:16px; height:16px;">
                                    </td>
                                    <td style="padding:12px 16px; font-weight:700; color:#1e293b;">{{ $c->code }}</td>
                                    <td style="padding:12px 16px;">{{ $c->name }}</td>
                                    <td style="padding:12px 16px;">{{ $c->sks }}</td>
                                    <td style="padding:12px 16px;">Semester {{ $c->semester }}</td>
                                    <td style="padding:12px 16px;">
                                        <span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:600; background:{{ $c->type === 'THEORY' ? '#e0e7ff; color:#3730a3' : '#fef3c7; color:#92400e' }};">
                                            {{ $c->type === 'THEORY' ? 'Teori' : 'Praktikum' }}
                                        </span>
                                    </td>
                                    <td style="padding:12px 16px;">
                                        <form action="{{ route('fakultas.courses.toggle-status', $c) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" style="background:none; border:none; cursor:pointer; padding:0;" title="Klik untuk mengubah status">
                                                <span style="display:inline-block; padding:3px 10px; border-radius:12px; font-size:11px; font-weight:600; background:{{ $c->status === 'ACTIVE' ? '#dcfce7; color:#15803d' : '#fee2e2; color:#b91c1c' }};">
                                                    {{ $c->status }}
                                                </span>
                                            </button>
                                        </form>
                                    </td>
                                    <td style="padding:12px 16px; text-align:right;">
                                        <div style="display:inline-flex; gap:6px; align-items:center;">
                                            <button type="button" 
                                                class="btn-action edit-btn btn-edit-course"
                                                title="Edit MKWU"
                                                data-id="{{ $c->id }}"
                                                data-code="{{ $c->code }}"
                                                data-name="{{ $c->name }}"
                                                data-sks="{{ $c->sks }}"
                                                data-semester="{{ $c->semester }}"
                                                data-type="{{ $c->type }}"
                                                data-status="{{ $c->status }}"
                                                onclick="openEditModalFromBtn(this)">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </button>
                                            <form action="{{ route('fakultas.courses.destroy', $c) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus MKWU {{ $c->code }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-action delete-btn" title="Hapus MKWU">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" style="padding:24px; text-align:center; color:#94a3b8;">
                                        Tidak ada data MKWU yang sesuai.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($courses->hasPages())
                    <div class="pagination-wrapper" style="padding:14px 18px; border-top:1px solid #e2e8f0;">
                        <style>
                            .pagination-wrapper svg { width: 18px !important; height: 18px !important; max-width: 18px !important; max-height: 18px !important; display: inline-block !important; }
                        </style>
                        {{ $courses->links() }}
                    </div>
                @endif
            </div>
        </section>
    </main>
</div>

{{-- MODAL TAMBAH MKWU --}}
<div id="modalAddCourse" class="modal-backdrop">
    <div class="modal-window">
        <div class="modal-header-blue">
            <h3>Tambah MKWU Baru</h3>
        </div>
        <div class="modal-body">
            <form action="{{ route('fakultas.courses.store') }}" method="POST" class="modal-form">
                @csrf
                <input type="hidden" name="category_code" value="UNV">

                <div class="modal-form-row">
                    <label>Kode Mata Kuliah <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <input type="text" name="code" required placeholder="Contoh: UNV101" style="text-transform:uppercase; font-weight:600;">
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>Nama Mata Kuliah <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <input type="text" name="name" required placeholder="Contoh: Pendidikan Pancasila">
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>SKS & Semester <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container" style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div>
                            <input type="number" name="sks" min="1" max="6" value="2" required placeholder="SKS">
                            <span style="font-size:11px; color:#64748b;">SKS (1–6)</span>
                        </div>
                        <div>
                            <input type="number" name="semester" min="1" max="8" value="1" required placeholder="Semester">
                            <span style="font-size:11px; color:#64748b;">Semester (1–8)</span>
                        </div>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>Jenis Perkuliahan <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <select name="type" required>
                            <option value="THEORY">THEORY (Teori)</option>
                            <option value="PRACTICUM">PRACTICUM (Praktikum)</option>
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>Status</label>
                    <div class="modal-input-container">
                        <select name="status">
                            <option value="ACTIVE">ACTIVE</option>
                            <option value="INACTIVE">INACTIVE</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer-buttons" style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalAddCourse')">Batal</button>
                    <button type="submit" class="btn-modal-submit" style="background:#0860be; color:#fff; border:none; padding:8px 18px; border-radius:6px; font-weight:600; cursor:pointer;">Simpan MKWU</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL EDIT MKWU --}}
<div id="modalEditCourse" class="modal-backdrop">
    <div class="modal-window">
        <div class="modal-header-blue">
            <h3>Edit MKWU</h3>
        </div>
        <div class="modal-body">
            <form id="formEditCourse" method="POST" class="modal-form">
                @csrf
                @method('PUT')

                <div class="modal-form-row">
                    <label>Kode Mata Kuliah <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <input type="text" id="edit_code" name="code" required style="font-weight:600; text-transform:uppercase;">
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>Nama Mata Kuliah <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <input type="text" id="edit_name" name="name" required>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>SKS & Semester <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container" style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div>
                            <input type="number" id="edit_sks" name="sks" min="1" max="6" required>
                            <span style="font-size:11px; color:#64748b;">SKS (1–6)</span>
                        </div>
                        <div>
                            <input type="number" id="edit_semester" name="semester" min="1" max="8" required>
                            <span style="font-size:11px; color:#64748b;">Semester (1–8)</span>
                        </div>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>Jenis Perkuliahan <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <select id="edit_type" name="type" required>
                            <option value="THEORY">THEORY (Teori)</option>
                            <option value="PRACTICUM">PRACTICUM (Praktikum)</option>
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>Status</label>
                    <div class="modal-input-container">
                        <select id="edit_status" name="status">
                            <option value="ACTIVE">ACTIVE</option>
                            <option value="INACTIVE">INACTIVE</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer-buttons" style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalEditCourse')">Batal</button>
                    <button type="submit" class="btn-modal-submit" style="background:#0860be; color:#fff; border:none; padding:8px 18px; border-radius:6px; font-weight:600; cursor:pointer;">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL SMART IMPORT MKWU (EXCEL / SPREADSHEET) --}}
<div class="modal-backdrop" id="modalImportMk">
    <div class="modal-window" id="modalImportMkWindow" style="max-width: 650px; transition: max-width 0.3s ease;">
        <div class="modal-header-blue">
            <h3>Smart Import MKWU Universitas (Excel / Spreadsheet)</h3>
        </div>
        <div class="modal-body">
            <form action="{{ route('fakultas.courses.import') }}" method="POST" enctype="multipart/form-data" class="modal-form" id="formImportMkwu">
                @csrf
                <input type="hidden" name="category_code" value="UNV">
                <input type="hidden" name="verified_items" id="verifiedItemsInput">

                <div class="modal-form-row" style="align-items: flex-start;">
                    <label for="importFile" style="padding-top:8px;">File Spreadsheet</label>
                    <div class="modal-input-container" style="flex-direction:column; align-items:flex-start; gap:6px; width:100%;">
                        <input type="file" id="importFile" name="file" accept=".xlsx,.xls,.csv,.ods" style="width:100%;">
                        <div style="display:flex; justify-content:space-between; align-items:center; width:100%; flex-wrap:wrap; gap:8px;">
                            <span style="font-size:12px; color:#64748b;">Mendukung: Excel (.xlsx, .xls), CSV (.csv), dan ODS (.ods).</span>
                            <a href="{{ route('fakultas.courses.template', ['category' => 'UNV']) }}" style="color:#0284c7; font-weight:600; font-size:12px; text-decoration:underline; display:inline-flex; align-items:center; gap:4px;" download>
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
                            Pratinjau Data (Bisa diedit/dikoreksi langsung sebelum disimpan):
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
function openModal(modalId) {
    const el = document.getElementById(modalId);
    if (el) {
        el.classList.add('show');
    }
}

function closeModal(modalId) {
    const el = document.getElementById(modalId);
    if (el) {
        el.classList.remove('show');
        el.style.display = '';
    }
}

function openEditModal(course) {
    document.getElementById('formEditCourse').action = `/fakultas/courses/${course.id}`;
    document.getElementById('edit_code').value = course.code;
    document.getElementById('edit_name').value = course.name;
    document.getElementById('edit_sks').value = course.sks;
    document.getElementById('edit_semester').value = course.semester;
    document.getElementById('edit_type').value = course.type;
    if (document.getElementById('edit_status')) {
        document.getElementById('edit_status').value = course.status || 'ACTIVE';
    }
    openModal('modalEditCourse');
}

function openEditModalFromBtn(btn) {
    openEditModal({
        id: btn.dataset.id,
        code: btn.dataset.code,
        name: btn.dataset.name,
        sks: btn.dataset.sks,
        semester: btn.dataset.semester,
        type: btn.dataset.type,
        status: btn.dataset.status || 'ACTIVE'
    });
}

function toggleSelectAll(masterCheckbox) {
    const checkboxes = document.querySelectorAll('.course-checkbox');
    checkboxes.forEach(cb => cb.checked = masterCheckbox.checked);
    updateBatchActionBar();
}

function updateBatchActionBar() {
    const selected = document.querySelectorAll('.course-checkbox:checked');
    const bar = document.getElementById('batchActionBar');
    const badge = document.getElementById('selectedCountBadge');
    const master = document.getElementById('selectAllCourses');
    const all = document.querySelectorAll('.course-checkbox');

    if (badge) badge.textContent = selected.length;
    if (master && all.length > 0) {
        master.checked = (selected.length === all.length);
    }

    if (bar) {
        bar.style.display = selected.length > 0 ? 'flex' : 'none';
    }
}

function submitBulkDelete() {
    const selected = document.querySelectorAll('.course-checkbox:checked');
    if (selected.length === 0) {
        alert('Pilih minimal satu data mata kuliah untuk dihapus.');
        return;
    }

    if (!confirm(`Apakah Anda yakin ingin menghapus ${selected.length} mata kuliah MKWU yang dipilih?`)) {
        return;
    }

    const container = document.getElementById('bulkDeleteInputsContainer');
    container.innerHTML = '';
    selected.forEach(cb => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'selected_ids[]';
        input.value = cb.value;
        container.appendChild(input);
    });

    document.getElementById('formBulkDelete').submit();
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
    window.previewItemsList.unshift({
        kode_mk: 'UNV',
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

// File input change handler for auto preview
document.addEventListener('DOMContentLoaded', function () {
    const importFileInput = document.getElementById('importFile');
    const modalWindow = document.getElementById('modalImportMkWindow');
    const previewContainer = document.getElementById('importPreviewContainer');
    const previewStatus = document.getElementById('importPreviewStatus');

    if (importFileInput) {
        importFileInput.addEventListener('change', function () {
            if (!this.files || this.files.length === 0) return;

            const file = this.files[0];
            const formData = new FormData();
            formData.append('file', file);
            formData.append('category_code', 'UNV');
            formData.append('_token', '{{ csrf_token() }}');

            if (previewStatus) {
                previewStatus.style.display = 'block';
                previewStatus.innerHTML = '<div style="display:flex; align-items:center; gap:8px; color:#0369a1;"><strong>Menganalisis file Excel...</strong> Membaca kolom dan memverifikasi data MKWU.</div>';
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

document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('modal-backdrop')) {
        closeModal(e.target.id);
    }
});
</script>
@endsection


