@extends('layouts.app')

@section('title', 'APJAD - Laporan Rekapitulasi Jurusan')

@section('content')
<style>
@media print {
    .admin-sidebar, .admin-header, .btn-print-toolbar, .flash-alert {
        display: none !important;
    }
    .admin-layout {
        display: block !important;
    }
    .admin-main {
        margin: 0 !important;
        padding: 0 !important;
    }
    .admin-content {
        padding: 0 !important;
    }
    .table-card {
        box-shadow: none !important;
        border: 1px solid #000 !important;
        page-break-inside: avoid;
    }
    .data-table th, .data-table td {
        border: 1px solid #000 !important;
        color: #000 !important;
    }
    body {
        background: #fff !important;
        color: #000 !important;
    }
}
</style>

<div class="admin-layout">
    @include('jurusan.sidebar', ['active' => 'laporan'])

    <main class="admin-main">
        <header class="admin-header">
            <div class="user-badge">
                <span>{{ Auth::user()->role }}, <span class="user-name">{{ Auth::user()->nama }}</span> ({{ $jurusan->kode ?? 'JURUSAN' }})</span>
            </div>
        </header>

        <section class="admin-content">
            <div class="btn-print-toolbar" style="margin-bottom: 24px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h1 class="page-title" style="margin-bottom: 4px;">Laporan & Rekapitulasi Jurusan</h1>
                    <p style="color: #64748b; font-size: 0.95rem; margin: 0;">
                        Laporan resmi rekapitulasi data akademik, jadwal perkuliahan, dan status evaluasi Jurusan {{ $jurusan->nama }}.
                    </p>
                </div>
                <button type="button" class="btn-primary" onclick="window.print()" style="display:inline-flex; align-items:center; gap:8px; padding:9px 18px; font-weight:600; border-radius:8px; font-size:0.9rem; cursor:pointer;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    <span>Cetak / Ekspor Laporan</span>
                </button>
            </div>

            {{-- KOP SURAT LAPORAN RESMI --}}
            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:24px 30px; margin-bottom:24px;">
                <div style="text-align:center; border-bottom:2px solid #1e293b; padding-bottom:14px; margin-bottom:18px;">
                    <h2 style="margin:0; font-size:1.25rem; font-weight:800; color:#0f172a; text-transform:uppercase; letter-spacing:0.5px;">
                        KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI
                    </h2>
                    <h3 style="margin:4px 0 0; font-size:1.1rem; font-weight:700; color:#1e293b; text-transform:uppercase;">
                        UNIVERSITAS MARITIM RAJA ALI HAJI &middot; FAKULTAS TEKNIK DAN TEKNOLOGI KEMARITIMAN
                    </h3>
                    <h4 style="margin:4px 0 0; font-size:1rem; font-weight:700; color:#0860be; text-transform:uppercase;">
                        JURUSAN {{ strtoupper($jurusan->nama) }} ({{ $jurusan->kode }})
                    </h4>
                    <p style="margin:4px 0 0; font-size:0.85rem; color:#64748b;">
                        Laporan Rekapitulasi Penjadwalan Perkuliahan dan Evaluasi Akademik &middot; Dicetak pada: {{ now()->translatedFormat('d F Y, H:i') }} WIB
                    </p>
                </div>

                {{-- Ringkasan Indikator --}}
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:14px; margin-bottom:24px;">
                    <div style="border:1px solid #e2e8f0; border-radius:8px; padding:12px 14px; text-align:center; background:#f8fafc;">
                        <span style="font-size:0.8rem; color:#64748b; font-weight:600;">Program Studi</span>
                        <h3 style="margin:4px 0 0; font-size:1.4rem; color:#0f172a;">{{ $stats['total_prodi'] }}</h3>
                    </div>
                    <div style="border:1px solid #e2e8f0; border-radius:8px; padding:12px 14px; text-align:center; background:#f8fafc;">
                        <span style="font-size:0.8rem; color:#64748b; font-weight:600;">Dosen Jurusan</span>
                        <h3 style="margin:4px 0 0; font-size:1.4rem; color:#0f172a;">{{ $stats['total_dosen'] }}</h3>
                    </div>
                    <div style="border:1px solid #e2e8f0; border-radius:8px; padding:12px 14px; text-align:center; background:#f8fafc;">
                        <span style="font-size:0.8rem; color:#64748b; font-weight:600;">Mata Kuliah</span>
                        <h3 style="margin:4px 0 0; font-size:1.4rem; color:#0f172a;">{{ $stats['total_mata_kuliah'] }}</h3>
                    </div>
                    <div style="border:1px solid #e2e8f0; border-radius:8px; padding:12px 14px; text-align:center; background:#f8fafc;">
                        <span style="font-size:0.8rem; color:#64748b; font-weight:600;">Rombel / Kelas</span>
                        <h3 style="margin:4px 0 0; font-size:1.4rem; color:#0f172a;">{{ $stats['total_rombel'] }}</h3>
                    </div>
                    <div style="border:1px solid #e2e8f0; border-radius:8px; padding:12px 14px; text-align:center; background:#f8fafc;">
                        <span style="font-size:0.8rem; color:#64748b; font-weight:600;">Jadwal Terplot</span>
                        <h3 style="margin:4px 0 0; font-size:1.4rem; color:#0860be;">{{ $stats['total_jadwal'] }}</h3>
                    </div>
                    <div style="border:1px solid #e2e8f0; border-radius:8px; padding:12px 14px; text-align:center; background:#f8fafc;">
                        <span style="font-size:0.8rem; color:#64748b; font-weight:600;">Tindak Lanjut Evaluasi</span>
                        <h3 style="margin:4px 0 0; font-size:1.4rem; color:#10b981;">{{ $catatanSelesai }} / {{ $catatanTotal }}</h3>
                    </div>
                </div>

                {{-- Tabel 1: Rekapitulasi per Program Studi --}}
                <div style="margin-bottom:28px;">
                    <h4 style="font-size:1rem; color:#1e293b; margin:0 0 10px; font-weight:700;">
                        1. Rekapitulasi Kesiapan Penjadwalan per Program Studi
                    </h4>
                    <table class="data-table" style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f1f5f9;">
                                <th style="width:40px; text-align:center;">No</th>
                                <th>Kode</th>
                                <th>Nama Program Studi</th>
                                <th>Ketua Program Studi</th>
                                <th style="text-align:center;">Dosen</th>
                                <th style="text-align:center;">Mata Kuliah</th>
                                <th style="text-align:center;">Rombel</th>
                                <th style="text-align:center;">Jadwal Terplot</th>
                                <th style="text-align:center;">Catatan Pending</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stats['prodi_summaries'] as $idx => $ps)
                                <tr>
                                    <td style="text-align:center;">{{ $idx + 1 }}</td>
                                    <td><strong>{{ $ps['prodi']->kode ?? '-' }}</strong></td>
                                    <td style="font-weight:600;">{{ $ps['prodi']->nama }}</td>
                                    <td>{{ $ps['prodi']->kaprodi ?? '-' }}</td>
                                    <td style="text-align:center;">{{ $ps['dosen_count'] }}</td>
                                    <td style="text-align:center;">{{ $ps['mk_count'] }}</td>
                                    <td style="text-align:center;">{{ $ps['rombel_count'] }}</td>
                                    <td style="text-align:center; font-weight:700; color:#0860be;">{{ $ps['jadwal_count'] }}</td>
                                    <td style="text-align:center; font-weight:600; color:{{ $ps['catatan_pending_count'] > 0 ? '#d97706' : '#10b981' }};">
                                        {{ $ps['catatan_pending_count'] }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" style="text-align:center; padding:18px;">Belum ada data program studi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Tabel 2: Beban Mengajar Dosen Terjadwal di Jurusan --}}
                <div style="margin-bottom:24px;">
                    <h4 style="font-size:1rem; color:#1e293b; margin:0 0 10px; font-weight:700;">
                        2. Rekapitulasi Beban Mengajar Dosen Terjadwal di Jurusan
                    </h4>
                    <table class="data-table" style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f1f5f9;">
                                <th style="width:40px; text-align:center;">No</th>
                                <th style="width:80px; text-align:center;">Inisial</th>
                                <th>Nama Dosen</th>
                                <th>Homebase Prodi</th>
                                <th style="text-align:center;">Jumlah Kelas Terplot</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $dosenClassCounts = $jadwals->whereNotNull('dosen_id')->groupBy('dosen_id');
                            @endphp
                            @forelse($dosens as $idx => $d)
                                @php
                                    $kelasCount = isset($dosenClassCounts[$d->id]) ? $dosenClassCounts[$d->id]->count() : 0;
                                @endphp
                                <tr>
                                    <td style="text-align:center;">{{ $idx + 1 }}</td>
                                    <td style="text-align:center; font-weight:700; color:#0860be;">{{ $d->inisial ?? '-' }}</td>
                                    <td style="font-weight:600;">{{ $d->nama }}</td>
                                    <td>{{ $d->prodi ?? '-' }}</td>
                                    <td style="text-align:center; font-weight:700; color:{{ $kelasCount > 0 ? '#0f172a' : '#94a3b8' }};">
                                        {{ $kelasCount }} Kelas
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align:center; padding:18px;">Belum ada data dosen.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Bagian Tanda Tangan Laporan --}}
                <div style="display:flex; justify-content:space-between; margin-top:40px; page-break-inside:avoid;">
                    <div style="text-align:center; width:220px;">
                        <p style="margin:0 0 60px; font-size:0.9rem;">Mengetahui,<br><strong>Dekan FTTK</strong></p>
                        <p style="margin:0; font-weight:700; text-decoration:underline;">(..............................................)</p>
                        <p style="margin:2px 0 0; font-size:0.8rem; color:#64748b;">NIP. ........................................</p>
                    </div>

                    <div style="text-align:center; width:220px;">
                        <p style="margin:0 0 60px; font-size:0.9rem;">Tanjungpinang, {{ now()->translatedFormat('d F Y') }}<br><strong>Ketua Jurusan {{ $jurusan->kode }}</strong></p>
                        <p style="margin:0; font-weight:700; text-decoration:underline;">{{ $jurusan->kajur ?? Auth::user()->nama }}</p>
                        <p style="margin:2px 0 0; font-size:0.8rem; color:#64748b;">NUPTK/NIP. {{ Auth::user()->nuptk ?? '........................................' }}</p>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>
@endsection

