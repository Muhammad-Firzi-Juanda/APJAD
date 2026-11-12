@extends('layouts.app')

@section('title', 'APJAD - Mata Kuliah ' . $prodi->nama)

@section('content')
<div class="admin-layout">
    @include('admin.sidebar', ['active' => 'mk-prodi-' . $prodi->id])

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
                    <ul style="margin:0; padding-left:20px;">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Header Toolbar & Identitas Prodi --}}
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px; flex-wrap:wrap; gap:16px;">
                <div>
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px; flex-wrap:wrap;">
                        <span style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; font-size:11.5px; font-weight:700; padding:2px 8px; border-radius:12px; letter-spacing:0.5px;">
                            PRODI: {{ $prodi->kode }}
                        </span>
                        <span style="background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; font-size:11.5px; font-weight:600; padding:2px 8px; border-radius:12px;">
                            Jurusan: {{ $prodi->jurusan->nama ?? '-' }}
                        </span>
                        <span style="background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; font-size:11.5px; font-weight:600; padding:2px 8px; border-radius:12px;">
                            Fakultas: {{ $prodi->fakultas->nama ?? '-' }}
                        </span>
                    </div>
                    <h1 class="page-title" style="margin-bottom:4px;">Mata Kuliah {{ $prodi->nama }}</h1>
                    <p style="color:#64748b; font-size:0.95rem; margin:0;">
                        Kelola data mata kuliah, kurikulum per semester, dan dosen pengampu khusus Program Studi {{ $prodi->nama }}.
                    </p>
                </div>

                <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                    {{-- Switcher Pindah Prodi Cepat --}}
                    <div style="display:flex; align-items:center; gap:6px;">
                        <label for="prodiSwitcher" style="font-size:13px; font-weight:600; color:#475569;">Pilih Prodi:</label>
                        <select id="prodiSwitcher" onchange="if(this.value) window.location.href=this.value;" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; font-weight:600; color:#1e293b; background:#fff; cursor:pointer;">
                            @foreach($allProdis as $ap)
                                <option value="{{ route('admin.mata-kuliah.prodi', ['prodi' => $ap->id]) }}" {{ $ap->id === $prodi->id ? 'selected' : '' }}>
                                    {{ $ap->nama }} ({{ $ap->kode }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <a href="{{ route('penjadwalan.template') }}" style="display:inline-flex; align-items:center; gap:6px; background:#fff; color:#0369a1; border:1px solid #bae6fd; padding:8px 14px; border-radius:6px; font-weight:600; cursor:pointer; font-size:13px; text-decoration:none;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        <span>Template Excel</span>
                    </a>

                    <button type="button" class="btn-add" onclick="openModal('modalTambahMk')" style="background:#0860be; color:#fff; border:none; padding:9px 16px; border-radius:6px; font-weight:600; font-size:13px; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        <span>Tambah Mata Kuliah</span>
                    </button>
                </div>
            </div>

            {{-- 4 KPI Stat Cards --}}
            <div class="stats-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:24px;">
                <div class="stat-card">
                    <div class="stat-icon mata-kuliah-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Total Mata Kuliah</p>
                        <p class="stat-value">{{ $stats['total_mk'] }}</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon aktif-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Total Bobot SKS</p>
                        <p class="stat-value">{{ $stats['total_sks'] }}</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon dosen-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Dosen Prodi Ini</p>
                        <p class="stat-value">{{ $stats['total_dosen'] }}</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon rombel-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Total Kelas Dibuka</p>
                        <p class="stat-value">{{ $stats['total_kelas'] }}</p>
                    </div>
                </div>
            </div>

            {{-- Filter & Search Bar --}}
            <div class="card" style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:16px; margin-bottom:20px;">
                <form action="{{ route('admin.mata-kuliah.prodi', ['prodi' => $prodi->id]) }}" method="GET" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
                    <div style="flex:1; min-width:220px;">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kode atau nama mata kuliah..." style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                    </div>

                    <div>
                        <select name="semester" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                            <option value="all">Semua Semester</option>
                            @for($s=1; $s<=8; $s++)
                                <option value="{{ $s }}" {{ request('semester') == $s ? 'selected' : '' }}>Semester {{ $s }}</option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <select name="tipe" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                            <option value="all">Semua Tipe</option>
                            <option value="Wajib" {{ request('tipe') === 'Wajib' ? 'selected' : '' }}>Wajib</option>
                            <option value="Pilihan" {{ request('tipe') === 'Pilihan' ? 'selected' : '' }}>Pilihan</option>
                        </select>
                    </div>

                    <button type="submit" style="padding:8px 16px; background:#0860be; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer;">
                        Filter
                    </button>

                    @if(request()->hasAny(['q', 'semester', 'tipe']))
                        <a href="{{ route('admin.mata-kuliah.prodi', ['prodi' => $prodi->id]) }}" style="font-size:13px; color:#dc2626; text-decoration:none; font-weight:600;">
                            Reset Filter
                        </a>
                    @endif
                </form>
            </div>

            {{-- Daftar Mata Kuliah Per Semester --}}
            @forelse($mataKuliahs as $smt => $listMk)
                <div class="table-card" style="margin-bottom:24px; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; background:#fff;">
                    <div style="background:#f8fafc; border-bottom:1px solid #e2e8f0; padding:12px 18px; display:flex; justify-content:space-between; align-items:center;">
                        <h3 style="margin:0; font-size:15px; font-weight:700; color:#0f172a;">Semester {{ $smt }}</h3>
                        <span style="font-size:12px; color:#64748b; font-weight:600;">
                            {{ $listMk->count() }} Mata Kuliah &middot; {{ $listMk->sum('sks') }} SKS &middot; {{ $listMk->sum('jumlah_kelas') }} Kelas
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table" style="width:100%; border-collapse:collapse;">
                            <thead>
                                <tr style="background:#f1f5f9; text-align:left; font-size:12px; color:#475569;">
                                    <th style="padding:10px 14px; width:110px;">Kode MK</th>
                                    <th style="padding:10px 14px;">Nama Mata Kuliah</th>
                                    <th style="padding:10px 14px; text-align:center; width:80px;">SKS</th>
                                    <th style="padding:10px 14px; text-align:center; width:90px;">Tipe</th>
                                    <th style="padding:10px 14px;">Dosen Ketua</th>
                                    <th style="padding:10px 14px;">Dosen Anggota</th>
                                    <th style="padding:10px 14px; text-align:center; width:90px;">Kelas</th>
                                    <th style="padding:10px 14px; text-align:center; width:80px;">Lab</th>
                                    <th style="padding:10px 14px; text-align:center; width:110px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($listMk as $mk)
                                    <tr style="border-bottom:1px solid #f1f5f9; font-size:13px;">
                                        <td style="padding:10px 14px; font-weight:700; color:#0369a1;">
                                            <span style="background:#e0f2fe; padding:2px 6px; border-radius:4px; font-size:12px;">{{ $mk->kode_mk }}</span>
                                        </td>
                                        <td style="padding:10px 14px; font-weight:600; color:#0f172a;">{{ $mk->nama_mk }}</td>
                                        <td style="padding:10px 14px; text-align:center;">
                                            <span style="background:#f1f5f9; padding:2px 8px; border-radius:12px; font-weight:600; font-size:12px;">{{ $mk->sks }} SKS</span>
                                        </td>
                                        <td style="padding:10px 14px; text-align:center;">
                                            <span style="display:inline-block; padding:2px 8px; border-radius:12px; font-weight:700; font-size:11px; {{ $mk->tipe === 'Wajib' ? 'background:#dbeafe; color:#1e40af;' : 'background:#fef3c7; color:#92400e;' }}">
                                                {{ $mk->tipe }}
                                            </span>
                                        </td>
                                        <td style="padding:10px 14px; color:#334155;">{{ $mk->dosenKetua->nama ?? '-' }}</td>
                                        <td style="padding:10px 14px; color:#334155;">{{ $mk->dosenAnggota->nama ?? '-' }}</td>
                                        <td style="padding:10px 14px; text-align:center; font-weight:600;">{{ $mk->jumlah_kelas ?? 1 }} Kls</td>
                                        <td style="padding:10px 14px; text-align:center;">
                                            @if($mk->butuh_lab)
                                                <span style="background:#fef3c7; color:#b45309; padding:2px 8px; border-radius:12px; font-weight:700; font-size:11px;">Ya</span>
                                            @else
                                                <span style="background:#f1f5f9; color:#64748b; padding:2px 8px; border-radius:12px; font-weight:700; font-size:11px;">Tidak</span>
                                            @endif
                                        </td>
                                        <td style="padding:10px 14px; text-align:center;">
                                            <div style="display:inline-flex; gap:6px;">
                                                <button type="button" onclick="editMk({{ json_encode($mk) }})" style="background:#f1f5f9; border:1px solid #cbd5e1; padding:4px 8px; border-radius:4px; font-size:12px; cursor:pointer;" title="Edit Mata Kuliah">
                                                    ✏️
                                                </button>

                                                <form action="{{ route('admin.mata-kuliah.prodi.destroy', ['prodi' => $prodi->id, 'mataKuliah' => $mk->id]) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus mata kuliah {{ $mk->nama_mk }}?');" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" style="background:#fee2e2; border:1px solid #fecaca; color:#dc2626; padding:4px 8px; border-radius:4px; font-size:12px; cursor:pointer;" title="Hapus Mata Kuliah">
                                                        🗑️
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="card" style="text-align:center; padding:40px 20px; background:#fff; border:1px dashed #cbd5e1; border-radius:10px;">
                    <p style="font-size:15px; color:#64748b; margin-bottom:12px;">Belum ada mata kuliah yang terdaftar untuk Program Studi {{ $prodi->nama }}.</p>
                    <button type="button" class="btn-add" onclick="openModal('modalTambahMk')" style="background:#0860be; color:#fff; border:none; padding:8px 16px; border-radius:6px; font-weight:600; font-size:13px; cursor:pointer;">
                        + Tambah Mata Kuliah Pertama
                    </button>
                </div>
            @endforelse
        </section>
    </main>
</div>

{{-- Modal Tambah Mata Kuliah --}}
<div id="modalTambahMk" class="modal-backdrop">
    <div class="modal-window" style="max-width:650px;">
        <div class="modal-header-blue">
            <h3>Tambah Mata Kuliah - {{ $prodi->nama }}</h3>
        </div>
        <div class="modal-body" style="padding:20px;">
            <div style="background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; padding:10px 14px; border-radius:6px; font-size:12.5px; margin-bottom:16px;">
                ℹ️ Mata kuliah ini akan disimpan untuk <strong>Program Studi {{ $prodi->nama }}</strong> (Jurusan: {{ $prodi->jurusan->nama ?? '-' }}).
            </div>

            <form action="{{ route('admin.mata-kuliah.prodi.store', ['prodi' => $prodi->id]) }}" method="POST" class="modal-form">
                @csrf
                <div class="modal-form-row">
                    <label for="mkKode">Kode MK *</label>
                    <div class="modal-input-container">
                        <input type="text" id="mkKode" name="kode_mk" placeholder="Contoh: TIF101" required>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="mkNama">Nama Mata Kuliah *</label>
                    <div class="modal-input-container">
                        <input type="text" id="mkNama" name="nama_mk" placeholder="Contoh: Algoritma dan Pemrograman" required>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div class="modal-form-row">
                        <label for="mkSks">Bobot SKS *</label>
                        <div class="modal-input-container">
                            <input type="number" id="mkSks" name="sks" min="1" max="6" value="3" required>
                        </div>
                    </div>

                    <div class="modal-form-row">
                        <label for="mkSemester">Semester *</label>
                        <div class="modal-input-container">
                            <select id="mkSemester" name="semester" required>
                                @for($s=1; $s<=8; $s++)
                                    <option value="{{ $s }}">Semester {{ $s }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div class="modal-form-row">
                        <label for="mkTipe">Tipe Mata Kuliah *</label>
                        <div class="modal-input-container">
                            <select id="mkTipe" name="tipe" required>
                                <option value="Wajib">Wajib</option>
                                <option value="Pilihan">Pilihan</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-form-row">
                        <label for="mkJumlahKelas">Jumlah Kelas/Rombel *</label>
                        <div class="modal-input-container">
                            <input type="number" id="mkJumlahKelas" name="jumlah_kelas" min="1" max="10" value="1" required>
                        </div>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="mkKapasitas">Kapasitas per Kelas *</label>
                    <div class="modal-input-container">
                        <input type="number" id="mkKapasitas" name="kapasitas_per_kelas" min="1" max="200" value="35" required>
                    </div>
                </div>

                {{-- Catatan Dosen: Dosen difilter KHUSUS prodi ini (tidak digabung) --}}
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:12px; margin-bottom:14px;">
                    <div style="font-size:12px; font-weight:700; color:#475569; margin-bottom:8px;">
                        👨‍🏫 Dosen Pengampu (Data Dosen Khusus {{ $prodi->nama }}):
                    </div>

                    <div class="modal-form-row" style="margin-bottom:10px;">
                        <label for="mkKetua">Dosen Ketua (Koordinator)</label>
                        <div class="modal-input-container">
                            <select id="mkKetua" name="dosen_ketua_id">
                                <option value="">-- Pilih Dosen Ketua (Opsional) --</option>
                                @foreach($dosens as $d)
                                    <option value="{{ $d->id }}">{{ $d->nama }} ({{ $d->inisial ?? 'Dosen' }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="modal-form-row" style="margin-bottom:0;">
                        <label for="mkAnggota">Dosen Anggota (Tim Pengajar)</label>
                        <div class="modal-input-container">
                            <select id="mkAnggota" name="dosen_anggota_id">
                                <option value="">-- Pilih Dosen Anggota (Opsional) --</option>
                                @foreach($dosens as $d)
                                    <option value="{{ $d->id }}">{{ $d->nama }} ({{ $d->inisial ?? 'Dosen' }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-form-row" style="align-items:center;">
                    <label style="cursor:pointer; display:inline-flex; align-items:center; gap:8px;">
                        <input type="checkbox" name="butuh_lab" value="1" style="width:16px; height:16px; accent-color:#0860be;">
                        <span style="font-weight:600; color:#1e293b; font-size:13px;">Mata Kuliah Praktikum / Membutuhkan Laboratorium</span>
                    </label>
                </div>

                <div class="modal-footer-buttons" style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalTambahMk')">Batal</button>
                    <button type="submit" class="btn-modal-submit" style="background:#0860be; color:#fff; border:none; padding:10px 20px; border-radius:6px; font-weight:600; cursor:pointer;">Simpan Mata Kuliah</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Edit Mata Kuliah --}}
<div id="modalEditMk" class="modal-backdrop">
    <div class="modal-window" style="max-width:650px;">
        <div class="modal-header-blue">
            <h3>Edit Mata Kuliah</h3>
        </div>
        <div class="modal-body" style="padding:20px;">
            <form id="formEditMk" method="POST" class="modal-form">
                @csrf
                @method('PUT')
                <div class="modal-form-row">
                    <label for="editKode">Kode MK *</label>
                    <div class="modal-input-container">
                        <input type="text" id="editKode" name="kode_mk" required>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="editNama">Nama Mata Kuliah *</label>
                    <div class="modal-input-container">
                        <input type="text" id="editNama" name="nama_mk" required>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div class="modal-form-row">
                        <label for="editSks">Bobot SKS *</label>
                        <div class="modal-input-container">
                            <input type="number" id="editSks" name="sks" min="1" max="6" required>
                        </div>
                    </div>

                    <div class="modal-form-row">
                        <label for="editSemester">Semester *</label>
                        <div class="modal-input-container">
                            <select id="editSemester" name="semester" required>
                                @for($s=1; $s<=8; $s++)
                                    <option value="{{ $s }}">Semester {{ $s }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div class="modal-form-row">
                        <label for="editTipe">Tipe Mata Kuliah *</label>
                        <div class="modal-input-container">
                            <select id="editTipe" name="tipe" required>
                                <option value="Wajib">Wajib</option>
                                <option value="Pilihan">Pilihan</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-form-row">
                        <label for="editJumlahKelas">Jumlah Kelas/Rombel *</label>
                        <div class="modal-input-container">
                            <input type="number" id="editJumlahKelas" name="jumlah_kelas" min="1" max="10" required>
                        </div>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="editKapasitas">Kapasitas per Kelas *</label>
                    <div class="modal-input-container">
                        <input type="number" id="editKapasitas" name="kapasitas_per_kelas" min="1" max="200" required>
                    </div>
                </div>

                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:12px; margin-bottom:14px;">
                    <div style="font-size:12px; font-weight:700; color:#475569; margin-bottom:8px;">
                        👨‍🏫 Dosen Pengampu (Data Dosen Khusus {{ $prodi->nama }}):
                    </div>

                    <div class="modal-form-row" style="margin-bottom:10px;">
                        <label for="editKetua">Dosen Ketua (Koordinator)</label>
                        <div class="modal-input-container">
                            <select id="editKetua" name="dosen_ketua_id">
                                <option value="">-- Tidak ada --</option>
                                @foreach($dosens as $d)
                                    <option value="{{ $d->id }}">{{ $d->nama }} ({{ $d->inisial ?? 'Dosen' }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="modal-form-row" style="margin-bottom:0;">
                        <label for="editAnggota">Dosen Anggota (Tim Pengajar)</label>
                        <div class="modal-input-container">
                            <select id="editAnggota" name="dosen_anggota_id">
                                <option value="">-- Tidak ada --</option>
                                @foreach($dosens as $d)
                                    <option value="{{ $d->id }}">{{ $d->nama }} ({{ $d->inisial ?? 'Dosen' }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-form-row" style="align-items:center;">
                    <label style="cursor:pointer; display:inline-flex; align-items:center; gap:8px;">
                        <input type="checkbox" id="editLab" name="butuh_lab" value="1" style="width:16px; height:16px; accent-color:#0860be;">
                        <span style="font-weight:600; color:#1e293b; font-size:13px;">Mata Kuliah Praktikum / Membutuhkan Laboratorium</span>
                    </label>
                </div>

                <div class="modal-footer-buttons" style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalEditMk')">Batal</button>
                    <button type="submit" class="btn-modal-submit" style="background:#0860be; color:#fff; border:none; padding:10px 20px; border-radius:6px; font-weight:600; cursor:pointer;">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openModal(id) {
    const el = document.getElementById(id);
    if(el) el.classList.add('show');
}

function closeModal(id) {
    const el = document.getElementById(id);
    if(el) el.classList.remove('show');
}

function editMk(mk) {
    const form = document.getElementById('formEditMk');
    form.action = `/admin/mata-kuliah/prodi/{{ $prodi->id }}/${mk.id}`;

    document.getElementById('editKode').value = mk.kode_mk || '';
    document.getElementById('editNama').value = mk.nama_mk || '';
    document.getElementById('editSks').value = mk.sks || 3;
    document.getElementById('editSemester').value = mk.semester || 1;
    document.getElementById('editTipe').value = mk.tipe || 'Wajib';
    document.getElementById('editJumlahKelas').value = mk.jumlah_kelas || 1;
    document.getElementById('editKapasitas').value = mk.kapasitas_per_kelas || 35;
    document.getElementById('editKetua').value = mk.dosen_ketua_id || '';
    document.getElementById('editAnggota').value = mk.dosen_anggota_id || '';
    document.getElementById('editLab').checked = Boolean(mk.butuh_lab);

    openModal('modalEditMk');
}
</script>
@endsection

