@extends('layouts.app')

@section('title', 'APJAD - Riwayat Generate Akun Massal')

@section('content')
<div class="admin-layout">
    @include('admin.sidebar', ['active' => 'manajemen-akun-riwayat-generate'])

    <main class="admin-main">
        <header class="admin-header">
            <div class="user-badge">
                <span>{{ Auth::user()->role }}, <span class="user-name">{{ Auth::user()->nama }}</span></span>
            </div>
        </header>

        <section class="admin-content">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px; margin-bottom:24px;">
                <div>
                    <h1 class="page-title" style="margin-bottom:4px;">Riwayat Generate Akun Massal</h1>
                    <p style="color:#64748b; font-size:0.92rem; margin:0;">
                        Catatan histori setiap eksekusi pembuatan akun massal melalui Excel oleh <strong>Super Admin</strong> maupun <strong>Admin</strong>. Demi keamanan, password plaintext tidak pernah disimpan pada riwayat.
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
                    <a href="{{ route('manajemen-akun.generate-excel') }}" style="display: inline-flex; align-items: center; gap: 7px; border: none; background: #16a34a; color: #ffffff; padding: 10px 16px; border-radius: 8px; font-weight: 600; font-size: 0.88rem; text-decoration: none; box-shadow: 0 2px 6px rgba(22, 163, 74, 0.25);">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="12" y1="18" x2="12" y2="12"></line>
                            <line x1="9" y1="15" x2="15" y2="15"></line>
                        </svg>
                        <span>Generate Akun Excel</span>
                    </a>
                    <a href="{{ route('manajemen-akun.daftar') }}" class="btn-primary" style="display: inline-flex; align-items: center; gap: 7px; border: none; background: #0860be; color: #ffffff; padding: 10px 16px; border-radius: 8px; font-weight: 600; font-size: 0.88rem; text-decoration: none;">
                        <span>&larr; Kembali ke Manajemen Akun</span>
                    </a>
                </div>
            </div>

            <div class="table-card" style="background:#ffffff; border-radius:12px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); padding: 20px;">
                <div style="overflow-x:auto;">
                    <table class="data-table" style="width:100%; border-collapse:collapse; text-align:left;">
                        <thead>
                            <tr style="border-bottom: 2px solid #e2e8f0; background: #f8fafc;">
                                <th style="padding: 12px; color: #64748b; font-size: 0.82rem; text-transform: uppercase;">Tanggal &amp; Waktu</th>
                                <th style="padding: 12px; color: #64748b; font-size: 0.82rem; text-transform: uppercase;">Dieksekusi Oleh</th>
                                <th style="padding: 12px; color: #64748b; font-size: 0.82rem; text-transform: uppercase;">Nama File &amp; Metode Password</th>
                                <th style="padding: 12px; color: #64748b; font-size: 0.82rem; text-transform: uppercase; text-align:center;">Diproses</th>
                                <th style="padding: 12px; color: #64748b; font-size: 0.82rem; text-transform: uppercase; text-align:center;">Berhasil Dibuat</th>
                                <th style="padding: 12px; color: #64748b; font-size: 0.82rem; text-transform: uppercase; text-align:center;">Gagal</th>
                                <th style="padding: 12px; color: #64748b; font-size: 0.82rem; text-transform: uppercase;">Rincian Role &amp; Ringkasan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($histories as $item)
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 14px 12px; vertical-align:top; white-space:nowrap;">
                                        <div style="font-weight:700; color:#0f172a; font-size:0.88rem;">{{ $item->created_at?->format('d M Y, H:i') }}</div>
                                        <div style="font-size:0.76rem; color:#64748b;">{{ $item->created_at?->diffForHumans() }}</div>
                                    </td>
                                    <td style="padding: 14px 12px; vertical-align:top;">
                                        <div style="font-weight:700; color:#0f172a; font-size:0.88rem;">{{ $item->actor_name }}</div>
                                        <span style="display:inline-block; padding:2px 8px; border-radius:6px; font-size:0.74rem; font-weight:700; background:#e0f2fe; color:#0369a1; margin-top:3px;">
                                            {{ $item->actor_role }}
                                        </span>
                                    </td>
                                    <td style="padding: 14px 12px; vertical-align:top;">
                                        <div style="font-weight:600; color:#0f172a; font-size:0.88rem;">{{ $item->file_name }}</div>
                                        <div style="font-size:0.76rem; color:#64748b; margin-top:3px;">
                                            Metode: <strong>{{ $item->password_method === 'manual' ? 'Password Manual' : 'Password Otomatis' }}</strong>
                                        </div>
                                    </td>
                                    <td style="padding: 14px 12px; vertical-align:top; text-align:center; font-weight:700; color:#334155;">
                                        {{ $item->total_processed }}
                                    </td>
                                    <td style="padding: 14px 12px; vertical-align:top; text-align:center;">
                                        <span style="display:inline-block; padding:3px 10px; border-radius:999px; background:#dcfce7; color:#166534; font-weight:700; font-size:0.8rem;">
                                            {{ $item->total_created }}
                                        </span>
                                    </td>
                                    <td style="padding: 14px 12px; vertical-align:top; text-align:center;">
                                        <span style="display:inline-block; padding:3px 10px; border-radius:999px; background:{{ $item->total_failed > 0 ? '#fee2e2' : '#f1f5f9' }}; color:{{ $item->total_failed > 0 ? '#991b1b' : '#475569' }}; font-weight:700; font-size:0.8rem;">
                                            {{ $item->total_failed }}
                                        </span>
                                    </td>
                                    <td style="padding: 14px 12px; vertical-align:top;">
                                        <div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:6px; font-size:0.76rem; font-weight:700;">
                                            <span style="padding:3px 8px; border-radius:6px; background:#eff6ff; color:#1d4ed8;">
                                                Fakultas: {{ $item->count_fakultas }}
                                            </span>
                                            <span style="padding:3px 8px; border-radius:6px; background:#f0f9ff; color:#0369a1;">
                                                Jurusan: {{ $item->count_jurusan }}
                                            </span>
                                            <span style="padding:3px 8px; border-radius:6px; background:#ecfdf5; color:#047857;">
                                                Prodi: {{ $item->count_prodi }}
                                            </span>
                                        </div>

                                        @if(!empty($item->created_accounts_summary))
                                            <details style="font-size:0.78rem; color:#334155; margin-top:4px;">
                                                <summary style="cursor:pointer; font-weight:600; color:#0860be;">
                                                    Lihat {{ count($item->created_accounts_summary) }} Akun Dibuat
                                                </summary>
                                                <ul style="margin:6px 0 0 16px; padding:0;">
                                                    @foreach($item->created_accounts_summary as $acc)
                                                        <li>
                                                            <strong>{{ $acc['nama'] ?? '-' }}</strong> ({{ $acc['email'] ?? '-' }}) &mdash; {{ $acc['jabatan'] ?? ($acc['role'] ?? '-') }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </details>
                                        @endif

                                        @if(!empty($item->error_summary))
                                            <details style="font-size:0.78rem; color:#b91c1c; margin-top:4px;">
                                                <summary style="cursor:pointer; font-weight:600;">
                                                    Lihat Ringkasan Error ({{ count($item->error_summary) }})
                                                </summary>
                                                <ul style="margin:6px 0 0 16px; padding:0;">
                                                    @foreach($item->error_summary as $errLine)
                                                        <li>{{ $errLine }}</li>
                                                    @endforeach
                                                </ul>
                                            </details>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align:center; padding:32px; color:#64748b; font-size:0.9rem;">
                                        Belum ada riwayat generate akun massal.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($histories->hasPages())
                    <div style="padding-top:16px; margin-top:12px; border-top:1px solid #e2e8f0;">
                        {{ $histories->links() }}
                    </div>
                @endif
            </div>
        </section>
    </main>
</div>
@endsection
