@extends('layouts.app')

@section('title', 'APJAD - Penjadwalan Otomatis')

@push('styles')
<style>
/* ==========================================================================
   Tab Generate Jadwal - Form Layout & Responsiveness
   ========================================================================== */
.generate-form {
    display: flex;
    flex-direction: column;
    gap: 22px; /* Padding/gap vertikal antar baris seragam 20-24px */
}

.generate-form-row {
    display: grid;
    grid-template-columns: 190px 1fr; /* Kolom label tetap 180-200px, kolom input 1fr */
    gap: 20px;
    align-items: start; /* Rata atas */
}

.generate-form-label {
    white-space: nowrap; /* Label nowrap */
    color: var(--primary-blue, #0860be);
    font-size: 0.95rem; /* Tidak lebih besar dari teks input */
    font-weight: 700;
    line-height: 1.5;
    padding-top: 3px;
    user-select: none;
}

.generate-form-label.label-input {
    padding-top: 10px; /* Sejajar dengan teks di dalam input Prodi */
}

/* Radio Pilihan Periode */
.generate-radio-group {
    display: flex;
    flex-wrap: wrap;
    gap: 12px 28px; /* Satu baris horizontal, gap 12px 28px */
    align-items: center;
    min-height: 28px;
}

.generate-radio-item {
    display: inline-flex;
    align-items: center;
    gap: 8px; /* Radio dan teks sejajar vertikal */
    cursor: pointer;
    white-space: nowrap; /* Setiap opsi white-space nowrap */
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--primary-blue, #0860be);
    user-select: none;
    transition: var(--transition, all 0.2s ease);
}

.generate-radio-item:hover {
    color: var(--primary-blue-dark, #064b96);
}

.generate-radio-item input[type="radio"] {
    width: 17px;
    height: 17px;
    margin: 0;
    cursor: pointer;
    accent-color: var(--primary-blue, #0860be);
    flex-shrink: 0;
}

/* Checkbox Grid (Semester spesifik, Hari tersedia, Jam tersedia) */
.generate-checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 10px 24px; /* Gap konsisten 10px 24px */
    align-items: center;
}

.generate-check-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    white-space: nowrap;
    font-size: 0.95rem;
    font-weight: 400;
    color: var(--text-main, #0f172a);
    user-select: none;
    transition: var(--transition, all 0.2s ease);
}

.generate-check-item:hover {
    color: var(--primary-blue, #0860be);
}

.generate-check-item input[type="checkbox"] {
    width: 17px;
    height: 17px;
    margin: 0;
    cursor: pointer;
    accent-color: var(--primary-blue, #0860be);
    flex-shrink: 0;
}

/* Input Prodi */
.generate-input-wrap {
    width: 100%;
}

.generate-input-wrap input {
    width: 100%;
    padding: 10px 16px;
    border: 1.5px solid var(--border-color, #e2e8f0);
    border-radius: var(--radius-md, 10px);
    font-size: 0.95rem;
    font-family: var(--font-sans, inherit);
    outline: none;
    background: #ffffff;
    color: var(--text-main, #0f172a);
    transition: var(--transition, all 0.2s ease);
}

.generate-input-wrap input:focus {
    border-color: var(--primary-blue, #0860be);
    box-shadow: 0 0 0 3px rgba(8, 96, 190, 0.12);
}

/* Tombol Generate Sekarang */
.generate-form-actions {
    display: flex;
    justify-content: flex-end; /* Rata kanan */
    margin-top: 24px;          /* Margin atas 24px */
    margin-bottom: 24px;       /* Margin bawah minimal 24px */
}

.btn-generate-submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 28px; /* Padding nyaman */
    background-color: var(--color-success, #10b981);
    color: #ffffff;
    border: none;
    border-radius: var(--radius-md, 10px);
    font-size: 0.95rem;
    font-weight: 600;
    font-family: var(--font-sans, inherit);
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(16, 185, 129, 0.25);
    transition: var(--transition, all 0.2s cubic-bezier(0.4, 0, 0.2, 1));
}

.btn-generate-submit:hover:not(:disabled) {
    background-color: var(--color-success-hover, #059669);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(16, 185, 129, 0.32);
}

.btn-generate-submit:active:not(:disabled) {
    transform: translateY(0);
}

.btn-generate-submit:disabled,
.btn-generate-submit[disabled] {
    opacity: 0.65;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* Kotak Aturan yang Dijalankan */
.generate-rules-box {
    margin-top: 0;
    padding: 16px 20px;
    background: #f5f8fc;
    border: 1px solid #e2e8f0;
    border-radius: var(--radius-md, 8px);
    font-size: 13px;
    line-height: 1.7;
}

/* Responsive Layar <= 768px */
@media (max-width: 768px) {
    .generate-form {
        gap: 18px;
    }

    .generate-form-row {
        grid-template-columns: 1fr; /* Satu kolom: label di atas input */
        gap: 8px;
    }

    .generate-form-label,
    .generate-form-label.label-input {
        padding-top: 0;
        white-space: normal;
    }

    .generate-radio-group {
        gap: 10px 20px;
    }

    .generate-checkbox-grid {
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        gap: 8px 16px;
    }

    .generate-form-actions {
        justify-content: stretch;
    }

    .btn-generate-submit {
        width: 100%;
    }
}

/* ==========================================================================
   Log & Diagnosa Generate Terakhir - Modern UI
   ========================================================================== */
.log-generate-box {
    margin-top: 24px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
    overflow: hidden;
    transition: all 0.25s ease;
}

.log-summary-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border-bottom: 1px solid transparent;
    flex-wrap: wrap;
    gap: 14px;
}

.log-generate-box.expanded .log-summary-header {
    border-bottom-color: #e2e8f0;
}

.log-summary-left {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}

.log-summary-icon-wrap {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.log-summary-icon-wrap.all-success {
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
}

.log-summary-icon-wrap.has-failures {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.log-summary-title-row {
    display: flex;
    align-items: baseline;
    gap: 8px;
    margin-bottom: 4px;
}

.log-summary-title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: #0f172a;
}

.log-summary-subtitle {
    font-size: 0.85rem;
    color: #64748b;
    font-weight: 500;
}

.log-summary-pills {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.log-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 9999px;
    font-size: 0.8rem;
    font-weight: 600;
}

.pill-success {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
}

.pill-danger {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fca5a5;
}

.pill-warning {
    background: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
}

.btn-toggle-log {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    background: #ffffff;
    color: #0860be;
    border: 1.5px solid #bfdbfe;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(8, 96, 190, 0.08);
    transition: all 0.2s ease;
}

.btn-toggle-log:hover {
    background: #eff6ff;
    border-color: #93c5fd;
    transform: translateY(-1px);
}

.btn-toggle-log.active {
    background: #0860be;
    color: #ffffff;
    border-color: #0860be;
}

.chevron-icon {
    display: inline-block;
    transition: transform 0.2s ease;
}

.btn-toggle-log.active .chevron-icon {
    transform: rotate(180deg);
}

/* Collapsible Details Container */
.log-details-container {
    padding: 20px;
    background: #ffffff;
}

.log-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 20px;
}

.log-filter-group {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.log-filter-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #64748b;
}

.log-filter-btn {
    padding: 6px 14px;
    background: #f8fafc;
    color: #475569;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}

.log-filter-btn:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.log-filter-btn.active {
    background: #0860be;
    color: #ffffff;
    border-color: #0860be;
}

.log-filter-btn.filter-btn-danger.active {
    background: #dc2626;
    color: #ffffff;
    border-color: #dc2626;
}

.log-filter-btn.filter-btn-success.active {
    background: #10b981;
    color: #ffffff;
    border-color: #10b981;
}

.log-filter-btn.filter-btn-warning.active {
    background: #d97706;
    color: #ffffff;
    border-color: #d97706;
}

.log-semester-select {
    padding: 6px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #1e293b;
    background: #ffffff;
    cursor: pointer;
    outline: none;
}

.log-semester-block {
    margin-bottom: 24px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 18px;
    background: #fafbfc;
}

.log-semester-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 16px;
    padding-bottom: 10px;
    border-bottom: 1.5px solid #e2e8f0;
}

.log-semester-header h4 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e293b;
}

.log-sem-badges {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.badge-count-success {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
}

.badge-count-danger {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
}

.badge-count-info {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
}

/* Warnings */
.log-warning-card {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 8px;
    padding: 12px 14px;
    margin-bottom: 14px;
    font-size: 0.88rem;
    color: #92400e;
    line-height: 1.5;
}

/* Failures / Diagnosa Cards */
.log-failures-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.95rem;
    font-weight: 700;
    color: #b91c1c;
    margin-bottom: 12px;
}

.log-failures-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
    margin-bottom: 18px;
}

.log-failure-card {
    background: #ffffff;
    border: 1px solid #fecaca;
    border-left: 4px solid #ef4444;
    border-radius: 8px;
    padding: 14px 16px;
    box-shadow: 0 1px 3px rgba(239, 68, 68, 0.06);
}

.failure-card-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 10px;
}

.failure-mk-info {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.failure-mk-code {
    font-weight: 700;
    color: #0f172a;
    font-size: 0.95rem;
}

.failure-mk-name {
    color: #334155;
    font-weight: 600;
    font-size: 0.95rem;
}

.failure-mk-sks {
    background: #f1f5f9;
    color: #475569;
    padding: 2px 7px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
}

.failure-prodi-badge {
    background: #fee2e2;
    color: #991b1b;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.78rem;
    font-weight: 600;
}

.failure-cause-box {
    background: #fef2f2;
    border-radius: 6px;
    padding: 9px 12px;
    margin-bottom: 8px;
}

.failure-solution-box {
    background: #f0fdf4;
    border-radius: 6px;
    padding: 9px 12px;
}

.failure-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    font-weight: 700;
    margin-bottom: 3px;
}

.failure-label.red-label {
    color: #b91c1c;
}

.failure-label.green-label {
    color: #15803d;
}

.failure-text {
    font-size: 0.87rem;
    line-height: 1.45;
}

.failure-text.red-text {
    color: #991b1b;
}

.failure-text.green-text {
    color: #166534;
}

/* Success Table */
.log-success-table-wrap {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow-x: auto;
    margin-bottom: 14px;
}

.log-success-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
    text-align: left;
}

.log-success-table th {
    background: #f8fafc;
    color: #475569;
    font-weight: 600;
    padding: 10px 14px;
    border-bottom: 1.5px solid #e2e8f0;
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.log-success-table td {
    padding: 10px 14px;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
}

.log-success-table tr:last-child td {
    border-bottom: none;
}

.badge-status-ok {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #dcfce7;
    color: #166534;
    padding: 3px 8px;
    border-radius: 9999px;
    font-size: 0.78rem;
    font-weight: 700;
}

.col-mk-title {
    font-weight: 600;
    color: #0f172a;
}

.row-warning-text {
    font-size: 0.78rem;
    color: #d97706;
    font-weight: 500;
    margin-top: 3px;
}

.badge-class-pill {
    background: #f1f5f9;
    color: #334155;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
}

.badge-slot-time {
    display: inline-block;
    background: #eff6ff;
    color: #1e40af;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.82rem;
    font-weight: 600;
}

/* Raw Monospace Details */
.log-raw-accordion {
    margin-top: 10px;
}

.log-raw-accordion details summary {
    cursor: pointer;
    font-size: 0.82rem;
    font-weight: 600;
    color: #64748b;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    user-select: none;
    padding: 4px 8px;
    border-radius: 4px;
    transition: background 0.15s;
}

.log-raw-accordion details summary:hover {
    background: #e2e8f0;
    color: #0f172a;
}

.log-raw-content {
    margin-top: 8px;
    background: #0f172a;
    border-radius: 8px;
    padding: 12px 16px;
    max-height: 260px;
    overflow-y: auto;
}

.log-raw-content pre {
    margin: 0;
    color: #e2e8f0;
    font-family: 'Consolas', 'Courier New', monospace;
    font-size: 0.78rem;
    line-height: 1.6;
    white-space: pre-wrap;
}
</style>
@endpush

@section('content')
<div class="admin-layout">
    @include('partials.smart-sidebar', ['active' => 'penjadwalan-prodi', 'activeFakultas' => 'penjadwalan'])

    <main class="admin-main">
        <header class="admin-header">
            @include('partials.smart-user-badge')
        </header>

        <section class="admin-content">
            @if (session('flash_success'))
                <div class="flash-alert success">
                    <span>{{ session('flash_success') }}</span>
                </div>
            @endif

            @if (session('flash_error'))
                <div class="flash-alert error">
                    <span>{{ session('flash_error') }}</span>
                </div>
            @endif

            @if (session('flash_warning'))
                <div class="flash-alert warning" style="background:#fffbeb; color:#b45309; border:1px solid #fcd34d;">
                    <span>{{ session('flash_warning') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="flash-alert error">
                    <span>
                        @foreach ($errors->all() as $e)
                            {{ $e }}@if (! $loop->last), @endif
                        @endforeach
                    </span>
                </div>
            @endif

            {{-- Header Toolbar --}}
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
                <div>
                    <h1 class="page-title" style="margin-bottom:4px;">Penjadwalan Kuliah Prodi {{ $userProdi ?? 'Teknik Informatika' }}</h1>
                    <p style="color:#64748b; font-size:14px; margin:0;">
                        Generate jadwal kuliah otomatis bebas bentrok untuk program studi {{ $userProdi ?? 'Teknik Informatika' }}.
                    </p>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                    <a href="{{ route('penjadwalan.template') }}" style="display:inline-flex; align-items:center; gap:6px; background:#fff; color:#0369a1; border:1px solid #bae6fd; padding:9px 14px; border-radius:6px; font-weight:600; cursor:pointer; font-size:13px; text-decoration:none;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        <span>Unduh Template Excel</span>
                    </a>

                    <button type="button" onclick="openModal('modalImportMk')" style="display:inline-flex; align-items:center; gap:6px; background:#16a34a; color:#fff; border:none; padding:9px 16px; border-radius:6px; font-weight:600; cursor:pointer; font-size:13px; box-shadow:0 2px 6px rgba(22,163,74,0.25);">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        <span>Import Spreadsheet</span>
                    </button>

                    @if($totalJadwalCount > 0)
                        <form action="{{ route('penjadwalan.destroy-all') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus SEMUA jadwal kuliah prodi yang telah digenerate? Data mata kuliah tetap tersimpan.');" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="scope" value="jadwal_only">
                            <button type="submit" style="display:inline-flex; align-items:center; gap:6px; background:#fee2e2; color:#dc2626; border:1px solid #fecaca; padding:9px 14px; border-radius:6px; font-weight:600; cursor:pointer; font-size:13px;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                <span>Reset Jadwal</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- 4 KPI Stat Cards --}}
            <div class="stats-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap:16px; margin-bottom:24px;">
                <div class="stat-card">
                    <div class="stat-icon users-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Total Jadwal Terplot</div>
                        <div class="stat-value">{{ $totalJadwalCount }}</div>
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
                        <div class="stat-label">Total SKS Terjadwal</div>
                        <div class="stat-value">{{ $totalSksCount }}</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon mkwu-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Mata Kuliah Ditawarkan</div>
                        <div class="stat-value">{{ $totalMkCount }} MK</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon mkwf-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Ruangan Siap Digunakan</div>
                        <div class="stat-value">{{ $totalRuanganCount }} Ruang</div>
                    </div>
                </div>
            </div>

            {{-- Generator Section Card --}}
            <div class="card" style="background:#fff; border:1px solid #cbd5e1; border-radius:12px; padding:22px; margin-bottom:28px; box-shadow:0 2px 8px rgba(0,0,0,0.04);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; border-bottom:1px solid #f1f5f9; padding-bottom:12px;">
                    <div>
                        <h3 style="margin:0 0 4px; font-size:16px; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:8px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                            Generator Jadwal Otomatis Bebas Bentrok
                        </h3>
                        <p style="margin:0; font-size:13px; color:#64748b;">
                            Engine penjadwalan cerdas mengoptimalkan ruangan prodi, menghindari tabrakan jam mengajar dosen, dan mendistribusikan kelas secara merata.
                        </p>
                    </div>
                    <span style="background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; font-size:12px; font-weight:600; padding:4px 10px; border-radius:20px;">
                        Ruangan Siap: {{ $totalRuanganCount }}
                    </span>
                </div>

                <form action="{{ route('penjadwalan.generate') }}" method="POST" id="formGenerate">
                    @csrf
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:18px; margin-bottom:20px;">
                        <div>
                            <label style="display:block; font-size:13px; font-weight:700; color:#334155; margin-bottom:6px;">
                                Periode Semester <span style="color:#ef4444;">*</span>
                            </label>
                            <select name="semester_mode" id="generatorSemesterMode" onchange="syncSemesterSelection(this.value)" required style="width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; background:#fff;">
                                <option value="ganjil" selected>Semester Ganjil (1, 3, 5, 7)</option>
                                <option value="genap">Semester Genap (2, 4, 6, 8)</option>
                                <option value="all">Semua Semester (1 s/d 8)</option>
                                @foreach ($semesterAll as $sem)
                                    <option value="{{ $sem }}">Semester {{ $sem }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label style="display:block; font-size:13px; font-weight:700; color:#334155; margin-bottom:6px;">
                                Program Studi Target <span style="color:#ef4444;">*</span>
                            </label>
                            @if (($userRole ?? '') === 'Prodi')
                                <input type="text" name="prodi" value="{{ $userProdi ?? 'Teknik Informatika' }}" readonly style="width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; background:#f8fafc; color:#334155; font-weight:600; cursor:not-allowed;">
                            @else
                                <select name="prodi" style="width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; background:#fff;">
                                    @foreach ($daftarProdi as $dp)
                                        <option value="{{ $dp->nama }}" @selected($loop->first)>{{ $dp->nama }} ({{ $dp->kode }})</option>
                                    @endforeach
                                    <option value="Semua">Semua Program Studi</option>
                                </select>
                            @endif
                        </div>
                    </div>

                    <!-- Hidden Semester Checkboxes to maintain backwards compatibility -->
                    <div id="hiddenSemesterInputs" style="display:none;">
                        @foreach ($semesterAll as $sem)
                            <input type="checkbox" name="semester[]" value="{{ $sem }}" id="sem_input_{{ $sem }}" @checked($sem % 2 === 1)>
                        @endforeach
                    </div>

                    <div style="margin-bottom:18px;">
                        <label style="display:block; font-size:13px; font-weight:700; color:#334155; margin-bottom:8px;">
                            Pilihan Hari Kuliah:
                        </label>
                        <div style="display:flex; flex-wrap:wrap; gap:16px;">
                            @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $hari)
                                <label style="display:inline-flex; align-items:center; gap:8px; font-size:13px; font-weight:500; color:#1e293b; cursor:pointer;">
                                    <input type="checkbox" name="hari[]" value="{{ $hari }}" checked style="width:16px; height:16px; accent-color:#2563eb;">
                                    {{ $hari }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div style="margin-bottom:24px;">
                        <label style="display:block; font-size:13px; font-weight:700; color:#334155; margin-bottom:8px;">
                            Slot Waktu Kuliah (Jam Mulai):
                        </label>
                        <div style="display:flex; flex-wrap:wrap; gap:14px;">
                            @foreach (['07:30', '10:00', '13:00', '14:50', '15:30'] as $jam)
                                <label style="display:inline-flex; align-items:center; gap:8px; font-size:13px; background:#f8fafc; border:1px solid #e2e8f0; padding:8px 14px; border-radius:6px; cursor:pointer;">
                                    <input type="checkbox" name="jam_mulai[]" value="{{ $jam }}" checked style="width:16px; height:16px; accent-color:#2563eb;">
                                    <span><strong>{{ $jam }}</strong></span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                        <button type="submit" id="btnSubmitGenerate" style="background:#2563eb; color:#fff; border:none; padding:12px 24px; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:8px; box-shadow:0 2px 6px rgba(37,99,235,0.3);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                            <span>Mulai Generate Jadwal Sekarang</span>
                        </button>

                        <button type="button" onclick="toggleElement('rulesInfoBox')" style="background:none; border:none; color:#64748b; font-size:13px; cursor:pointer; text-decoration:underline;">
                            Lihat Aturan Penjadwalan â–¾
                        </button>
                    </div>

                    <div id="rulesInfoBox" style="display:none; margin-top:16px; padding:14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; font-size:12.5px; color:#475569;">
                        <strong style="color:#1e293b;">Aturan Engine yang Dijalankan:</strong>
                        <ul style="margin:6px 0 0 18px; line-height:1.6;">
                            <li>Bentrok kelas tidak diperbolehkan &mdash; satu rombel prodi+semester tidak memiliki 2 MK pada waktu bersamaan.</li>
                            <li>Dosen ketua tidak boleh bentrok mengajar di MK mana pun.</li>
                            <li>Jadwal aktif yang sudah ada ikut dihitung &mdash; ruangan, dosen, dan rombel tidak ditimpa.</li>
                            <li>Hari dan jam yang tidak dicentang tidak akan digunakan oleh generator.</li>
                            <li>Slot berurutan dimulai dari 07:30, 10:00, 13:00, 14:50, 15:30. Durasi dihitung berdasarkan SKS x 50 menit.</li>
                            <li>MK Pilihan dibagi ke dalam kelompok slot untuk mencegah bentrok mahasiswa.</li>
                        </ul>
                    </div>
                </form>
            </div>

            <div class="tabs-container">
                <div class="tabs-header">
                    <button type="button" class="tab-btn active" data-tab="tab-jadwal" onclick="showTab('tab-jadwal', this)">Jadwal Semester</button>
                    <button type="button" class="tab-btn" data-tab="tab-input" onclick="showTab('tab-input', this)">Data Mata Kuliah</button>
                    <button type="button" class="tab-btn" data-tab="tab-ruangan" onclick="showTab('tab-ruangan', this)">Manajemen Ruangan</button>
                    @if (! empty($logGenerate))
                        <button type="button" class="tab-btn" data-tab="tab-log" onclick="showTab('tab-log', this)">Log &amp; Diagnosa</button>
                    @endif
                </div>

                {{-- ============ INPUT MATA KULIAH ============ --}}
                <div class="tab-content" id="tab-input">
                    <div class="tab-header">
                        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                            <h2>Data Mata Kuliah</h2>
                            @if (($userRole ?? '') === 'Prodi')
                                <span style="background:#f0fdf4; color:#15803d; padding:5px 12px; border-radius:20px; font-size:12.5px; font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                                    Prodi: {{ $userProdi ?? 'Teknik Informatika' }}
                                </span>
                            @endif
                        </div>
                        <div style="display:flex; gap:10px; flex-wrap:wrap;">
                            <a href="{{ route('penjadwalan.template') }}" class="btn-add" style="background:#0284c7; text-decoration:none;" download title="Unduh format template Excel untuk pengisian mata kuliah">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                </svg>
                                Unduh Template Excel
                            </a>
                            <button type="button" class="btn-add" onclick="openModal('modalImportMk')" style="background:#16a34a;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                Import Excel / Spreadsheet
                            </button>
                            <button type="button" class="btn-add" onclick="openTambahMk()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                Tambah MK
                            </button>
                            @if ($mataKuliah->isNotEmpty())
                                <button type="button" class="btn-add" onclick="openModalHapusSemua('all')" style="background:#dc2626;" title="Hapus semua data mata kuliah dan jadwal">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        <line x1="10" y1="11" x2="10" y2="17"></line>
                                        <line x1="14" y1="11" x2="14" y2="17"></line>
                                    </svg>
                                    Hapus Semua Data
                                </button>
                            @endif
                        </div>
                    </div>

                    @if (session('import_gagal'))
                        <div class="flash-alert error" style="margin-bottom:16px;">
                            <div>
                                <strong>Sebagian baris gagal:</strong>
                                <ul style="margin:8px 0 0 18px;">
                                    @foreach (session('import_gagal') as $gagal)
                                        <li>{{ $gagal }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kode MK</th>
                                <th>Nama MK</th>
                                <th>SKS</th>
                                <th>Semester</th>
                                <th>Tipe</th>
                                <th>Dosen Ketua</th>
                                <th>Dosen Anggota</th>
                                <th style="text-align:center;">Kelas</th>
                                <th style="text-align:center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($mataKuliah as $sem => $listMk)
                                @foreach ($listMk as $mk)
                                    <tr>
                                        <td><strong>{{ $mk->clean_kode_mk }}</strong></td>
                                        <td>{{ $mk->nama_mk }}</td>
                                        <td>{{ $mk->sks }}</td>
                                        <td>{{ $mk->semester }}</td>
                                        <td>{{ $mk->tipe }}</td>
                                        <td>{{ $mk->dosenKetua->nama ?? '-' }}</td>
                                        <td>{{ $mk->dosenAnggota->nama ?? '-' }}</td>
                                        <td style="text-align:center;">
                                            <span style="display:inline-block; padding:2px 8px; background:#dbeafe; color:#1e40af; border-radius:12px; font-weight:700; font-size:0.8rem;">
                                                {{ $mk->kelas_label }}
                                            </span>
                                        </td>
                                        <td style="text-align:center;">
                                            @if (($userRole ?? '') === 'Prodi' && ! empty($userProdi) && stripos($mk->prodi, $userProdi) === false)
                                                <span style="font-size:11px; background:#f1f5f9; color:#475569; padding:2px 8px; border-radius:4px;" title="Mata Kuliah Fakultas/Universitas">MK Bersama</span>
                                            @else
                                                <div class="action-buttons-group" style="justify-content:center;">
                                                    <button type="button" class="btn-action" title="Edit"
                                                        onclick='openEditMk(@json($mk))'>
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                            <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"></path>
                                                        </svg>
                                                    </button>
                                                    <form action="{{ route('penjadwalan.destroy', $mk->id) }}" method="POST" style="display:inline;"
                                                        onsubmit="return konfirmasiHapus(event, '{{ $mk->kode_mk }}')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn-action delete-btn" title="Hapus">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="9" style="text-align:center; padding:24px;">Belum ada mata kuliah.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- ============ MANAJEMEN RUANGAN ============ --}}
                <div class="tab-content" id="tab-ruangan">
                    <div class="tab-header">
                        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                            <h2>Manajemen Ruangan Kuliah & Lab</h2>
                            <span style="background:#eff6ff; color:#1d4ed8; padding:5px 12px; border-radius:20px; font-size:12.5px; font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l8-4 8 4v14M8 21v-8h8v8"/></svg>
                                Total: {{ $ruangans->count() }} Ruangan ({{ $ruanganAktif->count() }} Aktif)
                            </span>
                        </div>
                        <div style="display:flex; gap:10px; flex-wrap:wrap;">
                            <button type="button" class="btn-add" onclick="openTambahRuangan()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                Tambah Ruangan
                            </button>
                        </div>
                    </div>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kode Ruangan</th>
                                <th>Nama Ruangan</th>
                                <th>Tipe</th>
                                <th>Kapasitas</th>
                                <th>Gedung & Lantai</th>
                                <th style="text-align:center;">Status</th>
                                <th style="text-align:center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ruangans as $r)
                                <tr>
                                    <td>
                                        <strong>{{ $r->kode }}</strong>
                                        @if ($r->isDefault())
                                            <span style="display: inline-block; font-size: 0.72rem; font-weight: 600; padding: 2px 7px; background: #e0f2fe; color: #0369a1; border-radius: 4px; margin-left: 6px;" title="Ruangan bawaan resmi kampus">Kampus</span>
                                        @endif
                                    </td>
                                    <td>{{ $r->nama }}</td>
                                    <td>
                                        @if ($r->tipe === 'Lab')
                                            <span style="display: inline-block; padding: 3px 10px; background: #dcfce7; color: #15803d; border-radius: 12px; font-size: 0.82rem; font-weight: 600;">Lab</span>
                                        @elseif ($r->tipe === 'Ruang Kelas')
                                            <span style="display: inline-block; padding: 3px 10px; background: #eff6ff; color: #1d4ed8; border-radius: 12px; font-size: 0.82rem; font-weight: 600;">Ruang Kelas</span>
                                        @elseif ($r->tipe === 'Auditorium' || $r->tipe === 'Aula')
                                            <span style="display: inline-block; padding: 3px 10px; background: #f3e8ff; color: #7e22ce; border-radius: 12px; font-size: 0.82rem; font-weight: 600;">Auditorium</span>
                                        @else
                                            <span style="display: inline-block; padding: 3px 10px; background: #fef3c7; color: #92400e; border-radius: 12px; font-size: 0.82rem; font-weight: 600;">{{ $r->tipe }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $r->kapasitas }} Kursi</td>
                                    <td>{{ $r->gedung ?? '-' }} / Lt. {{ $r->lantai ?? '-' }}</td>
                                    <td style="text-align:center;">
                                        <form action="{{ route('ruangan.toggle-status', $r->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @if ($r->is_active)
                                                <button type="submit" style="background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; border-radius:14px; padding:3px 10px; font-size:12px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:5px; transition:all 0.2s;" title="Klik untuk menonaktifkan ruangan">
                                                    <span style="width:7px; height:7px; border-radius:50%; background:#10b981; display:inline-block;"></span>
                                                    Aktif
                                                </button>
                                            @else
                                                <button type="submit" style="background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; border-radius:14px; padding:3px 10px; font-size:12px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:5px; transition:all 0.2s;" title="Klik untuk mengaktifkan ruangan">
                                                    <span style="width:7px; height:7px; border-radius:50%; background:#ef4444; display:inline-block;"></span>
                                                    Nonaktif
                                                </button>
                                            @endif
                                        </form>
                                    </td>
                                    <td style="text-align:center;">
                                        <div class="action-buttons-group" style="justify-content:center;">
                                                <button type="button" class="btn-action edit-btn" title="Edit Ruangan"
                                                    onclick='openEditRuangan(@json($r))'>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                        <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"></path>
                                                    </svg>
                                                </button>
                                                @if ($r->isDefault())
                                                    <button type="button" class="btn-action" disabled title="Ruangan bawaan resmi kampus tidak dapat dihapus (dapat dinonaktifkan jika tidak digunakan)" style="opacity:0.4; cursor:not-allowed;">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                                        </svg>
                                                    </button>
                                                @else
                                                    <form action="{{ route('ruangan.destroy', $r->id) }}" method="POST" style="display:inline;"
                                                        onsubmit="return konfirmasiHapusRuangan(event, '{{ $r->kode }}')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn-action delete-btn" title="Hapus Ruangan">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align:center; padding:24px;">Belum ada data ruangan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (! empty($logGenerate))
                    {{-- ============ TAB LOG & DIAGNOSA ============ --}}
                    <div class="tab-content" id="tab-log">
                        <div class="tab-header" style="margin-bottom: 20px;">
                            <h2 style="margin: 0 0 4px 0;">Log &amp; Diagnosa Hasil Penjadwalan</h2>
                            <p style="margin: 0; font-size: 0.9rem; color: #64748b;">Rincian proses plotting jadwal cerdas, distribusi ruangan, dan deteksi peringatan alokasi.</p>
                        </div>
                        @php
                            $totalBerhasil = 0;
                            $allFailuresCount = 0;
                            $totalPeringatan = 0;
                            $semestersInLog = [];

                            foreach ($logGenerate as $h) {
                                $totalBerhasil += ($h['jumlah'] ?? 0);
                                $totalPeringatan += count($h['peringatan'] ?? []);
                                $semestersInLog[] = $h['semester'];
                                
                                $semFailures = $h['kegagalan'] ?? [];
                                $existingCodes = array_column($semFailures, 'kode_mk');
                                foreach ($h['log'] as $b) {
                                    $tb = trim($b);
                                    if (str_starts_with($tb, 'GAGAL')) {
                                        $clean = preg_replace('/^GAGAL\s+/', '', $tb);
                                        $codeFound = false;
                                        foreach ($existingCodes as $c) {
                                            if ($c && str_contains($clean, $c)) {
                                                $codeFound = true;
                                                break;
                                            }
                                        }
                                        if (! $codeFound) {
                                            $semFailures[] = true;
                                        }
                                    }
                                }
                                $allFailuresCount += count($semFailures);
                            }
                            $semestersList = array_values(array_unique($semestersInLog));
                        @endphp

                        <div class="log-generate-box" id="logGenerateBox">
                            {{-- SUMMARY TRIGGER HEADER --}}
                            <div class="log-summary-header">
                                <div class="log-summary-left">
                                    <div class="log-summary-icon-wrap {{ $allFailuresCount > 0 ? 'has-failures' : 'all-success' }}">
                                        @if ($allFailuresCount > 0)
                                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        @else
                                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="log-summary-title-row">
                                            <h3 class="log-summary-title">Log & Hasil Generate Terakhir</h3>
                                            <span class="log-summary-subtitle">Semester {{ implode(', ', $semestersList) }}</span>
                                        </div>
                                        <div class="log-summary-pills">
                                            <span class="log-pill pill-success">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                {{ $totalBerhasil }} Berhasil Terjadwal
                                            </span>
                                            @if ($allFailuresCount > 0)
                                                <span class="log-pill pill-danger">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                                                    {{ $allFailuresCount }} Butuh Penyesuaian
                                                </span>
                                            @endif
                                            @if ($totalPeringatan > 0)
                                                <span class="log-pill pill-warning">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                                                    {{ $totalPeringatan }} Catatan Sistem
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="log-summary-right" style="display:flex; align-items:center; gap:8px;">
                                    <form action="{{ route('penjadwalan.clear-log') }}" method="POST" style="margin:0;" onsubmit="return confirm('Apakah Anda yakin ingin membersihkan log generate ini?');">
                                        @csrf
                                        <button type="submit" 
                                            style="display:inline-flex; align-items:center; gap:6px; padding:9px 12px; background:#ffffff; color:#dc2626; border:1px solid #fecaca; border-radius:8px; font-size:0.85rem; font-weight:600; cursor:pointer; transition:all 0.2s;"
                                            title="Bersihkan riwayat log generate ini"
                                            onmouseover="this.style.background='#fef2f2';"
                                            onmouseout="this.style.background='#ffffff';">
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                            <span>Bersihkan</span>
                                        </button>
                                    </form>

                                    <button type="button" id="btnToggleLogGenerate" onclick="toggleLogGenerate()" class="btn-toggle-log">
                                        <svg id="iconToggleLogState" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        <span id="textToggleLog">Buka Log & Diagnosa</span>
                                        <span id="chevronToggleLog" class="chevron-icon">&#9662;</span>
                                    </button>
                                </div>
                            </div>

                            {{-- COLLAPSIBLE DETAILS CONTAINER (HIDDEN BY DEFAULT) --}}
                            <div id="containerLogGenerate" class="log-details-container" style="display: none;">
                                {{-- FILTER TOOLBAR --}}
                                <div class="log-toolbar">
                                    <div class="log-filter-group">
                                        <span class="log-filter-label">Filter Status:</span>
                                        <button type="button" class="log-filter-btn active" data-filter="all" onclick="applyLogFilter('all', this)">
                                            Semua
                                        </button>
                                        @if ($allFailuresCount > 0)
                                            <button type="button" class="log-filter-btn filter-btn-danger" data-filter="gagal" onclick="applyLogFilter('gagal', this)">
                                                <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#ef4444; margin-right:6px;"></span>Butuh Penyesuaian ({{ $allFailuresCount }})
                                            </button>
                                        @endif
                                        <button type="button" class="log-filter-btn filter-btn-success" data-filter="sukses" onclick="applyLogFilter('sukses', this)">
                                            <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#10b981; margin-right:6px;"></span>Berhasil ({{ $totalBerhasil }})
                                        </button>
                                        @if ($totalPeringatan > 0)
                                            <button type="button" class="log-filter-btn filter-btn-warning" data-filter="warning" onclick="applyLogFilter('warning', this)">
                                                <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#f59e0b; margin-right:6px;"></span>Catatan ({{ $totalPeringatan }})
                                            </button>
                                        @endif
                                    </div>

                                    @if (count($semestersList) > 1)
                                        <div class="log-filter-group">
                                            <span class="log-filter-label">Semester:</span>
                                            <select id="selectSemesterLogFilter" onchange="filterLogBySemester(this.value)" class="log-semester-select">
                                                <option value="all">Semua Semester</option>
                                                @foreach ($semestersList as $s)
                                                    <option value="{{ $s }}">Semester {{ $s }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif
                                </div>

                                {{-- LOOP PER SEMESTER --}}
                                <div class="log-semester-items-wrap">
                                    @foreach ($logGenerate as $hasil)
                                        @php
                                            $failures = $hasil['kegagalan'] ?? [];
                                            $existingFailedCodes = array_column($failures, 'kode_mk');
                                            foreach ($hasil['log'] as $baris) {
                                                $trimmed = trim($baris);
                                                if (str_starts_with($trimmed, 'GAGAL')) {
                                                    $cleanLine = preg_replace('/^GAGAL\s+/', '', $trimmed);
                                                    $parts = explode('|', $cleanLine);
                                                    $mkInfo = trim($parts[0] ?? '');
                                                    $codeFound = false;
                                                    foreach ($existingFailedCodes as $efc) {
                                                        if ($efc && str_contains($mkInfo, $efc)) {
                                                            $codeFound = true;
                                                            break;
                                                        }
                                                    }
                                                    if (! $codeFound) {
                                                        $splitDash = explode(' - ', $mkInfo, 2);
                                                        $failures[] = [
                                                            'kode_mk' => trim($splitDash[0] ?? 'MK'),
                                                            'nama_mk' => trim($splitDash[1] ?? $mkInfo),
                                                            'sks' => '-',
                                                            'semester' => $hasil['semester'],
                                                            'prodi' => 'Umum',
                                                            'tipe' => 'Wajib',
                                                            'alasan' => 'Tidak ada slot waktu kosong yang memenuhi syarat (ruangan/dosen/cohort) untuk kelas ini.',
                                                            'rekomendasi' => 'Aktifkan ruangan tambahan di menu Data Ruangan atau periksa ketersediaan jam dosen pengampu.',
                                                        ];
                                                    }
                                                }
                                            }
                                        @endphp

                                        <div class="log-semester-block" data-semester="{{ $hasil['semester'] }}">
                                            <div class="log-semester-header">
                                                <h4>Semester {{ $hasil['semester'] }}</h4>
                                                <div class="log-sem-badges">
                                                    <span class="badge-count-success">{{ $hasil['jumlah'] }} jadwal berhasil</span>
                                                    @if (! empty($failures))
                                                        <span class="badge-count-danger">{{ count($failures) }} perlu penyesuaian</span>
                                                    @endif
                                                    @if (! empty($hasil['slot_pilihan']))
                                                        <span class="badge-count-info">Kuota MK Pilihan: {{ $hasil['kuota_pilihan'] }}</span>
                                                    @endif
                                                </div>
                                            </div>

                                            {{-- PERINGATAN SISTEM (WARNINGS) --}}
                                            @if (! empty($hasil['peringatan']))
                                                <div class="log-section-warnings log-item-warning">
                                                    @foreach ($hasil['peringatan'] as $p)
                                                        <div class="log-warning-card">
                                                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#b45309" stroke-width="2" style="flex-shrink:0; margin-top:2px;">
                                                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                                            </svg>
                                                            <div>
                                                                <strong>Catatan Sistem:</strong> {{ $p }}
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            {{-- DIAGNOSA KEGAGALAN / BUTUH PENYESUAIAN --}}
                                            @if (! empty($failures))
                                                <div class="log-section-failures log-item-gagal">
                                                    <div class="log-failures-title">
                                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#dc2626" stroke-width="2">
                                                            <circle cx="12" cy="12" r="10"></circle>
                                                            <line x1="12" y1="8" x2="12" y2="12"></line>
                                                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                                        </svg>
                                                        <span>Mata Kuliah Yang Memerlukan Penyesuaian ({{ count($failures) }})</span>
                                                    </div>
                                                    <div class="log-failures-grid">
                                                        @foreach ($failures as $keg)
                                                            <div class="log-failure-card">
                                                                <div class="failure-card-top">
                                                                    <div class="failure-mk-info">
                                                                        <span class="failure-mk-code">{{ $keg['kode_mk'] }}</span>
                                                                        <span class="failure-mk-name">{{ $keg['nama_mk'] }}</span>
                                                                        @if (!empty($keg['sks']) && $keg['sks'] !== '-')
                                                                            <span class="failure-mk-sks">{{ $keg['sks'] }} SKS</span>
                                                                        @endif
                                                                    </div>
                                                                    <span class="failure-prodi-badge">{{ $keg['prodi'] ?? 'Umum' }}</span>
                                                                </div>
                                                                <div class="failure-cause-box">
                                                                    <div class="failure-label red-label">
                                                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                                                                        Penyebab:
                                                                    </div>
                                                                    <div class="failure-text red-text">{{ $keg['alasan'] }}</div>
                                                                </div>
                                                                <div class="failure-solution-box">
                                                                    <div class="failure-label green-label">
                                                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                                                                        Rekomendasi Solusi:
                                                                    </div>
                                                                    <div class="failure-text green-text">{{ $keg['rekomendasi'] }}</div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            {{-- JADWAL BERHASIL TERJADWAL --}}
                                            @php
                                                $logSuccessItems = [];
                                                foreach ($hasil['log'] as $baris) {
                                                    if (str_starts_with(trim($baris), 'OK')) {
                                                        $logSuccessItems[] = $baris;
                                                    }
                                                }
                                            @endphp

                                            @if (! empty($logSuccessItems))
                                                <div class="log-section-success log-item-sukses">
                                                    <div class="log-success-table-wrap">
                                                        <table class="log-success-table">
                                                            <thead>
                                                                <tr>
                                                                    <th style="width: 100px;">Status</th>
                                                                    <th>Kode & Nama Mata Kuliah</th>
                                                                    <th style="width: 120px;">Kelas</th>
                                                                    <th style="width: 240px;">Hari & Jam Kuliah</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($logSuccessItems as $baris)
                                                                    @php
                                                                        $parts = explode('|', $baris);
                                                                        $part0 = trim(preg_replace('/^OK\s+/', '', $parts[0] ?? ''));
                                                                        $part1 = trim($parts[1] ?? '1/1 kelas');
                                                                        $part2 = trim($parts[2] ?? '-');
                                                                        $part3 = isset($parts[3]) ? trim($parts[3]) : null;

                                                                        $extractedKode = $part0;
                                                                        $extractedNama = '';
                                                                        $extractedClass = null;
                                                                        if (str_contains($part0, ' - ')) {
                                                                            $p = explode(' - ', $part0, 2);
                                                                            $extractedKode = trim($p[0]);
                                                                            $extractedNama = trim($p[1]);
                                                                        }
                                                                        if (preg_match('/-([A-Za-z0-9]+)$/', $extractedKode, $mCls)) {
                                                                            $extractedClass = strtoupper($mCls[1]);
                                                                            $extractedKode = preg_replace('/-[A-Za-z0-9]+$/', '', $extractedKode);
                                                                        }
                                                                        if (! $extractedClass) {
                                                                            $extractedClass = 'A';
                                                                        }
                                                                    @endphp
                                                                    <tr>
                                                                        <td>
                                                                            <span class="badge-status-ok">
                                                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                                                OK
                                                                            </span>
                                                                        </td>
                                                                        <td class="col-mk-title">
                                                                            <strong>{{ $extractedKode }}</strong>@if($extractedNama) - {{ $extractedNama }}@endif
                                                                            @if ($part3)
                                                                                <div class="row-warning-text">{{ $part3 }}</div>
                                                                            @endif
                                                                        </td>
                                                                        <td style="text-align: center;">
                                                                            <span class="badge-class-pill" style="font-weight: 700; background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe;">Kelas {{ $extractedClass }}</span>
                                                                        </td>
                                                                        <td>
                                                                            <span class="badge-slot-time">{{ $part2 }}</span>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            @endif

                                            {{-- COLLAPSIBLE RAW LOG (UNTUK DEBUG / SALIN TEKS) --}}
                                            <div class="log-raw-accordion">
                                                <details>
                                                    <summary>
                                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 17 10 11 4 5"></polyline><line x1="12" y1="19" x2="20" y2="19"></line></svg>
                                                        Lihat Teks Mentah (Raw Monospace Output)
                                                    </summary>
                                                    <div class="log-raw-content">
                                                        <pre>@foreach ($hasil['log'] as $baris){{ $baris }}
@endforeach</pre>
                                                    </div>
                                                </details>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ============ TAB JADWAL SEMESTER ============ --}}
                <div class="tab-content active" id="tab-jadwal">
                    <div class="tab-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:20px;">
                        <div>
                            <h2 style="margin:0 0 4px 0;">Jadwal Semester</h2>
                            <p style="margin:0; font-size:0.9rem; color:#64748b;">Lihat jadwal terstruktur per semester dalam mode Ganjil atau Genap.</p>
                        </div>

                        <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                            @if (! empty($logGenerate))
                                <button type="button" 
                                    onclick="showTab('tab-log'); const el = document.getElementById('logGenerateBox'); if(el) el.scrollIntoView({behavior:'smooth'});"
                                    style="background:#eff6ff; color:#0860be; border:1.5px solid #bfdbfe; border-radius:18px; padding:7px 16px; font-size:0.88rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all 0.2s;"
                                    title="Buka Log & Diagnosa Hasil Generate Terakhir">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                    </svg>
                                    <span>Log Hasil Generate</span>
                                </button>
                            @endif

                            {{-- Filter Dropdown --}}
                            <div style="display:flex; align-items:center; gap:8px;">
                                <label for="filterSemesterSelect" style="font-size:0.9rem; font-weight:600; color:#1e293b; white-space:nowrap;">Semester:</label>
                                <select id="filterSemesterSelect" onchange="filterSemester(this.value)" 
                                    style="padding:7px 14px; border:1.5px solid #cbd5e1; border-radius:18px; font-size:0.9rem; outline:none; background:#ffffff; color:#1e293b; cursor:pointer;">
                                    <option value="all">Semua Semester</option>
                                    {{-- Options dynamically rendered by JS based on active toggle --}}
                                </select>
                            </div>

                            {{-- Filter Prodi Dropdown --}}
                            @if (($userRole ?? '') === 'Prodi')
                                <span style="font-size:0.9rem; font-weight:600; color:#1e293b; background:#f1f5f9; padding:6px 14px; border-radius:18px; border:1px solid #cbd5e1;">
                                    Prodi: {{ $userProdi ?? 'Teknik Informatika' }}
                                </span>
                            @else
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <label for="filterProdiSelect" style="font-size:0.9rem; font-weight:600; color:#1e293b; white-space:nowrap;">Prodi:</label>
                                    <select id="filterProdiSelect" onchange="filterProdi(this.value)" 
                                        style="padding:7px 14px; border:1.5px solid #cbd5e1; border-radius:18px; font-size:0.9rem; outline:none; background:#ffffff; color:#1e293b; cursor:pointer; font-weight:600;">
                                        <option value="all">Semua Prodi</option>
                                        @foreach ($daftarProdi as $dp)
                                            <option value="{{ $dp->nama }}">{{ $dp->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            {{-- Pill Toggle Ganjil | Genap --}}
                            <div class="pill-toggle-container" style="display:inline-flex; background:#e2e8f0; padding:4px; border-radius:30px; user-select:none;">
                                <button type="button" id="btnToggleGanjil" onclick="switchPeriode('ganjil')" 
                                    style="padding:7px 20px; border:none; border-radius:24px; font-weight:700; font-size:0.9rem; cursor:pointer; transition:all 0.2s ease; background:#0860be; color:#ffffff; box-shadow:0 2px 4px rgba(8,96,190,0.25);">
                                    Ganjil
                                </button>
                                <button type="button" id="btnToggleGenap" onclick="switchPeriode('genap')" 
                                    style="padding:7px 20px; border:none; border-radius:24px; font-weight:700; font-size:0.9rem; cursor:pointer; transition:all 0.2s ease; background:transparent; color:#64748b;">
                                    Genap
                                </button>
                            </div>

                            @if ($jadwal->isNotEmpty())
                                <button type="button" class="btn-add" onclick="openModalHapusSemua('jadwal_only')" 
                                    style="background:#dc2626; padding:7px 16px; font-size:0.875rem;" title="Hapus semua jadwal kuliah yang telah di-generate">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px; height:16px;">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                    Hapus Semua Jadwal
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Container Mode Ganjil (Semester 1, 3, 5, 7) --}}
                    <div id="container-ganjil" class="periode-container">
                        @foreach ($semesterGanjil as $sem)
                            @include('penjadwalan.partials.jadwal-semester', ['sem' => $sem, 'jadwal' => $jadwal])
                        @endforeach
                    </div>

                    {{-- Container Mode Genap (Semester 2, 4, 6, 8) --}}
                    <div id="container-genap" class="periode-container" style="display:none;">
                        @foreach ($semesterGenap as $sem)
                            @include('penjadwalan.partials.jadwal-semester', ['sem' => $sem, 'jadwal' => $jadwal])
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

{{-- ============ MODAL IMPORT MK (SMART IMPORTER WORKFLOW) ============ --}}
<div class="modal-backdrop" id="modalImportMk">
    <div class="modal-window" id="modalImportMkWindow" style="max-width: 600px; transition: max-width 0.3s ease;">
        <div class="modal-header-blue">
            <h3>Smart Import Mata Kuliah (Excel / Spreadsheet)</h3>
        </div>
        <div class="modal-body">
            <form action="{{ route('penjadwalan.import') }}" method="POST" enctype="multipart/form-data" class="modal-form" id="formImportSpreadsheet">
                @csrf
                <input type="hidden" name="verified_items" id="verifiedItemsInput">

                <div class="modal-form-row" style="align-items: flex-start;">
                    <label for="importFile" style="padding-top:8px;">File Spreadsheet</label>
                    <div class="modal-input-container" style="flex-direction:column; align-items:flex-start; gap:6px; width:100%;">
                        <input type="file" id="importFile" name="file" accept=".xlsx,.xls,.csv,.ods,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,application/vnd.oasis.opendocument.spreadsheet">
                        <div style="display:flex; justify-content:space-between; align-items:center; width:100%; flex-wrap:wrap; gap:8px;">
                            <span style="font-size:12px; color:#64748b;">Mendukung: Excel (.xlsx, .xls), CSV (.csv), dan ODS (.ods).</span>
                            <a href="{{ route('penjadwalan.template') }}" style="color:#0284c7; font-weight:600; font-size:12px; text-decoration:underline; display:inline-flex; align-items:center; gap:4px;" download>
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                Unduh Template Spreadsheet (.xlsx)
                            </a>
                        </div>
                    </div>
                </div>

                <div id="importPreviewStatus" style="display:none; padding:12px 14px; background:#f0f9ff; border:1px solid #bae6fd; border-radius:8px; font-size:13px; margin-top:12px;"></div>

                {{-- Interactive Smart Preview Section --}}
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
                        <div style="background:#f0f9ff; border:1px solid #bae6fd; border-radius:8px; padding:10px; text-align:center;">
                            <span style="font-size:11px; color:#0369a1; font-weight:600; text-transform:uppercase;">Dosen Baru</span>
                            <div id="badgeDosenBaru" style="font-size:18px; font-weight:700; color:#0284c7; margin-top:2px;">0</div>
                        </div>
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; flex-wrap:wrap; gap:8px;">
                        <span style="font-size:12.5px; color:#475569; font-weight:600;">
                            Pratinjau Data (Klik tombol Edit untuk mengoreksi data langsung sebelum disimpan):
                        </span>
                        <button type="button" onclick="tambahBarisManualPreview()" style="background:#f8fafc; color:#0369a1; border:1px solid #bae6fd; padding:5px 12px; border-radius:6px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:5px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            Tambah Baris
                        </button>
                    </div>

                    <div style="max-height: 320px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 12px;">
                        <table class="data-table" style="font-size: 12px; margin: 0; width:100%;">
                            <thead>
                                <tr style="background:#f8fafc; position:sticky; top:0; z-index:1;">
                                    <th style="padding:8px; width:95px;">Kode MK</th>
                                    <th style="padding:8px;">Nama Mata Kuliah</th>
                                    <th style="padding:8px; width:45px; text-align:center;">SKS</th>
                                    <th style="padding:8px; width:45px; text-align:center;">Sem</th>
                                    <th style="padding:8px; width:55px; text-align:center;">Kelas</th>
                                    <th style="padding:8px; width:120px;">Prodi</th>
                                    <th style="padding:8px; width:130px;">Dosen</th>
                                    <th style="padding:8px; width:75px; text-align:center;">Status</th>
                                    <th style="padding:8px; width:80px; text-align:center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="previewTableBody"></tbody>
                        </table>
                    </div>
                </div>

                <div id="importInfoDefault" style="padding:12px 14px; background:#f0f9ff; border:1px solid #bae6fd; border-radius:8px; font-size:13px; color:#0369a1; line-height:1.6; margin-top:8px;">
                    <strong>Smart Auto-Classification:</strong><br>
                    Sistem mendeteksi Sheet1 & Sheet2, mengklasifikasi MK Universitas (UNV), MK Fakultas (FST), MK Prodi, serta memetakan inisial dosen (MP, NH, dll.) secara otomatis.
                </div>

                <div class="modal-footer-buttons" style="margin-top:20px;">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalImportMk')">Batal</button>
                    <button type="submit" class="btn-modal-submit" id="btnSubmitImport">Import Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============ MODAL HAPUS SEMUA ============ --}}
<div class="modal-backdrop" id="modalHapusSemua">
    <div class="modal-window" style="max-width: 480px;">
        <div class="modal-header-blue" style="background:#dc2626;">
            <h3 id="modalHapusSemuaTitle">Konfirmasi Hapus Semua</h3>
        </div>
        <div class="modal-body">
            <div style="display:flex; align-items:flex-start; gap:14px; margin-bottom:18px;">
                <div style="width:40px; height:40px; border-radius:50%; background:#fee2e2; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </div>
                <div>
                    <p id="modalHapusSemuaDesc" style="color:#334155; font-size:14px; line-height:1.6; margin:0;">
                        Apakah Anda yakin ingin menghapus seluruh data? Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>
            </div>
            <form action="{{ route('penjadwalan.destroy-all') }}" method="POST" id="formHapusSemua">
                @csrf
                @method('DELETE')
                <input type="hidden" name="scope" id="hapusSemuaScope" value="all">
                <div class="modal-footer-buttons" style="margin-top:16px;">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalHapusSemua')">Batal</button>
                    <button type="submit" class="btn-modal-submit" style="background:#dc2626;" id="btnSubmitHapusSemua">Ya, Hapus Semua</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============ MODAL INPUT/EDIT MK ============ --}}
<div class="modal-backdrop" id="modalMk">
    <div class="modal-window">
        <div class="modal-header-blue">
            <h3 id="modalMkTitle">Tambah Mata Kuliah</h3>
        </div>
        <div class="modal-body">
            <form id="formMk" method="POST" class="modal-form">
                @csrf
                <input type="hidden" name="_method" id="mkMethod" value="">

                <div class="modal-form-row">
                    <label for="mkKode">Kode MK</label>
                    <div class="modal-input-container">
                        <input type="text" id="mkKode" name="kode_mk" required>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="mkNama">Nama MK</label>
                    <div class="modal-input-container">
                        <input type="text" id="mkNama" name="nama_mk" required>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="mkSks">SKS</label>
                    <div class="modal-input-container">
                        <input type="number" id="mkSks" name="sks" min="1" max="4" value="3" required>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="mkSemester">Semester</label>
                    <div class="modal-input-container">
                        <select id="mkSemester" name="semester" required>
                            <optgroup label="Semester Ganjil">
                                @foreach ($semesterGanjil as $s)
                                    <option value="{{ $s }}">Semester {{ $s }}</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Semester Genap">
                                @foreach ($semesterGenap as $s)
                                    <option value="{{ $s }}">Semester {{ $s }}</option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="mkTipe">Tipe</label>
                    <div class="modal-input-container">
                        <select id="mkTipe" name="tipe" required>
                            <option value="Wajib">Wajib</option>
                            <option value="Pilihan">Pilihan</option>
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="mkProdi">Prodi</label>
                    <div class="modal-input-container">
@if (($userRole ?? "") === "Prodi")
                        <input type="text" id="mkProdi" name="prodi" value="{{ $userProdi ?? "" }}" readonly style="background:#f8fafc; color:#334155; cursor:not-allowed;" required>
                    @else
                        <select id="mkProdi" name="prodi" required style="width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; background:#fff;">
                            @foreach ($daftarProdi as $dp)
                                <option value="{{ $dp->nama }}" @selected($loop->first)>{{ $dp->nama }}</option>
                            @endforeach
                        </select>
                    @endif
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="mkKetua">Dosen Ketua</label>
                    <div class="modal-input-container">
                        <select id="mkKetua" name="dosen_ketua_id">
                            <option value="">- Tidak ada -</option>
                            @php
                                $groupedDosens = $dosens->groupBy(fn($d) => $d->prodi ?: 'Umum / Lainnya');
                            @endphp
                            @foreach ($groupedDosens as $namaProdiGroup => $listDosenGroup)
                                <optgroup label="Prodi: {{ $namaProdiGroup }}">
                                    @foreach ($listDosenGroup as $d)
                                        <option value="{{ $d->id }}">{{ $d->nama }} ({{ $d->jabatan ?? 'Dosen' }})</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="mkAnggota">Dosen Anggota</label>
                    <div class="modal-input-container">
                        <select id="mkAnggota" name="dosen_anggota_id">
                            <option value="">- Tidak ada -</option>
                            @foreach ($groupedDosens as $namaProdiGroup => $listDosenGroup)
                                <optgroup label="Prodi: {{ $namaProdiGroup }}">
                                    @foreach ($listDosenGroup as $d)
                                        <option value="{{ $d->id }}">{{ $d->nama }} ({{ $d->jabatan ?? 'Dosen' }})</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="mkKelas">Jumlah Kelas</label>
                    <div class="modal-input-container">
                        <input type="number" id="mkKelas" name="jumlah_kelas" min="1" max="10" value="1" required>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="mkKapasitas">Kapasitas per Kelas</label>
                    <div class="modal-input-container">
                        <input type="number" id="mkKapasitas" name="kapasitas_per_kelas" min="1" max="200" value="30" required>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="mkLab">Perlu Laboratorium</label>
                    <div class="modal-input-container">
                        <select id="mkLab" name="butuh_lab">
                            <option value="0">Tidak</option>
                            <option value="1">Ya</option>
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="mkTanpaRuangan">Tanpa Ruangan</label>
                    <div class="modal-input-container">
                        <select id="mkTanpaRuangan" name="tanpa_ruangan">
                            <option value="0">Tidak (butuh ruang kelas)</option>
                            <option value="1">Ya (Skripsi / KKN / Magang)</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer-buttons">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalMk')">Batal</button>
                    <button type="submit" class="btn-modal-submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============ MODAL INPUT/EDIT RUANGAN ============ --}}
<div class="modal-backdrop" id="modalRuangan">
    <div class="modal-window">
        <div class="modal-header-blue">
            <h3 id="modalRuanganTitle">Tambah Ruangan</h3>
        </div>
        <div class="modal-body">
            <form id="formRuangan" method="POST" class="modal-form">
                @csrf
                <input type="hidden" name="_method" id="ruanganMethod" value="">

                <div class="modal-form-row">
                    <label for="ruanganKode">Kode Ruangan</label>
                    <div class="modal-input-container">
                        <input type="text" id="ruanganKode" name="kode" placeholder="Contoh: R01-FTTK, LAB-03" required>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="ruanganNama">Nama Ruangan</label>
                    <div class="modal-input-container">
                        <input type="text" id="ruanganNama" name="nama" placeholder="Contoh: Ruang Kelas 01 FTTK" required>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="ruanganTipe">Tipe Ruangan</label>
                    <div class="modal-input-container">
                        <select id="ruanganTipe" name="tipe" required>
                            <option value="Ruang Kelas">Ruang Kelas</option>
                            <option value="Lab">Laboratorium (Lab)</option>
                            <option value="Aula">Auditorium / Aula</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="ruanganKapasitas">Kapasitas (Kursi)</label>
                    <div class="modal-input-container">
                        <input type="number" id="ruanganKapasitas" name="kapasitas" min="1" max="500" value="50" required>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="ruanganGedung">Gedung</label>
                    <div class="modal-input-container">
                        <input type="text" id="ruanganGedung" name="gedung" placeholder="Contoh: Gedung FTTK / Utama">
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="ruanganLantai">Lantai</label>
                    <div class="modal-input-container">
                        <input type="text" id="ruanganLantai" name="lantai" placeholder="Contoh: 1, 2, atau 3">
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="ruanganIsActive">Status Aktif</label>
                    <div class="modal-input-container">
                        <select id="ruanganIsActive" name="is_active">
                            <option value="1">Aktif (Dipakai untuk generate jadwal)</option>
                            <option value="0">Nonaktif (Jangan dipakai generate)</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer-buttons">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalRuangan')">Batal</button>
                    <button type="submit" class="btn-modal-submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============ MODAL EDIT PROFILE ============ --}}
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
@endsection

@push('scripts')
<script>
const MK_FORM_ACTION_STORE = "{{ route('penjadwalan.store') }}";
const MK_FORM_ACTION_UPDATE = "{{ route('penjadwalan.update', ['mataKuliah' => '__ID__']) }}";
const RUANGAN_FORM_ACTION_STORE = "{{ route('ruangan.store') }}";
const RUANGAN_FORM_ACTION_UPDATE = "{{ route('ruangan.update', ['ruangan' => '__ID__']) }}";

let currentPeriode = 'ganjil';

function syncSemesterSelection(mode) {
    const inputs = document.querySelectorAll('#hiddenSemesterInputs input[type="checkbox"]');
    inputs.forEach(input => {
        const val = parseInt(input.value);
        if (mode === 'all') {
            input.checked = true;
        } else if (mode === 'ganjil') {
            input.checked = (val % 2 === 1);
        } else if (mode === 'genap') {
            input.checked = (val % 2 === 0);
        } else {
            input.checked = (val === parseInt(mode));
        }
    });
}

function toggleElement(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

function showTab(id, btn) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    
    const targetContent = document.getElementById(id);
    if (targetContent) targetContent.classList.add('active');
    
    const targetBtn = btn || document.querySelector(`.tab-btn[data-tab="${id}"]`);
    if (targetBtn) targetBtn.classList.add('active');

    // Simpan tab aktif di localStorage
    localStorage.setItem('penjadwalan_active_tab', id);
}

// Toggle Periode Ganjil / Genap pada Tab Jadwal Semester
function switchPeriode(mode, saveState = true) {
    currentPeriode = mode;
    const btnGanjil = document.getElementById('btnToggleGanjil');
    const btnGenap = document.getElementById('btnToggleGenap');
    const cGanjil = document.getElementById('container-ganjil');
    const cGenap = document.getElementById('container-genap');

    if (mode === 'ganjil') {
        btnGanjil.style.background = '#0860be';
        btnGanjil.style.color = '#ffffff';
        btnGanjil.style.boxShadow = '0 2px 4px rgba(8,96,190,0.25)';
        btnGenap.style.background = 'transparent';
        btnGenap.style.color = '#64748b';
        btnGenap.style.boxShadow = 'none';

        cGanjil.style.display = 'block';
        cGenap.style.display = 'none';
    } else {
        btnGenap.style.background = '#0860be';
        btnGenap.style.color = '#ffffff';
        btnGenap.style.boxShadow = '0 2px 4px rgba(8,96,190,0.25)';
        btnGanjil.style.background = 'transparent';
        btnGanjil.style.color = '#64748b';
        btnGanjil.style.boxShadow = 'none';

        cGenap.style.display = 'block';
        cGanjil.style.display = 'none';
    }

    updateFilterDropdown(mode);

    if (saveState) {
        localStorage.setItem('penjadwalan_periode', mode);
        const url = new URL(window.location);
        url.searchParams.set('periode', mode);
        window.history.replaceState({}, '', url);
    }
}

// Update opsi dropdown filter berdasarkan mode Ganjil/Genap
function updateFilterDropdown(mode) {
    const select = document.getElementById('filterSemesterSelect');
    if (!select) return;

    select.innerHTML = '<option value="all">Semua Semester</option>';
    const list = mode === 'ganjil' ? [1, 3, 5, 7] : [2, 4, 6, 8];

    list.forEach(sem => {
        const opt = document.createElement('option');
        opt.value = sem;
        opt.textContent = 'Semester ' + sem;
        select.appendChild(opt);
    });
    select.value = 'all';

    // Tampilkan kembali semua section di container aktif
    filterSemester('all');
}

// Filter semester tertentu di tab jadwal
function filterSemester(val) {
    const activeContainer = currentPeriode === 'ganjil' ? document.getElementById('container-ganjil') : document.getElementById('container-genap');
    if (!activeContainer) return;

    const sections = activeContainer.querySelectorAll('.semester-section');
    sections.forEach(sec => {
        if (val === 'all' || sec.getAttribute('data-semester') === String(val)) {
            sec.style.display = 'block';
        } else {
            sec.style.display = 'none';
        }
    });
}

// Filter prodi tertentu di tab jadwal
function filterProdi(val) {
    const rows = document.querySelectorAll('.jadwal-row-item');
    const filterLower = (val || 'all').toLowerCase();

    rows.forEach(r => {
        const rowProdi = (r.getAttribute('data-prodi') || '').toLowerCase();
        let show = true;
        if (filterLower !== 'all' && filterLower !== 'semua') {
            if (filterLower.includes('informatika') || filterLower === 'ti') {
                show = rowProdi.includes('informatika') && !rowProdi.includes('elektro');
            } else if (filterLower.includes('elektro') || filterLower === 'te') {
                show = rowProdi.includes('elektro') && !rowProdi.includes('informatika');
            } else {
                show = rowProdi.includes(filterLower);
            }
        }
        r.style.display = show ? '' : 'none';
    });

    // Update group counts per hari
    document.querySelectorAll('.hari-jadwal-group').forEach(grp => {
        const visibleRows = grp.querySelectorAll('.jadwal-row-item:not([style*="display: none"])');
        const badge = grp.querySelector('.hari-count-badge');
        if (badge) {
            badge.textContent = visibleRows.length + ' kelas';
        }
        grp.style.display = visibleRows.length > 0 ? '' : 'none';
    });

    // Update semester accordion headers
    document.querySelectorAll('.semester-section').forEach(sec => {
        const visibleRows = sec.querySelectorAll('.jadwal-row-item:not([style*="display: none"])');
        const sem = sec.getAttribute('data-semester');
        const badge = document.getElementById('accordion-badge-' + sem);
        if (badge) {
            badge.textContent = visibleRows.length > 0 ? 'Terjadwal (' + visibleRows.length + ')' : 'Kosong';
            if (visibleRows.length > 0) {
                badge.style.background = '#e0f2fe';
                badge.style.color = '#0284c7';
            } else {
                badge.style.background = '#f1f5f9';
                badge.style.color = '#94a3b8';
            }
        }
    });

    localStorage.setItem('penjadwalan_filter_prodi', val);
}

// Accordion Collapse / Expand per Semester
function toggleAccordion(sem) {
    const body = document.getElementById('accordion-body-' + sem);
    const chevron = document.getElementById('chevron-sem-' + sem);
    if (!body || !chevron) return;

    if (body.style.display === 'none') {
        body.style.display = 'block';
        chevron.style.transform = 'rotate(0deg)';
    } else {
        body.style.display = 'none';
        chevron.style.transform = 'rotate(-90deg)';
    }
}

// Update Checkbox Semester di Tab Generate Jadwal
function updateSemesterCheckboxes(mode) {
    const checkboxes = document.querySelectorAll('.sem-check-input');
    checkboxes.forEach(cb => {
        const val = parseInt(cb.value);
        if (mode === 'ganjil') {
            cb.checked = (val % 2 === 1);
        } else if (mode === 'genap') {
            cb.checked = (val % 2 === 0);
        } else if (mode === 'keduanya') {
            cb.checked = true;
        }
    });
}

// Inisialisasi State Saat Halaman Dimuat
document.addEventListener('DOMContentLoaded', function() {
    // 1. Cek parameter session flash (setelah generate) atau URL query atau localStorage
    const serverTab = "{{ session('tab_aktif') }}";
    const serverPeriode = "{{ session('periode_aktif') }}";
    const serverProdi = "{{ session('prodi_aktif') }}";
    const urlParams = new URLSearchParams(window.location.search);
    const queryPeriode = urlParams.get('periode');
    const localPeriode = localStorage.getItem('penjadwalan_periode');
    const localTab = localStorage.getItem('penjadwalan_active_tab');
    const localProdi = localStorage.getItem('penjadwalan_filter_prodi');

    // Tentukan filter prodi awal
    const prodiSelect = document.getElementById('filterProdiSelect');
    if (prodiSelect) {
        if (serverProdi && serverProdi !== 'all') {
            prodiSelect.value = serverProdi;
            filterProdi(serverProdi);
            localStorage.setItem('penjadwalan_filter_prodi', serverProdi);
        } else if (localProdi) {
            prodiSelect.value = localProdi;
            filterProdi(localProdi);
        }
    }

    // Tentukan periode awal
    let targetPeriode = 'ganjil';
    if (serverPeriode && (serverPeriode === 'ganjil' || serverPeriode === 'genap')) {
        targetPeriode = serverPeriode;
    } else if (queryPeriode && (queryPeriode === 'ganjil' || queryPeriode === 'genap')) {
        targetPeriode = queryPeriode;
    } else if (localPeriode && (localPeriode === 'ganjil' || localPeriode === 'genap')) {
        targetPeriode = localPeriode;
    }
    switchPeriode(targetPeriode, true);

    // Tentukan tab awal
    let targetTab = 'tab-jadwal';
    if (serverTab && document.getElementById(serverTab)) {
        targetTab = serverTab;
    } else if (localTab && document.getElementById(localTab) && localTab !== 'tab-generate') {
        targetTab = localTab;
    }
    showTab(targetTab);

    // Disabled state saat proses generate berjalan
    const formGenerateEl = document.getElementById('formGenerate');
    if (formGenerateEl) {
        formGenerateEl.addEventListener('submit', function() {
            const btn = document.getElementById('btnSubmitGenerate');
            if (btn) {
                setTimeout(function() {
                    btn.disabled = true;
                }, 10);
            }
        });
    }
});

// Modal Tambah & Edit Mata Kuliah
function openTambahMk() {
    const form = document.getElementById('formMk');
    form.action = MK_FORM_ACTION_STORE;
    form.reset();
    document.getElementById('mkMethod').value = '';
    document.getElementById('modalMkTitle').textContent = 'Tambah Mata Kuliah';
    document.getElementById('mkSemester').value = '1';
    document.getElementById('mkSks').value = '3';
    document.getElementById('mkKelas').value = '1';
    document.getElementById('mkKapasitas').value = '30';
    const mkProdiEl = document.getElementById('mkProdi'); if (mkProdiEl && mkProdiEl.tagName === 'INPUT') mkProdiEl.value = mkProdiEl.value || '';
    openModal('modalMk');
}

// Modal Tambah & Edit Ruangan
function openTambahRuangan() {
    const form = document.getElementById('formRuangan');
    form.action = RUANGAN_FORM_ACTION_STORE;
    form.reset();
    document.getElementById('ruanganMethod').value = '';
    document.getElementById('modalRuanganTitle').textContent = 'Tambah Ruangan';
    document.getElementById('ruanganKode').readOnly = false;
    document.getElementById('ruanganKapasitas').value = '40';
    document.getElementById('ruanganTipe').value = 'Ruang Kelas';
    document.getElementById('ruanganIsActive').value = '1';
    openModal('modalRuangan');
}

function openEditRuangan(r) {
    const form = document.getElementById('formRuangan');
    form.action = RUANGAN_FORM_ACTION_UPDATE.replace('__ID__', r.id);
    document.getElementById('ruanganMethod').value = 'PUT';
    document.getElementById('modalRuanganTitle').textContent = 'Edit Ruangan ' + r.kode;
    document.getElementById('ruanganKode').value = r.kode;
    document.getElementById('ruanganNama').value = r.nama;
    document.getElementById('ruanganTipe').value = r.tipe;
    document.getElementById('ruanganKapasitas').value = r.kapasitas;
    document.getElementById('ruanganGedung').value = r.gedung || '';
    document.getElementById('ruanganLantai').value = r.lantai || '';
    document.getElementById('ruanganIsActive').value = r.is_active ? '1' : '0';
    openModal('modalRuangan');
}

function konfirmasiHapusRuangan(event, kode) {
    if (!confirm('Yakin ingin menghapus ruangan ' + kode + '?')) {
        event.preventDefault();
        return false;
    }
    return true;
}

function openEditMk(mk) {
    const form = document.getElementById('formMk');
    form.action = MK_FORM_ACTION_UPDATE.replace('__ID__', mk.id);
    document.getElementById('mkMethod').value = 'PUT';
    document.getElementById('modalMkTitle').textContent = 'Edit ' + mk.kode_mk;

    document.getElementById('mkKode').value = mk.kode_mk;
    document.getElementById('mkNama').value = mk.nama_mk;
    document.getElementById('mkSks').value = mk.sks;
    document.getElementById('mkSemester').value = mk.semester;
    document.getElementById('mkTipe').value = mk.tipe;
    document.getElementById('mkProdi').value = mk.prodi;
    document.getElementById('mkKetua').value = mk.dosen_ketua_id || '';
    document.getElementById('mkAnggota').value = mk.dosen_anggota_id || '';
    document.getElementById('mkKelas').value = mk.jumlah_kelas;
    document.getElementById('mkKapasitas').value = mk.kapasitas_per_kelas;
    document.getElementById('mkLab').value = mk.butuh_lab ? '1' : '0';
    document.getElementById('mkTanpaRuangan').value = mk.tanpa_ruangan ? '1' : '0';

    openModal('modalMk');
}

function konfirmasiHapus(event, kode) {
    if (!confirm('Yakin ingin menghapus mata kuliah ' + kode + '?')) {
        event.preventDefault();
        return false;
    }
    return true;
}

function openModalHapusSemua(scope) {
    const scopeInput = document.getElementById('hapusSemuaScope');
    const titleEl = document.getElementById('modalHapusSemuaTitle');
    const descEl = document.getElementById('modalHapusSemuaDesc');

    if (scope === 'jadwal_only') {
        scopeInput.value = 'jadwal_only';
        titleEl.textContent = 'Hapus Semua Hasil Jadwal';
        descEl.innerHTML = 'Apakah Anda yakin ingin <strong>menghapus semua hasil jadwal kuliah yang telah di-generate</strong>? Data mata kuliah tetap aman tersimpan.';
    } else {
        scopeInput.value = 'all';
        titleEl.textContent = 'Hapus Semua Data Mata Kuliah dan Jadwal ?';
        descEl.innerHTML = 'Apakah Anda yakin ingin <strong>menghapus seluruh data mata kuliah beserta seluruh jadwal</strong>? Semua baris data akan dibersihkan dan tindakan ini tidak dapat dibatalkan.';
    }

    openModal('modalHapusSemua');
}

// ==========================================================================
// Smart Preview & Interactive CRUD Editing Sebelum Import
// ==========================================================================
window.previewItemsList = [];

function updatePreviewSummary() {
    const verifiedInput = document.getElementById('verifiedItemsInput');
    const submitBtn = document.getElementById('btnSubmitImport');
    const total = window.previewItemsList.length;

    let dikenali = 0, duplikat = 0, perluVerifikasi = 0, dosenBaru = 0;
    const seenCodes = {};

    window.previewItemsList.forEach(item => {
        const fullKode = (item.kode_mk || '').trim().toUpperCase();
        if (seenCodes[fullKode]) {
            item.status_verifikasi = 'duplikat';
            duplikat++;
        } else {
            seenCodes[fullKode] = true;
            if (item.status_verifikasi === 'perlu_verifikasi' || !item.kode_mk || !item.nama_mk || !item.semester) {
                item.status_verifikasi = 'perlu_verifikasi';
                perluVerifikasi++;
            } else {
                item.status_verifikasi = 'dikenali';
                dikenali++;
            }
        }
        if (item.dosen_baru) dosenBaru++;
    });

    if (document.getElementById('badgeTotal')) document.getElementById('badgeTotal').textContent = total;
    if (document.getElementById('badgeDikenali')) document.getElementById('badgeDikenali').textContent = dikenali;
    if (document.getElementById('badgeDuplikat')) document.getElementById('badgeDuplikat').textContent = duplikat;
    if (document.getElementById('badgePerluVerifikasi')) document.getElementById('badgePerluVerifikasi').textContent = perluVerifikasi;
    if (document.getElementById('badgeDosenBaru')) document.getElementById('badgeDosenBaru').textContent = dosenBaru;

    if (verifiedInput) {
        verifiedInput.value = JSON.stringify(window.previewItemsList);
    }
    if (submitBtn) {
        submitBtn.textContent = 'Konfirmasi & Simpan (' + total + ' MK)';
    }
}

function renderInteractivePreview() {
    const tbody = document.getElementById('previewTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (window.previewItemsList.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:16px; color:#64748b; font-style:italic;">Tidak ada data mata kuliah. Silakan pilih file spreadsheet atau klik Tambah Baris.</td></tr>';
        return;
    }

    const itemsToShow = window.previewItemsList.slice(0, 100);
    itemsToShow.forEach((item, index) => {
        const tr = document.createElement('tr');
        const rawKode = item.kode_mk || '-';
        const cleanKode = rawKode.replace(/-[A-Za-z0-9]+$/, '');
        const matchCls = rawKode.match(/-([A-Za-z0-9]+)$/);
        const itemKelas = item.kelas || (matchCls ? matchCls[1].toUpperCase() : 'A');

        if (item._isEditing) {
            tr.style.background = '#fefce8';
            tr.innerHTML = `
                <td style="padding:4px;"><input type="text" id="edit_kode_${index}" value="${cleanKode}" style="width:100%; padding:3px 6px; font-size:11.5px; font-weight:600; border:1px solid #cbd5e1; border-radius:4px;"></td>
                <td style="padding:4px;"><input type="text" id="edit_nama_${index}" value="${item.nama_mk || ''}" style="width:100%; padding:3px 6px; font-size:11.5px; border:1px solid #cbd5e1; border-radius:4px;"></td>
                <td style="padding:4px;"><input type="number" min="1" max="6" id="edit_sks_${index}" value="${item.sks || 3}" style="width:100%; padding:3px 4px; font-size:11.5px; text-align:center; border:1px solid #cbd5e1; border-radius:4px;"></td>
                <td style="padding:4px;"><input type="number" min="1" max="8" id="edit_sem_${index}" value="${item.semester || 1}" style="width:100%; padding:3px 4px; font-size:11.5px; text-align:center; border:1px solid #cbd5e1; border-radius:4px;"></td>
                <td style="padding:4px;"><input type="text" maxlength="3" id="edit_kelas_${index}" value="${itemKelas}" style="width:100%; padding:3px 4px; font-size:11.5px; text-align:center; text-transform:uppercase; font-weight:700; border:1px solid #cbd5e1; border-radius:4px;"></td>
                <td style="padding:4px;"><input type="text" id="edit_prodi_${index}" value="${item.prodi || 'Informatika'}" style="width:100%; padding:3px 6px; font-size:11.5px; border:1px solid #cbd5e1; border-radius:4px;"></td>
                <td style="padding:4px;"><input type="text" id="edit_dosen_${index}" value="${item.dosen_ketua || ''}" placeholder="Dosen Pengampu" style="width:100%; padding:3px 6px; font-size:11.5px; border:1px solid #cbd5e1; border-radius:4px;"></td>
                <td style="padding:4px; text-align:center;"><span style="background:#fef08a; color:#854d0e; padding:2px 6px; border-radius:4px; font-size:11px;">Edit</span></td>
                <td style="padding:4px; text-align:center; white-space:nowrap;">
                    <button type="button" onclick="savePreviewItem(${index})" style="background:#16a34a; color:#fff; border:none; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:700; cursor:pointer;" title="Simpan Perubahan">âœ“</button>
                    <button type="button" onclick="cancelPreviewItem(${index})" style="background:#e2e8f0; color:#475569; border:none; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:700; cursor:pointer; margin-left:3px;" title="Batal">âœ•</button>
                </td>
            `;
        } else {
            const statusBadge = item.status_verifikasi === 'duplikat'
                ? '<span style="background:#fef08a; color:#854d0e; padding:2px 6px; border-radius:4px; font-size:11px;">Duplikat</span>'
                : (item.status_verifikasi === 'perlu_verifikasi'
                    ? '<span style="background:#fed7aa; color:#9a3412; padding:2px 6px; border-radius:4px; font-size:11px;">Verifikasi</span>'
                    : '<span style="background:#bbf7d0; color:#166534; padding:2px 6px; border-radius:4px; font-size:11px;">OK</span>');

            tr.innerHTML = `
                <td style="padding:6px 8px; font-weight:600; white-space:nowrap;">${cleanKode}</td>
                <td style="padding:6px 8px;">${item.nama_mk || '-'}</td>
                <td style="padding:6px 8px; text-align:center;">${item.sks || 0}</td>
                <td style="padding:6px 8px; text-align:center;">${item.semester || 1}</td>
                <td style="padding:6px 8px; text-align:center;"><span style="display:inline-block; padding:2px 6px; background:#dbeafe; color:#1e40af; border-radius:10px; font-weight:700; font-size:11px;">${itemKelas}</span></td>
                <td style="padding:6px 8px;">${item.prodi || '-'}</td>
                <td style="padding:6px 8px; font-size:11.5px; color:#334155;">${item.dosen_ketua || '-'}</td>
                <td style="padding:6px 8px; text-align:center;">${statusBadge}</td>
                <td style="padding:6px 8px; text-align:center; white-space:nowrap;">
                    <button type="button" onclick="editPreviewItem(${index})" style="background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center;" title="Edit baris ini">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </button>
                    <button type="button" onclick="deletePreviewItem(${index})" style="background:#fef2f2; color:#dc2626; border:1px solid #fecaca; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:600; cursor:pointer; margin-left:3px; display:inline-flex; align-items:center;" title="Hapus baris ini">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                </td>
            `;
        }
        tbody.appendChild(tr);
    });

    if (window.previewItemsList.length > 100) {
        const trMore = document.createElement('tr');
        trMore.innerHTML = `<td colspan="9" style="text-align:center; padding:8px; color:#64748b; font-style:italic;">... dan ${(window.previewItemsList.length - 100)} data lainnya</td>`;
        tbody.appendChild(trMore);
    }
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

    const kode = (document.getElementById(`edit_kode_${index}`)?.value || '').trim().toUpperCase();
    const nama = (document.getElementById(`edit_nama_${index}`)?.value || '').trim();
    const sks = parseInt(document.getElementById(`edit_sks_${index}`)?.value || '3', 10);
    const semester = parseInt(document.getElementById(`edit_sem_${index}`)?.value || '1', 10);
    const kelas = (document.getElementById(`edit_kelas_${index}`)?.value || 'A').trim().toUpperCase();
    const prodi = (document.getElementById(`edit_prodi_${index}`)?.value || 'Informatika').trim();
    const dosen = (document.getElementById(`edit_dosen_${index}`)?.value || '').trim();

    if (!kode || !nama) {
        alert('Kode MK dan Nama Mata Kuliah wajib diisi.');
        return;
    }

    item.kode_mk = kelas ? `${kode}-${kelas}` : kode;
    item.kode_induk = kode;
    item.nama_mk = nama;
    item.sks = Math.max(1, Math.min(6, sks));
    item.semester = Math.max(1, Math.min(8, semester));
    item.kelas = kelas;
    item.prodi = prodi;
    item.dosen_ketua = dosen || null;
    item._isEditing = false;
    delete item._isNew;

    updatePreviewSummary();
    renderInteractivePreview();
}

function deletePreviewItem(index) {
    if (!confirm('Hapus baris mata kuliah ini dari pratinjau import?')) return;
    window.previewItemsList.splice(index, 1);
    updatePreviewSummary();
    renderInteractivePreview();
}

function tambahBarisManualPreview() {
    window.previewItemsList.unshift({
        kode_mk: '',
        kode_induk: '',
        nama_mk: '',
        sks: 3,
        semester: 1,
        kelas: 'A',
        tipe: 'Wajib',
        prodi: 'Informatika',
        dosen_ketua: '',
        status_verifikasi: 'dikenali',
        _isEditing: true,
        _isNew: true,
    });
    renderInteractivePreview();
}

// Disabled state saat submit impor berjalan & Smart Preview
document.addEventListener('DOMContentLoaded', function() {
    const formImportEl = document.getElementById('formImportSpreadsheet');
    const importFileInput = document.getElementById('importFile');
    const modalWindow = document.getElementById('modalImportMkWindow');
    const previewContainer = document.getElementById('importPreviewContainer');
    const previewStatus = document.getElementById('importPreviewStatus');
    const infoDefault = document.getElementById('importInfoDefault');

    if (importFileInput) {
        importFileInput.addEventListener('change', function() {
            if (!this.files || !this.files[0]) return;

            const file = this.files[0];
            const formData = new FormData();
            formData.append('file', file);

            if (previewStatus) {
                previewStatus.style.display = 'block';
                previewStatus.innerHTML = '<div style="display:flex; align-items:center; gap:8px; color:#0369a1;"><strong>Menganalisis file Excel...</strong> (Sheet1, Sheet2, relasi dosen, dan klasifikasi MK)</div>';
            }
            if (previewContainer) previewContainer.style.display = 'none';

            fetch('{{ route('penjadwalan.import.preview') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => res.json())
            .then(response => {
                if (response.success && response.data) {
                    const data = response.data;
                    if (modalWindow) modalWindow.style.maxWidth = '980px';
                    if (previewStatus) previewStatus.style.display = 'none';
                    if (infoDefault) infoDefault.style.display = 'none';
                    if (previewContainer) previewContainer.style.display = 'block';

                    window.previewItemsList = (data.items || []).map(item => ({ ...item, _isEditing: false }));
                    updatePreviewSummary();
                    renderInteractivePreview();
                } else {
                    if (previewStatus) {
                        previewStatus.innerHTML = '<div style="color:#b91c1c;">Gagal membaca pratinjau: ' + (response.message || 'Format tidak sesuai') + '. Anda tetap bisa klik Import Sekarang.</div>';
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

    if (formImportEl) {
        formImportEl.addEventListener('submit', function() {
            const btn = document.getElementById('btnSubmitImport');
            if (btn) {
                setTimeout(function() {
                    btn.disabled = true;
                    btn.textContent = 'Memproses Impor...';
                }, 10);
            }
        });
    }

    // Pulihkan status tampilan log jika pengguna sebelumnya membukanya
    if (localStorage.getItem('penjadwalan_log_open') === '1') {
        const panel = document.getElementById('containerLogGenerate');
        if (panel && panel.style.display === 'none') {
            toggleLogGenerate(false);
        }
    }
});

// ==========================================================================
// Log & Diagnosa Penjadwalan - Toggle and Interactive Filtering
// ==========================================================================
function toggleLogGenerate(persist = true) {
    const panel = document.getElementById('containerLogGenerate');
    const box = document.getElementById('logGenerateBox');
    const btn = document.getElementById('btnToggleLogGenerate');
    const text = document.getElementById('textToggleLog');
    
    if (!panel) return;
    
    const isHidden = panel.style.display === 'none';
    if (isHidden) {
        panel.style.display = 'block';
        if (box) box.classList.add('expanded');
        if (btn) btn.classList.add('active');
        if (text) text.textContent = 'Tutup Log & Diagnosa';
        if (persist) localStorage.setItem('penjadwalan_log_open', '1');
    } else {
        panel.style.display = 'none';
        if (box) box.classList.remove('expanded');
        if (btn) btn.classList.remove('active');
        if (text) text.textContent = 'Buka Log & Diagnosa';
        if (persist) localStorage.setItem('penjadwalan_log_open', '0');
    }
}

function applyLogFilter(filterType, btn) {
    document.querySelectorAll('.log-filter-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    const blocks = document.querySelectorAll('.log-semester-block');
    blocks.forEach(block => {
        const itemGagal = block.querySelector('.log-item-gagal');
        const itemSukses = block.querySelector('.log-item-sukses');
        const itemWarning = block.querySelector('.log-item-warning');

        if (filterType === 'all') {
            if (itemGagal) itemGagal.style.display = '';
            if (itemSukses) itemSukses.style.display = '';
            if (itemWarning) itemWarning.style.display = '';
            block.style.display = '';
        } else if (filterType === 'gagal') {
            if (itemGagal) itemGagal.style.display = '';
            if (itemSukses) itemSukses.style.display = 'none';
            if (itemWarning) itemWarning.style.display = 'none';
            block.style.display = itemGagal ? '' : 'none';
        } else if (filterType === 'sukses') {
            if (itemGagal) itemGagal.style.display = 'none';
            if (itemSukses) itemSukses.style.display = '';
            if (itemWarning) itemWarning.style.display = 'none';
            block.style.display = itemSukses ? '' : 'none';
        } else if (filterType === 'warning') {
            if (itemGagal) itemGagal.style.display = 'none';
            if (itemSukses) itemSukses.style.display = 'none';
            if (itemWarning) itemWarning.style.display = '';
            block.style.display = itemWarning ? '' : 'none';
        }
    });
}

function filterLogBySemester(semester) {
    const blocks = document.querySelectorAll('.log-semester-block');
    blocks.forEach(block => {
        const sem = block.getAttribute('data-semester');
        if (semester === 'all' || sem === semester) {
            block.style.display = '';
        } else {
            block.style.display = 'none';
        }
    });
}
</script>
@endpush


