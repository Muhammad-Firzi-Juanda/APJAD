@extends('layouts.app')

@section('title', 'APJAD - Evaluasi & Catatan Jurusan')

@section('content')
<div class="admin-layout">
    @include('jurusan.sidebar', ['active' => 'evaluasi'])

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

            <div style="margin-bottom: 24px; display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
                <div>
                    <h1 class="page-title" style="margin-bottom: 4px;">Evaluasi & Catatan ke Admin Program Studi</h1>
                    <p style="color: #64748b; font-size: 0.95rem; margin: 0;">
                        Kirim rekomendasi dan pantau tindak lanjut perbaikan jadwal oleh Admin Program Studi di bawah Jurusan {{ $jurusan->nama }}.
                    </p>
                </div>
                <div>
                    <button type="button" class="btn-primary" onclick="openModal('modalCatatanBaru')" style="display:inline-flex; align-items:center; gap:8px; padding:9px 16px; font-weight:600; border-radius:8px; font-size:0.9rem; border:none; cursor:pointer;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Buat Catatan Evaluasi Baru</span>
                    </button>
                </div>
            </div>

            {{-- Filter Toolbar --}}
            <div class="table-card" style="padding:16px; margin-bottom:24px; border-radius:10px; background:#ffffff;">
                <form action="{{ route('jurusan.evaluasi') }}" method="GET" style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
                    <div style="flex:1; min-width:180px;">
                        <label style="display:block; font-size:0.8rem; font-weight:700; color:#475569; margin-bottom:4px;">Target Prodi</label>
                        <select name="prodi_id" style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1; padding:0 10px;">
                            <option value="all">Semua Program Studi</option>
                            @foreach($prodis as $p)
                                <option value="{{ $p->id }}" {{ request('prodi_id') == $p->id ? 'selected' : '' }}>{{ $p->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="flex:1; min-width:180px;">
                        <label style="display:block; font-size:0.8rem; font-weight:700; color:#475569; margin-bottom:4px;">Kategori Masalah</label>
                        <select name="kategori" style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1; padding:0 10px;">
                            <option value="all">Semua Kategori</option>
                            @foreach($listKategori as $kat)
                                <option value="{{ $kat }}" {{ request('kategori') === $kat ? 'selected' : '' }}>{{ $kat }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="flex:1; min-width:160px;">
                        <label style="display:block; font-size:0.8rem; font-weight:700; color:#475569; margin-bottom:4px;">Status Tindak Lanjut</label>
                        <select name="status" style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1; padding:0 10px;">
                            <option value="all">Semua Status</option>
                            @foreach($listStatus as $st)
                                <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display:flex; gap:8px;">
                        <button type="submit" class="btn-primary" style="height:38px; padding:0 16px; border-radius:6px; font-weight:600;">
                            Filter
                        </button>
                        <a href="{{ route('jurusan.evaluasi') }}" style="display:inline-flex; align-items:center; height:38px; padding:0 14px; border-radius:6px; font-weight:600; font-size:0.88rem; background:#f1f5f9; color:#475569; text-decoration:none; border:1px solid #cbd5e1;">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            {{-- Tabel Daftar Catatan Evaluasi --}}
            <div class="table-card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width:50px;">No</th>
                                <th style="width:130px;">Waktu & Pengirim</th>
                                <th>Target Prodi</th>
                                <th>Kategori</th>
                                <th>Judul & Rekomendasi Jurusan</th>
                                <th>Jadwal Terkait</th>
                                <th style="text-align:center; width:130px;">Status</th>
                                <th style="width:240px;">Tanggapan Admin Prodi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($catatanList as $i => $c)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td style="font-size:0.82rem; color:#64748b; white-space:nowrap;">
                                        <strong>{{ $c->created_at->format('d/m/Y') }}</strong><br>
                                        {{ $c->created_at->format('H:i') }} WIB<br>
                                        <span style="font-size:0.75rem; color:#94a3b8;">Oleh: {{ $c->pengirim->nama ?? 'Admin Jurusan' }}</span>
                                    </td>
                                    <td style="font-weight:700; color:#0f172a; white-space:nowrap;">
                                        {{ $c->prodi->nama ?? '-' }}
                                    </td>
                                    <td>
                                        <span style="display:inline-block; font-size:0.75rem; padding:3px 8px; border-radius:6px; background:#f8fafc; color:#1e293b; font-weight:700; border:1px solid #cbd5e1;">
                                            {{ $c->kategori }}
                                        </span>
                                    </td>
                                    <td>
                                        <h4 style="margin:0 0 4px; font-size:0.95rem; color:#0f172a; font-weight:700;">{{ $c->judul }}</h4>
                                        <p style="margin:0; font-size:0.875rem; color:#475569; line-height:1.45;">
                                            {{ $c->catatan_rekomendasi }}
                                        </p>
                                    </td>
                                    <td style="font-size:0.82rem;">
                                        @if($c->jadwal)
                                            <div style="background:#f8fafc; padding:6px 8px; border-radius:6px; border:1px solid #e2e8f0;">
                                                <strong>{{ $c->jadwal->rombel->mataKuliah->nama_mk ?? '-' }}</strong> ({{ $c->jadwal->kelas_label }})<br>
                                                <span style="color:#0860be; font-weight:600;">{{ $c->jadwal->hari }}, {{ substr($c->jadwal->jam_mulai, 0, 5) }}-{{ substr($c->jadwal->jam_selesai, 0, 5) }}</span><br>
                                                <span style="color:#64748b;">Dosen: {{ $c->jadwal->dosen->nama ?? '-' }}</span>
                                            </div>
                                        @else
                                            <span style="color:#94a3b8; font-style:italic;">Evaluasi Umum Prodi</span>
                                        @endif
                                    </td>
                                    <td style="text-align:center;">
                                        @php
                                            $statusColors = [
                                                'Menunggu tanggapan' => 'background:#fef3c7; color:#b45309; border:1px solid #fde68a;',
                                                'Sedang ditinjau' => 'background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd;',
                                                'Ditanggapi' => 'background:#f3e8ff; color:#7e22ce; border:1px solid #e9d5ff;',
                                                'Selesai' => 'background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;',
                                            ];
                                        @endphp
                                        <span style="display:inline-block; font-size:0.75rem; padding:4px 10px; border-radius:12px; font-weight:700; white-space:nowrap; {{ $statusColors[$c->status] ?? '' }}">
                                            {{ $c->status }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($c->tanggapan_prodi)
                                            <div style="background:#f0fdf4; border:1px solid #bbf7d0; padding:8px 10px; border-radius:6px; font-size:0.85rem; color:#166534;">
                                                <p style="margin:0 0 4px; font-weight:600;">{{ $c->tanggapan_prodi }}</p>
                                                <span style="font-size:0.72rem; color:#15803d;">
                                                    Oleh: {{ $c->penanggap->nama ?? 'Admin Prodi' }} ({{ $c->ditanggapi_pada ? $c->ditanggapi_pada->format('d/m/Y H:i') : '-' }})
                                                </span>
                                            </div>
                                        @else
                                            <span style="color:#94a3b8; font-style:italic; font-size:0.85rem;">Belum ada tanggapan</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" style="text-align:center; padding:36px; color:#64748b;">
                                        Belum ada catatan evaluasi yang dibuat.
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

{{-- MODAL: BUAT CATATAN EVALUASI BARU --}}
<div class="modal-backdrop" id="modalCatatanBaru">
    <div class="modal-window" style="max-width:580px;">
        <div class="modal-header-blue">
            <h3>Buat Catatan & Rekomendasi Evaluasi ke Prodi</h3>
        </div>
        <div class="modal-body" style="padding:20px;">
            <form action="{{ route('jurusan.evaluasi.store') }}" method="POST" class="modal-form">
                @csrf
                <div class="modal-form-row">
                    <label for="prodi_id">Program Studi Tujuan <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <select name="prodi_id" id="prodi_id" required style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1; padding:0 10px;">
                            <option value="">-- Pilih Program Studi --</option>
                            @foreach($prodis as $p)
                                <option value="{{ $p->id }}">{{ $p->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="jadwal_id">Terkait Jadwal Tertentu (Opsional)</label>
                    <div class="modal-input-container">
                        <select name="jadwal_id" id="jadwal_id" style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1; padding:0 10px;">
                            <option value="">-- Tidak terkait jadwal khusus / Catatan Umum --</option>
                            @foreach($jadwalOptions as $jo)
                                <option value="{{ $jo->id }}">
                                    [{{ $jo->rombel->mataKuliah->prodi ?? 'Prodi' }}] {{ $jo->rombel->mataKuliah->nama_mk ?? 'MK' }} ({{ $jo->kelas_label }}) - {{ $jo->hari }} {{ substr($jo->jam_mulai,0,5) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="kategori">Kategori Catatan <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <select name="kategori" id="kategori" required style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1; padding:0 10px;">
                            @foreach($listKategori as $kat)
                                <option value="{{ $kat }}">{{ $kat }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="judul">Judul Catatan <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <input type="text" name="judul" id="judul" placeholder="Contoh: Penyesuaian Ruang Kuliah Kelas Paralel" required style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1; padding:0 12px;">
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="catatan_rekomendasi">Isi Catatan & Rekomendasi Perbaikan <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <textarea name="catatan_rekomendasi" id="catatan_rekomendasi" rows="4" placeholder="Uraikan temuan hasil monitoring dan rekomendasi perbaikan untuk Admin Prodi..." required style="width:100%; border-radius:6px; border:1px solid #cbd5e1; padding:10px; font-family:inherit; font-size:0.88rem;"></textarea>
                    </div>
                </div>

                <div class="modal-footer-buttons" style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalCatatanBaru')">Batal</button>
                    <button type="submit" class="btn-modal-submit" style="background:#0860be; color:#fff; border:none; padding:9px 18px; border-radius:6px; font-weight:600; cursor:pointer;">
                        Kirim Catatan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
</script>
@endsection

