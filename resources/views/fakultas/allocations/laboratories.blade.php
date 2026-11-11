@extends('layouts.app')

@section('title', 'APJAD - Alokasi Laboratorium ke Program Studi')

@section('content')
<div class="admin-layout">
    @include('fakultas.sidebar', ['active' => 'allocations_laboratories'])

    <main class="admin-main">
        <header class="admin-header">
            <div class="user-badge">
                <span>{{ Auth::user()->role }}, <span class="user-name">{{ Auth::user()->nama }}</span> ({{ Auth::user()->fakultas->kode ?? 'FTTK' }})</span>
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

            @if ($errors->any())
                <div class="flash-alert error" style="background:#fee2e2; color:#b91c1c; border-left:4px solid #ef4444; padding:12px 16px; border-radius:6px; margin-bottom:20px;">
                    <ul style="margin:0; padding-left:18px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
                <div>
                    <h1 class="page-title" style="margin-bottom:4px;">Alokasi Laboratorium ke Program Studi</h1>
                    <p style="color:#64748b; font-size:14px; margin:0;">
                        Relasi Many-to-Many: Satu laboratorium milik Fakultas dapat dialokasikan ke beberapa Program Studi (contoh: Lab TI &rarr; TI, Lab Energi &rarr; TE).
                    </p>
                </div>
                <button type="button" onclick="openModal('modalAddLabAllocation')" style="display:inline-flex; align-items:center; gap:8px; background:#0860be; color:#fff; padding:10px 18px; border-radius:6px; border:none; font-weight:600; cursor:pointer; font-size:14px; box-shadow:0 2px 6px rgba(8,96,190,0.25);">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>Tambah Alokasi Laboratorium</span>
                </button>
            </div>

            {{-- Filter Bar Alokasi Laboratorium --}}
            <div class="card" style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:16px; margin-bottom:20px;">
                <form action="{{ route('fakultas.allocations.laboratories') }}" method="GET" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
                    <select name="laboratory_id" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; min-width:200px;">
                        <option value="">Semua Laboratorium</option>
                        @foreach($laboratories as $l)
                            <option value="{{ $l->id }}" {{ (string) request('laboratory_id') === (string) $l->id ? 'selected' : '' }}>
                                {{ $l->name }} ({{ $l->lab_type }})
                            </option>
                        @endforeach
                    </select>

                    <select name="study_program_id" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; min-width:220px;">
                        <option value="">Semua Program Studi</option>
                        @foreach($prodis as $p)
                            <option value="{{ $p->id }}" {{ (string) request('study_program_id') === (string) $p->id ? 'selected' : '' }}>
                                {{ $p->kode }} - {{ $p->nama }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit" style="padding:8px 16px; background:#0f172a; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer;">Filter</button>
                    <a href="{{ route('fakultas.allocations.laboratories') }}" style="padding:8px 14px; background:#f1f5f9; color:#475569; text-decoration:none; border-radius:6px; font-size:13px; font-weight:500;">Reset</a>
                </form>
            </div>

            {{-- Ringkasan Laboratorium dan Prodi yang Dialokasikan --}}
            <div class="card" style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:18px; margin-bottom:24px;">
                <h3 style="margin:0 0 12px; font-size:15px; font-weight:600; color:#1e293b;">Daftar Laboratorium & Program Studi Pengguna</h3>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:12px;">
                    @foreach($laboratories as $l)
                        <div style="border:1px solid #e2e8f0; border-radius:8px; padding:12px; background:#f8fafc;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                <strong style="font-size:14px; color:#0f172a;">{{ $l->name }}</strong>
                                <span style="font-size:11px; padding:2px 8px; border-radius:10px; background:#f3e8ff; color:#7e22ce; font-weight:600;">
                                    {{ $l->lab_type }} ({{ $l->capacity }} PC)
                                </span>
                            </div>
                            <div style="font-size:12px; color:#64748b; margin-bottom:6px;">
                                Alokasi Prodi:
                            </div>
                            <div style="display:flex; flex-wrap:wrap; gap:6px;">
                                @forelse($l->studyPrograms as $sp)
                                    <span style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:4px; font-size:11px; background:#fff; border:1px solid #cbd5e1; font-weight:600; color:#334155;">
                                        {{ $sp->kode }} - {{ $sp->nama }}
                                    </span>
                                @empty
                                    <span style="color:#94a3b8; font-size:11px; font-style:italic;">Belum dialokasikan ke Prodi</span>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Table Alokasi Lab --}}
            <div class="card" style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden;">
                <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0;">
                    <h3 style="margin:0; font-size:15px; font-weight:600; color:#1e293b;">Tabel Alokasi Laboratorium (Junction)</h3>
                </div>
                <div class="table-responsive">
                    <table class="data-table" style="width:100%; border-collapse:collapse; font-size:13px;">
                        <thead>
                            <tr style="background:#f8fafc; text-align:left; border-bottom:1px solid #e2e8f0;">
                                <th style="padding:12px 16px;">Laboratorium</th>
                                <th style="padding:12px 16px;">Tipe Lab</th>
                                <th style="padding:12px 16px;">Kapasitas</th>
                                <th style="padding:12px 16px;">Program Studi Dialokasikan</th>
                                <th style="padding:12px 16px;">Tanggal Alokasi</th>
                                <th style="padding:12px 16px; text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($allocations as $a)
                                <tr style="border-bottom:1px solid #f1f5f9;">
                                    <td style="padding:12px 16px; font-weight:700; color:#1e293b;">
                                        {{ $a->laboratory->name ?? '-' }}
                                    </td>
                                    <td style="padding:12px 16px;">
                                        <span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:600; background:#f3e8ff; color:#7e22ce;">
                                            {{ $a->laboratory->lab_type ?? '-' }}
                                        </span>
                                    </td>
                                    <td style="padding:12px 16px;">
                                        {{ $a->laboratory->capacity ?? '-' }} Peserta
                                    </td>
                                    <td style="padding:12px 16px;">
                                        <span style="display:inline-block; padding:3px 10px; border-radius:4px; font-size:12px; font-weight:600; background:#e0f2fe; color:#0369a1;">
                                            {{ $a->studyProgram->kode ?? '-' }} &bull; {{ $a->studyProgram->nama ?? '-' }}
                                        </span>
                                    </td>
                                    <td style="padding:12px 16px; color:#64748b; font-size:12px;">
                                        {{ $a->created_at ? $a->created_at->format('d M Y H:i') : '-' }}
                                    </td>
                                    <td style="padding:12px 16px; text-align:right;">
                                        <form action="{{ route('fakultas.allocations.laboratories.destroy', $a) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus alokasi {{ $a->laboratory->name ?? '' }} untuk {{ $a->studyProgram->kode ?? '' }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="redirect_to" value="allocations_page">
                                            <button type="submit" class="btn-action delete-btn" title="Hapus Alokasi">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="padding:24px; text-align:center; color:#94a3b8;">
                                        Belum ada data alokasi laboratorium ke Program Studi.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($allocations->hasPages())
                    <div style="padding:16px; border-top:1px solid #e2e8f0;">
                        {{ $allocations->links() }}
                    </div>
                @endif
            </div>
        </section>
    </main>
</div>

{{-- Modal Tambah Alokasi Lab --}}
<div id="modalAddLabAllocation" class="modal-backdrop">
    <div class="modal-window">
        <div class="modal-header-blue">
            <h3>Tambah Alokasi Laboratorium ke Prodi</h3>
        </div>
        <div class="modal-body">
            <form action="{{ route('fakultas.allocations.laboratories.store') }}" method="POST" class="modal-form">
                @csrf
                <input type="hidden" name="redirect_to" value="allocations_page">

                <div class="modal-form-row">
                    <label>Pilih Laboratorium <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <select name="laboratory_id" required>
                            <option value="">-- Pilih Laboratorium (ACTIVE) --</option>
                            @foreach($activeLabs ?? $laboratories as $l)
                                <option value="{{ $l->id }}">{{ $l->name }} ({{ $l->lab_type }}, Kapasitas: {{ $l->capacity }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>Program Studi <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <div style="display:flex; flex-direction:column; gap:8px; padding:10px; border:1px solid #cbd5e1; border-radius:6px; background:#f8fafc;">
                            @foreach($prodis as $p)
                                <label style="display:flex; align-items:center; gap:8px; font-weight:500; font-size:13px; cursor:pointer; color:#1e293b;">
                                    <input type="checkbox" name="study_program_ids[]" value="{{ $p->id }}" style="width:16px; height:16px; cursor:pointer;">
                                    <span><strong>{{ $p->kode }}</strong> — {{ $p->nama }}</span>
                                </label>
                            @endforeach
                        </div>
                        <small style="color:#64748b; font-size:11px; display:block; margin-top:4px;">Dapat memilih 1 atau beberapa Program Studi aktif sekaligus (Many-to-Many).</small>
                    </div>
                </div>

                <div class="modal-footer-buttons" style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalAddLabAllocation')">Batal</button>
                    <button type="submit" class="btn-modal-submit" style="background:#9333ea; color:#fff; border:none; padding:8px 18px; border-radius:6px; font-weight:600; cursor:pointer;">Simpan Alokasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openModal(id) {
    const m = document.getElementById(id);
    if (m) {
        m.classList.add('show');
        m.style.display = 'flex';
    }
}
function closeModal(id) {
    const m = document.getElementById(id);
    if (m) {
        m.classList.remove('show');
        m.style.display = 'none';
    }
}
document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('modal-backdrop')) {
        closeModal(e.target.id);
    }
});
</script>
@endsection
