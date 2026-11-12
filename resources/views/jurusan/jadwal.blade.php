@extends('layouts.app')

@section('title', 'APJAD - Monitoring Jadwal Jurusan')

@section('content')
<div class="admin-layout">
    @include('jurusan.sidebar', ['active' => 'jadwal'])

    <main class="admin-main">
        <header class="admin-header">
            <div class="user-badge">
                <span>{{ Auth::user()->role }}, <span class="user-name">{{ Auth::user()->nama }}</span> ({{ $jurusan->kode ?? 'JURUSAN' }})</span>
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
                <div class="flash-alert error" style="background:#fee2e2; border-color:#fca5a5; color:#991b1b; padding:12px 16px; border-radius:8px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
                    <span>{{ session('flash_error') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;">✕</button>
                </div>
            @endif

            <div style="margin-bottom: 24px;">
                <h1 class="page-title" style="margin-bottom: 4px;">Monitoring Jadwal Kuliah Jurusan</h1>
                <p style="color: #64748b; font-size: 0.95rem; margin: 0;">
                    Pemantauan jadwal terpadu seluruh Program Studi di bawah Jurusan {{ $jurusan->nama }}. Menampilkan status plotting, rincian per rombel, serta deteksi bentrok dosen.
                </p>
            </div>

            {{-- Alert Bentrok Dosen jika ditemukan --}}
            @if (!empty($conflicts))
                <div style="background:#fffbeb; border:1px solid #fde68a; border-left:5px solid #f59e0b; padding:16px 20px; border-radius:8px; margin-bottom:24px;">
                    <div style="display:flex; align-items:flex-start; gap:12px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; margin-top:2px;">
                            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                        <div style="width:100%;">
                            <h4 style="margin:0 0 6px; font-size:1rem; color:#92400e; font-weight:700;">
                                Terdeteksi {{ count($conflicts) }} Indikasi Jadwal Bentrok Dosen
                            </h4>
                            <ul style="margin:0; padding-left:18px; font-size:0.875rem; color:#b45309;">
                                @foreach($conflicts as $c)
                                    <li style="margin-bottom:4px;">
                                        {{ $c['pesan'] }}
                                        <button type="button" 
                                            class="btn-beri-catatan"
                                            style="margin-left:8px; padding:2px 8px; font-size:0.75rem; background:#f59e0b; color:#fff; border:none; border-radius:4px; cursor:pointer;"
                                            onclick="openCatatanModal({{ $c['jadwal_1']->id }}, {{ $c['jadwal_1']->rombel->mataKuliah->prodi_id ?? 1 }}, '{{ $c['jadwal_1']->rombel->mataKuliah->nama_mk ?? 'MK' }} ({{ $c['jadwal_1']->kelas_label }})', '{{ $c['dosen']->nama ?? 'Dosen' }}', '{{ $c['jadwal_1']->hari }}', '{{ substr($c['jadwal_1']->jam_mulai, 0, 5) }}-{{ substr($c['jadwal_1']->jam_selesai, 0, 5) }}', 'Dugaan bentrok dosen', 'Jadwal bentrok dengan kelas di prodi lain: {{ $c['pesan'] }}')">
                                            Beri Catatan ke Prodi
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Toolbar Filter Terpadu --}}
            <div class="table-card" style="padding:18px; margin-bottom:24px; border-radius:10px; background:#ffffff;">
                <form action="{{ route('jurusan.jadwal') }}" method="GET" style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
                    <div style="flex:1; min-width:180px;">
                        <label style="display:block; font-size:0.8rem; font-weight:700; color:#475569; margin-bottom:4px;">Program Studi</label>
                        <select name="prodi_id" class="filter-select" style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1; padding:0 10px;">
                            <option value="">Semua Program Studi</option>
                            @foreach($prodis as $p)
                                <option value="{{ $p->id }}" {{ (string)($filters['prodi_id'] ?? '') === (string)$p->id ? 'selected' : '' }}>
                                    {{ $p->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div style="flex:1; min-width:130px;">
                        <label style="display:block; font-size:0.8rem; font-weight:700; color:#475569; margin-bottom:4px;">Hari</label>
                        <select name="hari" class="filter-select" style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1; padding:0 10px;">
                            <option value="all">Semua Hari</option>
                            @foreach($hariList as $h)
                                <option value="{{ $h }}" {{ ($filters['hari'] ?? '') === $h ? 'selected' : '' }}>{{ $h }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="flex:1; min-width:130px;">
                        <label style="display:block; font-size:0.8rem; font-weight:700; color:#475569; margin-bottom:4px;">Semester</label>
                        <select name="semester" class="filter-select" style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1; padding:0 10px;">
                            <option value="all">Semua Semester</option>
                            @for($s = 1; $s <= 8; $s++)
                                <option value="{{ $s }}" {{ (string)($filters['semester'] ?? '') === (string)$s ? 'selected' : '' }}>Semester {{ $s }}</option>
                            @endfor
                        </select>
                    </div>

                    <div style="flex:1; min-width:180px;">
                        <label style="display:block; font-size:0.8rem; font-weight:700; color:#475569; margin-bottom:4px;">Dosen Pengampu</label>
                        <select name="dosen_id" class="filter-select" style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1; padding:0 10px;">
                            <option value="all">Semua Dosen</option>
                            @foreach($dosens as $d)
                                <option value="{{ $d->id }}" {{ (string)($filters['dosen_id'] ?? '') === (string)$d->id ? 'selected' : '' }}>
                                    {{ $d->nama }} ({{ $d->inisial ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div style="flex:2; min-width:200px;">
                        <label style="display:block; font-size:0.8rem; font-weight:700; color:#475569; margin-bottom:4px;">Pencarian (MK / Kode / Dosen)</label>
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Ketik kode, nama MK, atau nama dosen..." style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1; padding:0 12px; font-size:0.88rem;">
                    </div>

                    <div style="display:flex; gap:8px;">
                        <button type="submit" class="btn-primary" style="height:38px; padding:0 16px; border-radius:6px; font-weight:600; font-size:0.88rem;">
                            Terapkan
                        </button>
                        <a href="{{ route('jurusan.jadwal') }}" style="display:inline-flex; align-items:center; height:38px; padding:0 14px; border-radius:6px; font-weight:600; font-size:0.88rem; background:#f1f5f9; color:#475569; text-decoration:none; border:1px solid #cbd5e1;">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            {{-- Tabel Jadwal per Hari --}}
            @if ($jadwals->isEmpty())
                <div class="table-card" style="text-align:center; padding:48px 20px; border-style:dashed; border-color:#cbd5e1; background:#f8fafc; border-radius:12px;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin:0 auto 12px;">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <h3 style="font-size:1.1rem; color:#475569; margin:0 0 6px;">Tidak Ada Jadwal Ditemukan</h3>
                    <p style="color:#64748b; font-size:0.92rem; margin:0;">
                        Tidak ada data jadwal kuliah yang sesuai dengan filter yang dipilih, atau Program Studi di jurusan ini belum melakukan plotting jadwal.
                    </p>
                </div>
            @else
                @foreach ($hariList as $hari)
                    @if (isset($perHari[$hari]) && $perHari[$hari]->isNotEmpty())
                        <div style="margin-bottom: 28px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                                <h3 style="margin:0; font-size:1.15rem; color:#1e3a5f; display:flex; align-items:center; gap:8px;">
                                    <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#0860be;"></span>
                                    {{ $hari }}
                                    <span style="font-size:0.85rem; font-weight:normal; color:#64748b;">({{ $perHari[$hari]->count() }} kelas)</span>
                                </h3>
                            </div>

                            <div class="table-card">
                                <div class="table-responsive">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th style="width:110px;">Jam</th>
                                                <th>Kode MK</th>
                                                <th>Mata Kuliah</th>
                                                <th style="text-align:center;">Smt</th>
                                                <th style="text-align:center;">SKS</th>
                                                <th style="text-align:center;">Kelas</th>
                                                <th>Dosen Pengampu</th>
                                                <th>Ruangan / Lab</th>
                                                <th>Program Studi</th>
                                                <th style="text-align:center; width:120px;">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($perHari[$hari] as $j)
                                                @php
                                                    $prodiName = $j->rombel->mataKuliah->prodi ?? '-';
                                                    $prodiObj = $prodis->first(function($p) use ($prodiName) {
                                                        return str_contains(strtolower($p->nama), strtolower($prodiName)) || str_contains(strtolower($prodiName), strtolower($p->nama));
                                                    });
                                                    $prodiId = $prodiObj->id ?? ($prodis->first()->id ?? 1);
                                                    $hasCatatan = $j->catatanEvaluasi->isNotEmpty();
                                                @endphp
                                                <tr>
                                                    <td style="white-space:nowrap; font-weight:700; color:#0860be;">
                                                        {{ substr($j->jam_mulai, 0, 5) }} &ndash; {{ substr($j->jam_selesai, 0, 5) }}
                                                    </td>
                                                    <td style="white-space:nowrap;">
                                                        <strong>{{ $j->clean_kode_mk }}</strong>
                                                    </td>
                                                    <td style="font-weight:600; color:#0f172a;">
                                                        {{ $j->rombel->mataKuliah->nama_mk ?? '-' }}
                                                        @if($hasCatatan)
                                                            <span title="{{ $j->catatanEvaluasi->count() }} catatan evaluasi terhubung" style="display:inline-block; font-size:0.7rem; background:#fef3c7; color:#b45309; padding:1px 6px; border-radius:10px; margin-left:4px; border:1px solid #fde68a;">
                                                                Catatan ({{ $j->catatanEvaluasi->count() }})
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td style="text-align:center;">{{ $j->rombel->mataKuliah->semester ?? '-' }}</td>
                                                    <td style="text-align:center;">
                                                        <span style="display:inline-block; padding:2px 8px; background:#e2e8f0; border-radius:12px; font-size:0.8rem; font-weight:600;">
                                                            {{ $j->rombel->mataKuliah->sks ?? '-' }}
                                                        </span>
                                                    </td>
                                                    <td style="text-align:center;">
                                                        <span style="display:inline-block; padding:2px 8px; background:#dbeafe; color:#1e40af; border-radius:12px; font-weight:700; font-size:0.8rem;">
                                                            {{ $j->kelas_label }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span title="{{ $j->dosen->nama ?? '-' }}" style="font-weight:700; color:#1e3a5f; cursor:help; background:#f8fafc; padding:3px 8px; border-radius:6px; font-size:0.85rem; display:inline-block; border:1px solid #cbd5e1;">
                                                            {{ $j->dosen_inisial }}
                                                        </span>
                                                        <span style="font-size:0.82rem; color:#475569; margin-left:4px;">
                                                            {{ $j->dosen->nama ?? '-' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if($j->laboratory_id)
                                                            <span style="display:inline-block; padding:3px 8px; background:#f5f3ff; color:#6d28d9; font-weight:600; border-radius:6px; font-size:0.82rem; border:1px solid #ddd6fe;">
                                                                {{ $j->lokasi }}
                                                            </span>
                                                        @elseif($j->ruangan?->kode)
                                                            <span style="display:inline-block; padding:3px 8px; background:#ecfdf5; color:#047857; font-weight:600; border-radius:6px; font-size:0.82rem; border:1px solid #a7f3d0;">
                                                                {{ $j->ruangan->kode }}
                                                            </span>
                                                        @else
                                                            <span style="color:#94a3b8; font-style:italic;">-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span style="font-size:0.75rem; padding:3px 8px; border-radius:10px; font-weight:600; white-space:nowrap; {{ str_contains(strtolower($prodiName), 'informatika') ? 'background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe;' : 'background:#fef3c7; color:#b45309; border:1px solid #fde68a;' }}">
                                                            {{ $prodiName }}
                                                        </span>
                                                    </td>
                                                    <td style="text-align:center;">
                                                        <button type="button" 
                                                            class="btn-sm" 
                                                            style="padding:4px 10px; font-size:0.78rem; font-weight:600; border-radius:6px; background:#0860be; color:#ffffff; border:none; cursor:pointer; display:inline-flex; align-items:center; gap:4px;"
                                                            onclick="openCatatanModal({{ $j->id }}, {{ $prodiId }}, '{{ addslashes($j->rombel->mataKuliah->nama_mk ?? 'MK') }} ({{ $j->kelas_label }})', '{{ addslashes($j->dosen->nama ?? '-') }}', '{{ $j->hari }}', '{{ substr($j->jam_mulai, 0, 5) }} - {{ substr($j->jam_selesai, 0, 5) }}')">
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                                            </svg>
                                                            <span>Evaluasi</span>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            @endif
        </section>
    </main>
</div>

{{-- MODAL: BUAT CATATAN EVALUASI JADWAL --}}
<div class="modal-backdrop" id="modalCatatanJadwal">
    <div class="modal-window" style="max-width:550px;">
        <div class="modal-header-blue">
            <h3>Beri Catatan Evaluasi Jadwal ke Admin Prodi</h3>
        </div>
        <div class="modal-body" style="padding:20px;">
            <form action="{{ route('jurusan.evaluasi.store') }}" method="POST" class="modal-form">
                @csrf
                <input type="hidden" name="jadwal_id" id="modal_jadwal_id">
                <input type="hidden" name="prodi_id" id="modal_prodi_id">

                {{-- Ringkasan Info Jadwal yang Dipilih --}}
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px 14px; margin-bottom:16px;">
                    <p style="margin:0 0 4px; font-size:0.8rem; font-weight:700; color:#64748b; text-transform:uppercase;">Jadwal yang Dievaluasi:</p>
                    <p style="margin:0; font-size:0.95rem; font-weight:700; color:#0f172a;" id="modal_mk_label"></p>
                    <p style="margin:2px 0 0; font-size:0.85rem; color:#475569;" id="modal_dosen_waktu"></p>
                </div>

                <div class="modal-form-row">
                    <label for="modal_kategori">Kategori Catatan <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <select name="kategori" id="modal_kategori" required style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1; padding:0 10px;">
                            @foreach($listKategori as $kat)
                                <option value="{{ $kat }}">{{ $kat }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="modal_judul">Judul Singkat Catatan <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <input type="text" name="judul" id="modal_judul" placeholder="Contoh: Jam Bentrok dengan Prodi Lain" required style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1; padding:0 12px;">
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="modal_rekomendasi">Alasan Evaluasi & Rekomendasi Perbaikan <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <textarea name="catatan_rekomendasi" id="modal_rekomendasi" rows="4" placeholder="Tuliskan temuan ketidaksesuaian dan rekomendasi perbaikan untuk Admin Prodi..." required style="width:100%; border-radius:6px; border:1px solid #cbd5e1; padding:10px; font-family:inherit; font-size:0.88rem;"></textarea>
                    </div>
                </div>

                <div class="modal-footer-buttons" style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalCatatanJadwal')">Batal</button>
                    <button type="submit" class="btn-modal-submit" style="background:#0860be; color:#fff; border:none; padding:9px 18px; border-radius:6px; font-weight:600; cursor:pointer;">
                        Kirim Catatan ke Prodi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }

function openCatatanModal(jadwalId, prodiId, mkLabel, dosenLabel, hari, waktu, defaultKategori = '', defaultPesan = '') {
    document.getElementById('modal_jadwal_id').value = jadwalId;
    document.getElementById('modal_prodi_id').value = prodiId;
    document.getElementById('modal_mk_label').textContent = mkLabel;
    document.getElementById('modal_dosen_waktu').textContent = 'Dosen: ' + dosenLabel + ' | ' + hari + ', ' + waktu;
    
    if (defaultKategori) {
        document.getElementById('modal_kategori').value = defaultKategori;
    }
    if (defaultPesan) {
        document.getElementById('modal_judul').value = defaultKategori;
        document.getElementById('modal_rekomendasi').value = defaultPesan;
    } else {
        document.getElementById('modal_judul').value = '';
        document.getElementById('modal_rekomendasi').value = '';
    }

    openModal('modalCatatanJadwal');
}
</script>
@endsection

