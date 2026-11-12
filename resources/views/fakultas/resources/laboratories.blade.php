@extends('layouts.app')

@section('title', 'APJAD - Laboratorium Fakultas')

@section('content')
<div class="admin-layout">
    @include('partials.smart-sidebar', ['active' => 'laboratories', 'activeFakultas' => 'laboratories'])

    <main class="admin-main">
        <header class="admin-header">
            @include('partials.smart-user-badge')
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
                    <h1 class="page-title" style="margin-bottom:4px;">Daftar Laboratorium Fakultas</h1>
                    <p style="color:#64748b; font-size:14px; margin:0;">
                        Owner Resource: <strong>{{ Auth::user()->fakultas->nama ?? 'Fakultas' }}</strong> &bull; Kelola laboratorium praktikum dan kapasitasnya.
                    </p>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <a href="{{ route('fakultas.allocations.laboratories') }}" style="display:inline-flex; align-items:center; gap:8px; background:#f3e8ff; color:#7e22ce; padding:10px 16px; border-radius:6px; border:1px solid #e9d5ff; font-weight:600; text-decoration:none; font-size:13.5px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="18" cy="5" r="3"></circle>
                            <circle cx="6" cy="12" r="3"></circle>
                            <circle cx="18" cy="19" r="3"></circle>
                            <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                            <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                        </svg>
                        <span>Kelola Alokasi Laboratorium ke Prodi</span>
                    </a>
                    <button type="button" onclick="openModal('modalAddLab')" style="display:inline-flex; align-items:center; gap:8px; background:#0860be; color:#fff; padding:10px 18px; border-radius:6px; border:none; font-weight:600; cursor:pointer; font-size:14px; box-shadow:0 2px 6px rgba(8,96,190,0.25);">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        <span>Tambah Laboratorium</span>
                    </button>
                </div>
            </div>

            {{-- Filter & Search Lab --}}
            <div class="card" style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:16px; margin-bottom:20px;">
                <form action="{{ route('fakultas.laboratories.index') }}" method="GET" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama laboratorium..." style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; min-width:220px;">

                    <select name="lab_type" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                        <option value="">Semua Jenis Lab</option>
                        @foreach($labTypes ?? [] as $lt)
                            <option value="{{ $lt }}" {{ request('lab_type') === $lt ? 'selected' : '' }}>{{ $lt }}</option>
                        @endforeach
                    </select>

                    <select name="status" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                        <option value="">Semua Status</option>
                        <option value="ACTIVE" {{ request('status') === 'ACTIVE' ? 'selected' : '' }}>ACTIVE (Aktif)</option>
                        <option value="INACTIVE" {{ request('status') === 'INACTIVE' ? 'selected' : '' }}>INACTIVE (Nonaktif)</option>
                    </select>

                    <button type="submit" style="padding:8px 16px; background:#0f172a; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer;">Filter</button>
                    <a href="{{ route('fakultas.laboratories.index') }}" style="padding:8px 14px; background:#f1f5f9; color:#475569; text-decoration:none; border-radius:6px; font-size:13px; font-weight:500;">Reset</a>
                </form>
            </div>

            {{-- Table Lab --}}
            <div class="card" style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden;">
                <div class="table-responsive">
                    <table class="data-table" style="width:100%; border-collapse:collapse; font-size:13px;">
                        <thead>
                            <tr style="background:#f8fafc; text-align:left; border-bottom:1px solid #e2e8f0;">
                                <th style="padding:12px 16px;">Nama Laboratorium</th>
                                <th style="padding:12px 16px;">Jenis / Tipe Lab</th>
                                <th style="padding:12px 16px;">Kapasitas (PC/Peserta)</th>
                                <th style="padding:12px 16px;">Alokasi Prodi</th>
                                <th style="padding:12px 16px;">Status</th>
                                <th style="padding:12px 16px; text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($laboratories as $l)
                                <tr style="border-bottom:1px solid #f1f5f9;">
                                    <td style="padding:12px 16px; font-weight:700; color:#1e293b;">{{ $l->name }}</td>
                                    <td style="padding:12px 16px;">
                                        <span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:600; background:#f3e8ff; color:#7e22ce;">
                                            {{ $l->lab_type }}
                                        </span>
                                    </td>
                                    <td style="padding:12px 16px; font-weight:600;">
                                        {{ $l->capacity }} Peserta
                                    </td>
                                    <td style="padding:12px 16px;">
                                        @if($l->studyPrograms->count() > 0)
                                            <div style="display:flex; flex-wrap:wrap; gap:4px;">
                                                @foreach($l->studyPrograms as $sp)
                                                    <span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:600; background:#f3e8ff; color:#7e22ce; border:1px solid #e9d5ff;">
                                                        {{ $sp->kode }} - {{ $sp->nama }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span style="color:#94a3b8; font-style:italic; font-size:12px;">Belum dialokasikan</span>
                                        @endif
                                    </td>
                                    <td style="padding:12px 16px;">
                                        <form action="{{ route('fakultas.laboratories.toggle-status', $l) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" style="background:none; border:none; cursor:pointer; padding:0;" title="Klik untuk mengubah status">
                                                <span style="display:inline-block; padding:3px 10px; border-radius:12px; font-size:11px; font-weight:600; background:{{ $l->status === 'ACTIVE' ? '#dcfce7; color:#15803d' : '#fee2e2; color:#b91c1c' }};">
                                                    {{ $l->status }}
                                                </span>
                                            </button>
                                        </form>
                                    </td>
                                    <td style="padding:12px 16px; text-align:right;">
                                        <div style="display:inline-flex; gap:6px; align-items:center;">
                                            <button type="button"
                                                class="btn-action edit-btn"
                                                title="Edit Laboratorium"
                                                data-id="{{ $l->id }}"
                                                data-name="{{ $l->name }}"
                                                data-capacity="{{ $l->capacity }}"
                                                data-lab-type="{{ $l->lab_type }}"
                                                data-status="{{ $l->status }}"
                                                onclick="openEditLabFromBtn(this)">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </button>
                                            <form action="{{ route('fakultas.laboratories.destroy', $l) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus laboratorium {{ $l->name }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-action delete-btn" title="Hapus Laboratorium">
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
                                    <td colspan="6" style="padding:24px; text-align:center; color:#94a3b8;">
                                        Belum ada data laboratorium.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($laboratories->hasPages())
                    <div style="padding:16px; border-top:1px solid #e2e8f0;">
                        {{ $laboratories->links() }}
                    </div>
                @endif
            </div>
        </section>
    </main>
</div>

{{-- MODAL TAMBAH LABORATORIUM --}}
<div id="modalAddLab" class="modal-backdrop">
    <div class="modal-window">
        <div class="modal-header-blue">
            <h3>Tambah Laboratorium Baru</h3>
        </div>
        <div class="modal-body">
            <form action="{{ route('fakultas.laboratories.store') }}" method="POST" class="modal-form">
                @csrf
                <div class="modal-form-row">
                    <label>Nama Laboratorium <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <input type="text" name="name" placeholder="Contoh: Lab Komputasi Awan" required>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>Kapasitas (Peserta) <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <input type="number" name="capacity" min="1" max="500" value="35" required>
                        <small style="color:#64748b; font-size:11px; display:block; margin-top:2px;">Kapasitas dapat diedit kapan saja.</small>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>Jenis / Tipe Lab <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <input type="text" name="lab_type" placeholder="Contoh: Komputer, Elektronika, Jaringan..." required>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>Status</label>
                    <div class="modal-input-container">
                        <select name="status">
                            <option value="ACTIVE">ACTIVE (Aktif)</option>
                            <option value="INACTIVE">INACTIVE (Nonaktif)</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer-buttons" style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalAddLab')">Batal</button>
                    <button type="submit" class="btn-modal-submit" style="background:#9333ea; color:#fff; border:none; padding:8px 18px; border-radius:6px; font-weight:600; cursor:pointer;">Simpan Laboratorium</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL EDIT LABORATORIUM --}}
<div id="modalEditLab" class="modal-backdrop">
    <div class="modal-window">
        <div class="modal-header-blue">
            <h3>Edit Laboratorium</h3>
        </div>
        <div class="modal-body">
            <form id="formEditLab" method="POST" class="modal-form">
                @csrf
                @method('PUT')

                <div class="modal-form-row">
                    <label>Nama Laboratorium <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <input type="text" id="edit_lab_name" name="name" required>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>Kapasitas (Peserta) <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <input type="number" id="edit_lab_capacity" name="capacity" min="1" max="500" required>
                        <small style="color:#64748b; font-size:11px; display:block; margin-top:2px;">Kapasitas dapat disesuaikan sewaktu-waktu.</small>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>Jenis / Tipe Lab <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <input type="text" id="edit_lab_type" name="lab_type" required>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>Status</label>
                    <div class="modal-input-container">
                        <select id="edit_lab_status" name="status">
                            <option value="ACTIVE">ACTIVE (Aktif)</option>
                            <option value="INACTIVE">INACTIVE (Nonaktif)</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer-buttons" style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalEditLab')">Batal</button>
                    <button type="submit" class="btn-modal-submit" style="background:#9333ea; color:#fff; border:none; padding:8px 18px; border-radius:6px; font-weight:600; cursor:pointer;">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL TAMBAH ALOKASI LABORATORIUM --}}
<div id="modalAddLabAllocation" class="modal-backdrop">
    <div class="modal-window">
        <div class="modal-header-blue">
            <h3>Alokasi Laboratorium ke Prodi</h3>
        </div>
        <div class="modal-body">
            <form action="{{ route('fakultas.allocations.laboratories.store') }}" method="POST" class="modal-form">
                @csrf
                <div class="modal-form-row">
                    <label>Pilih Laboratorium <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <select name="laboratory_id" required>
                            <option value="">-- Pilih Laboratorium --</option>
                            @foreach($allLabs as $l)
                                @if($l->status === 'ACTIVE')
                                    <option value="{{ $l->id }}">{{ $l->name }} ({{ $l->lab_type }}, Kapasitas: {{ $l->capacity }})</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>Pilih Program Studi <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <select name="study_program_id" required>
                            <option value="">-- Pilih Program Studi --</option>
                            @foreach($prodis as $p)
                                <option value="{{ $p->id }}">{{ $p->kode }} - {{ $p->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-footer-buttons" style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalAddLabAllocation')">Batal</button>
                    <button type="submit" class="btn-modal-submit" style="background:#0284c7; color:#fff; border:none; padding:8px 18px; border-radius:6px; font-weight:600; cursor:pointer;">Simpan Alokasi</button>
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
        el.style.display = 'flex';
    }
}

function closeModal(modalId) {
    const el = document.getElementById(modalId);
    if (el) {
        el.classList.remove('show');
        el.style.display = 'none';
    }
}

function openEditModal(lab) {
    document.getElementById('formEditLab').action = `/fakultas/laboratorium/${lab.id}`;
    document.getElementById('edit_lab_name').value = lab.name;
    document.getElementById('edit_lab_capacity').value = lab.capacity;
    document.getElementById('edit_lab_type').value = lab.lab_type;
    document.getElementById('edit_lab_status').value = lab.status;
    openModal('modalEditLab');
}

function openEditLabFromBtn(btn) {
    openEditModal({
        id: btn.dataset.id,
        name: btn.dataset.name || '',
        capacity: btn.dataset.capacity || '',
        lab_type: btn.dataset.labType || '',
        status: btn.dataset.status || 'ACTIVE'
    });
}

document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('modal-backdrop')) {
        closeModal(e.target.id);
    }
});
</script>
@endsection


