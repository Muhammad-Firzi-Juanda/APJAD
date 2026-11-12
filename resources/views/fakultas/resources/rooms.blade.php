@extends('layouts.app')

@section('title', 'APJAD - Ruangan Fakultas')

@section('content')
<div class="admin-layout">
    @include('partials.smart-sidebar', ['active' => 'rooms', 'activeFakultas' => 'rooms'])

    <main class="admin-main">
        <header class="admin-header">
            @include('partials.smart-user-badge')
        </header>

        <section class="admin-content">
            {{-- Flash Messages --}}
            @if(session('flash_success'))
                <div style="background:#dcfce7; border:1px solid #86efac; color:#15803d; padding:12px 16px; border-radius:8px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
                    <span>{{ session('flash_success') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:#15803d;font-weight:700;">✕</button>
                </div>
            @endif

            @if(session('flash_error'))
                <div style="background:#fee2e2; border:1px solid #fca5a5; color:#b91c1c; padding:12px 16px; border-radius:8px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
                    <span>{{ session('flash_error') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:#b91c1c;font-weight:700;">✕</button>
                </div>
            @endif

            @if($errors->any())
                <div style="background:#fee2e2; border:1px solid #fca5a5; color:#b91c1c; padding:12px 16px; border-radius:8px; margin-bottom:20px;">
                    <ul style="margin:0; padding-left:20px;">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Header Toolbar --}}
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
                <div>
                    <h1 class="page-title" style="margin-bottom:4px;">Daftar Ruangan Fakultas</h1>
                    <p style="color:#64748b; font-size:0.95rem; margin:0;">
                        Owner Resource: <strong>{{ Auth::user()->fakultas->nama ?? 'Fakultas' }}</strong> &bull; Kapasitas ruangan dapat diedit kapan saja sesuai kebutuhan.
                    </p>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <a href="{{ route('fakultas.allocations.rooms') }}" style="display:inline-flex; align-items:center; gap:8px; background:#e0f2fe; color:#0369a1; padding:10px 16px; border-radius:6px; border:1px solid #bae6fd; font-weight:600; text-decoration:none; font-size:13.5px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="16 3 21 3 21 8"></polyline>
                            <line x1="4" y1="20" x2="21" y2="3"></line>
                            <polyline points="21 16 21 21 16 21"></polyline>
                            <line x1="15" y1="15" x2="21" y2="21"></line>
                            <line x1="4" y1="4" x2="9" y2="9"></line>
                        </svg>
                        <span>Kelola Alokasi Ruangan ke Prodi</span>
                    </a>
                    <button type="button" onclick="openModal('modalAddRoom')" style="display:inline-flex; align-items:center; gap:8px; background:#0860be; color:#fff; padding:10px 18px; border-radius:6px; border:none; font-weight:600; cursor:pointer; font-size:14px; box-shadow:0 2px 6px rgba(8,96,190,0.25);">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        <span>Tambah Ruangan</span>
                    </button>
                </div>
            </div>

            {{-- Filter Bar --}}
            <div class="card" style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:16px; margin-bottom:20px;">
                <form action="{{ route('fakultas.rooms.index') }}" method="GET" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode atau nama ruangan..." style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; min-width:220px;">

                    <select name="status" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                        <option value="">Semua Status</option>
                        <option value="ACTIVE" {{ request('status') === 'ACTIVE' ? 'selected' : '' }}>ACTIVE (Aktif)</option>
                        <option value="INACTIVE" {{ request('status') === 'INACTIVE' ? 'selected' : '' }}>INACTIVE (Nonaktif)</option>
                    </select>

                    <input type="number" name="min_capacity" value="{{ request('min_capacity') }}" min="1" placeholder="Min. Kapasitas..." style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; width:150px;">

                    <button type="submit" style="padding:8px 16px; background:#0f172a; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer;">Filter</button>
                    <a href="{{ route('fakultas.rooms.index') }}" style="padding:8px 14px; background:#f1f5f9; color:#475569; text-decoration:none; border-radius:6px; font-size:13px; font-weight:500;">Reset</a>
                </form>
            </div>

            {{-- Batch Action Bar (Bulk Delete) --}}
            <div id="batchActionBar" style="display:none; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:10px 16px; margin-bottom:14px; align-items:center; justify-content:space-between;">
                <div style="font-size:13px; font-weight:600; color:#1e40af; display:flex; align-items:center; gap:8px;">
                    <span id="selectedCountBadge" style="background:#0860be; color:#fff; border-radius:12px; padding:2px 8px; font-size:11px;">0</span>
                    <span>data ruangan dipilih</span>
                </div>
                <button type="button" onclick="submitBulkDelete()" style="background:#ef4444; color:#fff; border:none; padding:7px 14px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    <span>Hapus Terpilih</span>
                </button>
            </div>

            {{-- Form Tersembunyi Bulk Delete --}}
            <form id="formBulkDelete" action="{{ route('fakultas.rooms.bulk-delete') }}" method="POST" style="display:none;">
                @csrf
                <div id="bulkDeleteInputsContainer"></div>
            </form>

            {{-- Table Ruangan --}}
            <div class="card" style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden;">
                <div class="table-responsive">
                    <table class="data-table" style="width:100%; border-collapse:collapse; font-size:13px;">
                        <thead>
                            <tr style="background:#f8fafc; text-align:left; border-bottom:1px solid #e2e8f0;">
                                <th style="padding:12px 14px; width:40px; text-align:center;">
                                    <input type="checkbox" id="selectAllRooms" onchange="toggleSelectAll(this)" style="cursor:pointer; width:16px; height:16px;">
                                </th>
                                <th style="padding:12px 16px;">Kode Ruangan</th>
                                <th style="padding:12px 16px;">Nama Ruangan</th>
                                <th style="padding:12px 16px;">Tipe</th>
                                <th style="padding:12px 16px;">Kapasitas</th>
                                <th style="padding:12px 16px;">Alokasi Prodi</th>
                                <th style="padding:12px 16px;">Status</th>
                                <th style="padding:12px 16px; text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rooms as $room)
                                @php
                                    $isLabor = in_array($room->type, ['Labor', 'LABORATORY', 'Lab']);
                                @endphp
                                <tr style="border-bottom:1px solid #f1f5f9;">
                                    <td style="padding:12px 14px; text-align:center;">
                                        <input type="checkbox" class="room-checkbox" value="{{ $room->id }}" onchange="updateBatchActionBar()" style="cursor:pointer; width:16px; height:16px;">
                                    </td>
                                    <td style="padding:12px 16px; font-weight:700; color:#1e293b;">
                                        {{ $room->code ?? ('R-' . str_pad($room->id, 2, '0', STR_PAD_LEFT)) }}
                                    </td>
                                    <td style="padding:12px 16px; font-weight:600; color:#0f172a;">
                                        {{ $room->name }}
                                    </td>
                                    <td style="padding:12px 16px;">
                                        @if($isLabor)
                                            <span style="display:inline-block; padding:3px 10px; border-radius:6px; font-size:11px; font-weight:700; background:#f3e8ff; color:#7e22ce;">
                                                Labor
                                            </span>
                                        @else
                                            <span style="display:inline-block; padding:3px 10px; border-radius:6px; font-size:11px; font-weight:700; background:#e0f2fe; color:#0369a1;">
                                                CLASSROOM
                                            </span>
                                        @endif
                                    </td>
                                    <td style="padding:12px 16px;">
                                        <span style="font-weight:700; color:#0f172a;">{{ $room->capacity }}</span>
                                        <span style="color:#64748b; font-size:11px;">Kursi</span>
                                    </td>
                                    <td style="padding:12px 16px;">
                                        <div style="display:flex; flex-wrap:wrap; gap:4px; align-items:center;">
                                            @forelse($room->studyPrograms as $sp)
                                                <span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:600; background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd;">
                                                    {{ $sp->kode }} - {{ $sp->nama }}
                                                </span>
                                            @empty
                                                <span style="color:#94a3b8; font-size:12px; font-style:italic;">Belum dialokasikan</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td style="padding:12px 16px;">
                                        <form action="{{ route('fakultas.rooms.toggle-status', $room) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" style="background:none; border:none; cursor:pointer; padding:0;" title="Klik untuk mengubah status ACTIVE / INACTIVE">
                                                <span style="display:inline-block; padding:3px 10px; border-radius:12px; font-size:11px; font-weight:600; background:{{ $room->status === 'ACTIVE' ? '#dcfce7; color:#15803d' : '#fee2e2; color:#b91c1c' }};">
                                                    {{ $room->status }}
                                                </span>
                                            </button>
                                        </form>
                                    </td>
                                    <td style="padding:12px 16px; text-align:right;">
                                        <div style="display:inline-flex; gap:6px; align-items:center;">
                                            <button type="button" 
                                                class="btn-action edit-btn btn-edit-room"
                                                title="Edit Ruangan"
                                                data-id="{{ $room->id }}"
                                                data-code="{{ $room->code ?? '' }}"
                                                data-name="{{ $room->name }}"
                                                data-capacity="{{ $room->capacity }}"
                                                data-type="{{ $room->type }}"
                                                data-status="{{ $room->status }}"
                                                onclick="openEditModalFromBtn(this)">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </button>
                                            <form action="{{ route('fakultas.rooms.destroy', $room) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus {{ $room->name }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-action delete-btn" title="Hapus Ruangan">
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
                                    <td colspan="8" style="padding:24px; text-align:center; color:#94a3b8;">
                                        Belum ada data ruangan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($rooms->hasPages())
                    <div style="padding:16px; border-top:1px solid #e2e8f0;">
                        {{ $rooms->links() }}
                    </div>
                @endif
            </div>
        </section>
    </main>
</div>

{{-- MODAL TAMBAH RUANGAN --}}
<div id="modalAddRoom" class="modal-backdrop">
    <div class="modal-window">
        <div class="modal-header-blue">
            <h3>Tambah Ruangan Baru</h3>
        </div>
        <div class="modal-body">
            <form action="{{ route('fakultas.rooms.store') }}" method="POST" class="modal-form">
                @csrf
                <input type="hidden" name="type" value="CLASSROOM">

                <div class="modal-form-row">
                    <label>Kode Ruangan</label>
                    <div class="modal-input-container">
                        <input type="text" name="code" placeholder="Contoh: R-12 (Kosongkan untuk otomatis)" style="text-transform:uppercase; font-weight:600;">
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>Nama Ruangan <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <input type="text" name="name" required placeholder="Contoh: Room 12">
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>Kapasitas (Kursi) <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <input type="number" name="capacity" min="1" max="500" value="42" required placeholder="Kapasitas">
                        <small style="color:#64748b; font-size:11px; display:block; margin-top:2px;">Kapasitas ruangan dapat diedit kapan saja.</small>
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
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalAddRoom')">Batal</button>
                    <button type="submit" class="btn-modal-submit" style="background:#0860be; color:#fff; border:none; padding:8px 18px; border-radius:6px; font-weight:600; cursor:pointer;">Simpan Ruangan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL EDIT RUANGAN --}}
<div id="modalEditRoom" class="modal-backdrop">
    <div class="modal-window">
        <div class="modal-header-blue">
            <h3>Edit Ruangan</h3>
        </div>
        <div class="modal-body">
            <form id="formEditRoom" method="POST" class="modal-form">
                @csrf
                @method('PUT')

                <div class="modal-form-row">
                    <label>Kode Ruangan</label>
                    <div class="modal-input-container">
                        <input type="text" id="edit_code" name="code" style="text-transform:uppercase; font-weight:600;">
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>Nama Ruangan <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <input type="text" id="edit_name" name="name" required>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>Kapasitas (Kursi) <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <input type="number" id="edit_capacity" name="capacity" min="1" max="500" required>
                        <small style="color:#64748b; font-size:11px; display:block; margin-top:2px;">Kapasitas dapat disesuaikan sewaktu-waktu.</small>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>Status</label>
                    <div class="modal-input-container">
                        <select id="edit_status" name="status">
                            <option value="ACTIVE">ACTIVE (Aktif)</option>
                            <option value="INACTIVE">INACTIVE (Nonaktif)</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer-buttons" style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalEditRoom')">Batal</button>
                    <button type="submit" class="btn-modal-submit" style="background:#0860be; color:#fff; border:none; padding:8px 18px; border-radius:6px; font-weight:600; cursor:pointer;">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL TAMBAH ALOKASI RUANGAN KE PRODI --}}
<div id="modalAddAllocation" class="modal-backdrop">
    <div class="modal-window">
        <div class="modal-header-blue">
            <h3>Alokasi Ruangan ke Prodi</h3>
        </div>
        <div class="modal-body">
            <form action="{{ route('fakultas.allocations.rooms.store') }}" method="POST" class="modal-form">
                @csrf
                <div class="modal-form-row">
                    <label>Pilih Ruangan <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <select name="room_id" required>
                            <option value="">-- Pilih Ruangan --</option>
                            @foreach($allRooms as $r)
                                @if($r->status === 'ACTIVE')
                                    <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->capacity }} Kursi)</option>
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
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalAddAllocation')">Batal</button>
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
    }
}

function closeModal(modalId) {
    const el = document.getElementById(modalId);
    if (el) {
        el.classList.remove('show');
        el.style.display = '';
    }
}

function openEditModal(data) {
    document.getElementById('formEditRoom').action = `/fakultas/ruangan/${data.id}`;
    document.getElementById('edit_code').value = data.code || '';
    document.getElementById('edit_name').value = data.name || '';
    document.getElementById('edit_capacity').value = data.capacity || '';
    if (document.getElementById('edit_status')) {
        document.getElementById('edit_status').value = data.status || 'ACTIVE';
    }
    openModal('modalEditRoom');
}

function openEditModalFromBtn(btn) {
    openEditModal({
        id: btn.dataset.id,
        code: btn.dataset.code || '',
        name: btn.dataset.name || '',
        capacity: btn.dataset.capacity || '',
        status: btn.dataset.status || 'ACTIVE'
    });
}

function toggleSelectAll(masterCheckbox) {
    const checkboxes = document.querySelectorAll('.room-checkbox');
    checkboxes.forEach(cb => cb.checked = masterCheckbox.checked);
    updateBatchActionBar();
}

function updateBatchActionBar() {
    const selected = document.querySelectorAll('.room-checkbox:checked');
    const bar = document.getElementById('batchActionBar');
    const badge = document.getElementById('selectedCountBadge');
    const master = document.getElementById('selectAllRooms');
    const all = document.querySelectorAll('.room-checkbox');

    if (badge) badge.textContent = selected.length;
    if (master && all.length > 0) {
        master.checked = (selected.length === all.length);
    }

    if (bar) {
        bar.style.display = selected.length > 0 ? 'flex' : 'none';
    }
}

function submitBulkDelete() {
    const selected = document.querySelectorAll('.room-checkbox:checked');
    if (selected.length === 0) {
        alert('Pilih minimal satu data ruangan untuk dihapus.');
        return;
    }

    if (!confirm(`Apakah Anda yakin ingin menghapus ${selected.length} data ruangan yang dipilih?`)) {
        return;
    }

    const container = document.getElementById('bulkDeleteInputsContainer');
    container.innerHTML = '';
    selected.forEach(cb => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'selected_ids[]';
        input.value = cb.value;
        container.appendChild(input);
    });

    document.getElementById('formBulkDelete').submit();
}

document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('modal-backdrop')) {
        closeModal(e.target.id);
    }
});
</script>
@endsection


