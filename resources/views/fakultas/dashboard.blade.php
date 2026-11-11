@extends('layouts.app')

@section('title', 'APJAD - Dashboard Fakultas')

@section('content')
<div class="admin-layout">
    @include('fakultas.sidebar', ['active' => 'dashboard'])

    <main class="admin-main">
        <header class="admin-header">
            <div class="user-badge">
                <span>{{ Auth::user()->role }}, <span class="user-name">{{ Auth::user()->nama }}</span> ({{ $faculty->kode ?? 'FTTK' }})</span>
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
                <div class="flash-alert error" style="background:#fee2e2; color:#b91c1c; border-left:4px solid #ef4444; padding:12px 16px; border-radius:6px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
                    <span>{{ session('flash_error') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:#b91c1c;">✕</button>
                </div>
            @endif

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
                <div>
                    <h1 class="page-title" style="margin-bottom:4px;">Dashboard Fakultas</h1>
                    <p style="color:#64748b; font-size:14px; margin:0;">
                        {{ $faculty->nama ?? 'Fakultas Teknik dan Teknologi Kemaritiman' }} ({{ $faculty->kode ?? 'FTTK' }}) — Pengelolaan MKWU, MKWF, Ruangan, Laboratorium, dan Penjadwalan Kuliah Bebas Bentrok
                    </p>
                </div>
                <div style="display:flex; gap:10px; align-items:center;">
                    <a href="{{ route('fakultas.penjadwalan') }}" style="display:inline-flex; align-items:center; gap:8px; background:#0860be; color:#fff; padding:10px 18px; border-radius:8px; text-decoration:none; font-weight:600; font-size:13px; box-shadow:0 2px 6px rgba(8,96,190,0.25);">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        <span>Penjadwalan Kuliah</span>
                    </a>
                </div>
            </div>

            {{-- 6 KPI Cards dengan Ikon Siluet Minimalis --}}
            <div class="stats-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap:16px; margin-bottom:28px;">
                <a href="{{ route('fakultas.mkwu') }}" class="stat-card" style="text-decoration:none; color:inherit;">
                    <div class="stat-icon mkwu-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Total MKWU</div>
                        <div class="stat-value">{{ $stats['total_mkwu'] }}</div>
                    </div>
                </a>

                <a href="{{ route('fakultas.mkwf') }}" class="stat-card" style="text-decoration:none; color:inherit;">
                    <div class="stat-icon mkwf-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Total MKWF</div>
                        <div class="stat-value">{{ $stats['total_mkwf'] }}</div>
                    </div>
                </a>

                <a href="{{ route('fakultas.rooms.index') }}" class="stat-card" style="text-decoration:none; color:inherit;">
                    <div class="stat-icon ruangan-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Total Ruangan</div>
                        <div class="stat-value">{{ $stats['total_rooms'] }}</div>
                    </div>
                </a>

                <a href="{{ route('fakultas.laboratories.index') }}" class="stat-card" style="text-decoration:none; color:inherit;">
                    <div class="stat-icon lab-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10 2v7.31"></path>
                            <path d="M14 9.3V1.99"></path>
                            <path d="M8.5 2h7"></path>
                            <path d="M14 9.3a6.5 6.5 0 1 1-4 0"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Total Laboratorium</div>
                        <div class="stat-value">{{ $stats['total_laboratories'] }}</div>
                    </div>
                </a>

                <a href="{{ route('fakultas.allocations.rooms') }}" class="stat-card" style="text-decoration:none; color:inherit;">
                    <div class="stat-icon prodi-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="16 3 21 3 21 8"></polyline>
                            <line x1="4" y1="20" x2="21" y2="3"></line>
                            <polyline points="21 16 21 21 16 21"></polyline>
                            <line x1="15" y1="15" x2="21" y2="21"></line>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Alokasi Ruangan</div>
                        <div class="stat-value">{{ $stats['total_room_allocations'] }}</div>
                    </div>
                </a>

                <a href="{{ route('fakultas.allocations.laboratories') }}" class="stat-card" style="text-decoration:none; color:inherit;">
                    <div class="stat-icon fakultas-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="18" cy="5" r="3"></circle>
                            <circle cx="6" cy="12" r="3"></circle>
                            <circle cx="18" cy="19" r="3"></circle>
                            <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                            <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Alokasi Laboratorium</div>
                        <div class="stat-value">{{ $stats['total_lab_allocations'] }}</div>
                    </div>
                </a>
            </div>

            {{-- Ringkasan Mata Kuliah --}}
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:28px;">
                {{-- Preview MKWU --}}
                <div class="card" style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                        <h3 style="margin:0; font-size:16px; font-weight:600;">Mata Kuliah Wajib Universitas (MKWU)</h3>
                        <a href="{{ route('fakultas.mkwu') }}" style="font-size:13px; color:#2563eb; text-decoration:none; font-weight:500;">Kelola MKWU →</a>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table" style="width:100%; font-size:13px;">
                            <thead>
                                <tr style="background:#f8fafc; text-align:left;">
                                    <th style="padding:10px;">Kode</th>
                                    <th style="padding:10px;">Nama</th>
                                    <th style="padding:10px;">SKS</th>
                                    <th style="padding:10px;">Sem</th>
                                    <th style="padding:10px;">Jenis</th>
                                    <th style="padding:10px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentMkwu as $mk)
                                    <tr style="border-top:1px solid #f1f5f9;">
                                        <td style="padding:10px; font-weight:600;">{{ $mk->code }}</td>
                                        <td style="padding:10px;">{{ $mk->name }}</td>
                                        <td style="padding:10px;">{{ $mk->sks }}</td>
                                        <td style="padding:10px;">{{ $mk->semester }}</td>
                                        <td style="padding:10px;">
                                            <span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:600; background:{{ $mk->type === 'THEORY' ? '#e0e7ff; color:#3730a3' : '#fef3c7; color:#92400e' }};">
                                                {{ $mk->type === 'THEORY' ? 'Teori' : 'Praktikum' }}
                                            </span>
                                        </td>
                                        <td style="padding:10px;">
                                            <span style="display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; background:{{ $mk->status === 'ACTIVE' ? '#dcfce7; color:#15803d' : '#fee2e2; color:#b91c1c' }};">
                                                {{ $mk->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="padding:16px; text-align:center; color:#94a3b8;">Belum ada MKWU.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Preview MKWF --}}
                <div class="card" style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                        <h3 style="margin:0; font-size:16px; font-weight:600;">Mata Kuliah Wajib Fakultas (MKWF)</h3>
                        <a href="{{ route('fakultas.mkwf') }}" style="font-size:13px; color:#2563eb; text-decoration:none; font-weight:500;">Kelola MKWF →</a>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table" style="width:100%; font-size:13px;">
                            <thead>
                                <tr style="background:#f8fafc; text-align:left;">
                                    <th style="padding:10px;">Kode</th>
                                    <th style="padding:10px;">Nama</th>
                                    <th style="padding:10px;">SKS</th>
                                    <th style="padding:10px;">Sem</th>
                                    <th style="padding:10px;">Jenis</th>
                                    <th style="padding:10px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentMkwf as $mk)
                                    <tr style="border-top:1px solid #f1f5f9;">
                                        <td style="padding:10px; font-weight:600;">{{ $mk->code }}</td>
                                        <td style="padding:10px;">{{ $mk->name }}</td>
                                        <td style="padding:10px;">{{ $mk->sks }}</td>
                                        <td style="padding:10px;">{{ $mk->semester }}</td>
                                        <td style="padding:10px;">
                                            <span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:600; background:{{ $mk->type === 'THEORY' ? '#e0e7ff; color:#3730a3' : '#fef3c7; color:#92400e' }};">
                                                {{ $mk->type === 'THEORY' ? 'Teori' : 'Praktikum' }}
                                            </span>
                                        </td>
                                        <td style="padding:10px;">
                                            <span style="display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; background:{{ $mk->status === 'ACTIVE' ? '#dcfce7; color:#15803d' : '#fee2e2; color:#b91c1c' }};">
                                                {{ $mk->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="padding:16px; text-align:center; color:#94a3b8;">Belum ada MKWF.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Ringkasan Alokasi Resource Fakultas --}}
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:28px;">
                {{-- Ringkasan Ruangan --}}
                <div class="card" style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                        <h3 style="margin:0; font-size:16px; font-weight:600;">Alokasi Ruangan ke Program Studi</h3>
                        <a href="{{ route('fakultas.allocations.rooms') }}" style="font-size:13px; color:#0284c7; text-decoration:none; font-weight:500;">Kelola Alokasi Ruangan →</a>
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(190px, 1fr)); gap:10px;">
                        @foreach($rooms->take(6) as $r)
                            <div style="border:1px solid #e2e8f0; border-radius:8px; padding:10px; background:#f8fafc;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                                    <strong style="font-size:12.5px; color:#0f172a;">{{ $r->name }}</strong>
                                    <span style="font-size:11px; color:#64748b;">{{ $r->capacity }} kursi</span>
                                </div>
                                <div style="display:flex; flex-wrap:wrap; gap:4px;">
                                    @forelse($r->studyPrograms as $sp)
                                        <span style="font-size:10.5px; padding:1px 6px; border-radius:4px; background:#e0f2fe; color:#0369a1; font-weight:600;">{{ $sp->kode }}</span>
                                    @empty
                                        <span style="font-size:11px; color:#94a3b8; font-style:italic;">Belum dialokasi</span>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Ringkasan Laboratorium --}}
                <div class="card" style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                        <h3 style="margin:0; font-size:16px; font-weight:600;">Alokasi Laboratorium ke Program Studi</h3>
                        <a href="{{ route('fakultas.allocations.laboratories') }}" style="font-size:13px; color:#9333ea; text-decoration:none; font-weight:500;">Kelola Alokasi Lab →</a>
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(190px, 1fr)); gap:10px;">
                        @foreach($laboratories->take(6) as $l)
                            <div style="border:1px solid #e2e8f0; border-radius:8px; padding:10px; background:#f8fafc;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                                    <strong style="font-size:12.5px; color:#0f172a;">{{ $l->name }}</strong>
                                    <span style="font-size:11px; color:#64748b;">{{ $l->capacity }} PC</span>
                                </div>
                                <div style="display:flex; flex-wrap:wrap; gap:4px;">
                                    @forelse($l->studyPrograms as $sp)
                                        <span style="font-size:10.5px; padding:1px 6px; border-radius:4px; background:#f3e8ff; color:#7e22ce; font-weight:600;">{{ $sp->kode }}</span>
                                    @empty
                                        <span style="font-size:11px; color:#94a3b8; font-style:italic;">Belum dialokasi</span>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Rekap Sinkronisasi Jadwal (Untuk menjaga kompatibilitas test SynchronizedRoleScheduleTest) --}}
            @if(isset($jadwals) && $jadwals->count() > 0)
                <div class="card" style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin-top:20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                        <h3 style="margin:0; font-size:16px; font-weight:600;">Monitoring Rekap Jadwal Terjadwal</h3>
                        <a href="{{ route('fakultas.penjadwalan') }}" style="font-size:13px; color:#2563eb; text-decoration:none; font-weight:600;">Buka Penjadwalan Kuliah →</a>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table" style="width:100%; font-size:13px;">
                            <thead>
                                <tr style="background:#f8fafc; text-align:left;">
                                    <th style="padding:10px;">Hari</th>
                                    <th style="padding:10px;">Waktu</th>
                                    <th style="padding:10px;">Kode MK</th>
                                    <th style="padding:10px;">Mata Kuliah</th>
                                    <th style="padding:10px;">Dosen</th>
                                    <th style="padding:10px;">Ruangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($jadwals as $hari => $items)
                                    @foreach($items as $j)
                                        <tr style="border-top:1px solid #f1f5f9;">
                                            <td style="padding:10px; font-weight:600;">{{ $j->hari }}</td>
                                            <td style="padding:10px;">{{ substr($j->jam_mulai, 0, 5) }} - {{ substr($j->jam_selesai, 0, 5) }}</td>
                                            <td style="padding:10px;">{{ $j->clean_kode_mk ?? ($j->rombel->mataKuliah->clean_kode_mk ?? '') }}</td>
                                            <td style="padding:10px;">{{ $j->rombel->mataKuliah->nama_mk ?? '-' }}</td>
                                            <td style="padding:10px;">{{ $j->dosen_inisial ?? ($j->dosen->nama ?? '-') }}</td>
                                            <td style="padding:10px;">{{ $j->ruangan->nama ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </section>
    </main>
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
document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('modal-backdrop')) {
        closeModal(e.target.id);
    }
});
</script>
@endsection
