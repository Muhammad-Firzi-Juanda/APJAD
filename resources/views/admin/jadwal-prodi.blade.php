@extends('layouts.app')

@section('title', 'APJAD - Jadwal Kuliah ' . $prodi->nama)

@section('content')
<div class="admin-layout">
    @include('admin.sidebar', ['active' => 'jadwal-prodi-' . $prodi->id])

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

            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px; flex-wrap:wrap; gap:16px;">
                <div>
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px; flex-wrap:wrap;">
                        <span style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; font-size:11.5px; font-weight:700; padding:2px 8px; border-radius:12px;">
                            PRODI: {{ $prodi->kode }}
                        </span>
                        <span style="background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; font-size:11.5px; font-weight:600; padding:2px 8px; border-radius:12px;">
                            Jurusan: {{ $prodi->jurusan->nama ?? '-' }}
                        </span>
                    </div>
                    <h1 class="page-title" style="margin-bottom:4px;">Jadwal Kuliah {{ $prodi->nama }}</h1>
                    <p style="color:#64748b; font-size:0.95rem; margin:0;">
                        Monitoring seluruh alokasi jadwal kuliah, rombel, ruangan, dan dosen pengampu aktif pada Program Studi {{ $prodi->nama }}.
                    </p>
                </div>

                <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                    <div style="display:flex; align-items:center; gap:6px;">
                        <label for="prodiSwitcher" style="font-size:13px; font-weight:600; color:#475569;">Pilih Prodi:</label>
                        <select id="prodiSwitcher" onchange="if(this.value) window.location.href=this.value;" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; font-weight:600; color:#1e293b; background:#fff; cursor:pointer;">
                            @foreach($allProdis as $ap)
                                <option value="{{ route('admin.jadwal.prodi', ['prodi' => $ap->id]) }}" {{ $ap->id === $prodi->id ? 'selected' : '' }}>
                                    {{ $ap->nama }} ({{ $ap->kode }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <a href="{{ route('penjadwalan.index') }}" style="display:inline-flex; align-items:center; gap:6px; background:#0860be; color:#fff; padding:9px 16px; border-radius:6px; font-weight:600; font-size:13px; text-decoration:none;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                        <span>Penjadwalan Otomatis</span>
                    </a>
                </div>
            </div>

            {{-- 3 KPI Cards --}}
            <div class="stats-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:24px;">
                <div class="stat-card">
                    <div class="stat-icon jadwal-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Total Jadwal Terplot</p>
                        <p class="stat-value">{{ $jadwals->count() }}</p>
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
                        <p class="stat-label">Dosen Mengajar</p>
                        <p class="stat-value">{{ $jadwals->pluck('dosen_id')->unique()->count() }}</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon ruangan-icon" style="background:#eff6ff; color:#2563eb;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-label">Ruangan Terpakai</p>
                        <p class="stat-value">{{ $jadwals->pluck('ruangan_id')->unique()->count() }}</p>
                    </div>
                </div>
            </div>

            {{-- Jadwal Per Hari --}}
            @foreach($hariList as $hari)
                @php
                    $listJadwalHari = $perHari->get($hari, collect());
                @endphp
                <div class="table-card" style="margin-bottom:24px; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; background:#fff;">
                    <div style="background:#f8fafc; border-bottom:1px solid #e2e8f0; padding:12px 18px; display:flex; justify-content:space-between; align-items:center;">
                        <h3 style="margin:0; font-size:15px; font-weight:700; color:#0f172a;">Hari {{ $hari }}</h3>
                        <span style="font-size:12px; color:#64748b; font-weight:600;">
                            {{ $listJadwalHari->count() }} Sesi Kuliah
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table" style="width:100%; border-collapse:collapse;">
                            <thead>
                                <tr style="background:#f1f5f9; text-align:left; font-size:12px; color:#475569;">
                                    <th style="padding:10px 14px; width:130px;">Jam Kuliah</th>
                                    <th style="padding:10px 14px;">Mata Kuliah</th>
                                    <th style="padding:10px 14px; text-align:center; width:80px;">SKS</th>
                                    <th style="padding:10px 14px; text-align:center; width:90px;">Kelas</th>
                                    <th style="padding:10px 14px;">Dosen Pengampu</th>
                                    <th style="padding:10px 14px; text-align:center; width:120px;">Ruangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($listJadwalHari as $j)
                                    <tr style="border-bottom:1px solid #f1f5f9; font-size:13px;">
                                        <td style="padding:10px 14px; font-weight:700; color:#0860be;">
                                            {{ substr($j->jam_mulai, 0, 5) }} - {{ substr($j->jam_selesai, 0, 5) }}
                                        </td>
                                        <td style="padding:10px 14px;">
                                            <span style="font-weight:700; color:#0f172a;">{{ $j->rombel->mataKuliah->nama_mk ?? '-' }}</span>
                                            <span style="font-size:11px; color:#64748b; margin-left:6px;">({{ $j->rombel->mataKuliah->kode_mk ?? '-' }})</span>
                                        </td>
                                        <td style="padding:10px 14px; text-align:center;">
                                            <span style="background:#f1f5f9; padding:2px 8px; border-radius:12px; font-weight:600; font-size:12px;">
                                                {{ $j->rombel->mataKuliah->sks ?? '-' }} SKS
                                            </span>
                                        </td>
                                        <td style="padding:10px 14px; text-align:center; font-weight:700; color:#0369a1;">
                                            {{ $j->rombel->kode_rombel ?? '-' }}
                                        </td>
                                        <td style="padding:10px 14px; color:#334155;">
                                            {{ $j->dosen->nama ?? '-' }}
                                        </td>
                                        <td style="padding:10px 14px; text-align:center;">
                                            <span style="background:#ecfdf5; color:#047857; font-weight:700; padding:3px 10px; border-radius:6px; font-size:12px;">
                                                {{ $j->ruangan->kode ?? '-' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="padding:20px; text-align:center; color:#94a3b8; font-style:italic;">
                                            Tidak ada jadwal kuliah pada hari {{ $hari }}.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </section>
    </main>
</div>
@endsection
