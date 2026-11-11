@extends('layouts.app')

@section('title', 'MKWU (Mata Kuliah Wajib Universitas) - Fakultas')

@section('content')
<div class="admin-layout">
    @include('fakultas.sidebar', ['active' => 'mkwu'])

    <main class="admin-main">
        <header class="admin-header">
            <div class="user-badge">
                <span>{{ Auth::user()->role }}, <span class="user-name">{{ Auth::user()->nama }}</span> ({{ Auth::user()->fakultas->kode ?? 'FTTK' }})</span>
            </div>
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
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <div>
                    <h1 class="page-title" style="margin-bottom:4px;">MKWU (Mata Kuliah Wajib Universitas)</h1>
                    <p style="color:#64748b; font-size:0.95rem; margin:0;">
                        Kelola data mata kuliah kurikulum tingkat universitas (Kode kategori: <strong>UNV</strong>).
                    </p>
                </div>
                <button type="button" onclick="openModal('modalAddCourse')" style="display:inline-flex; align-items:center; gap:8px; background:#2563eb; color:#fff; padding:10px 18px; border-radius:6px; border:none; font-weight:600; cursor:pointer; font-size:14px; box-shadow:0 2px 6px rgba(37,99,235,0.25);">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>Tambah MKWU</span>
                </button>
            </div>

            {{-- Filter Bar --}}
            <div class="card" style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:16px; margin-bottom:20px;">
                <form action="{{ route('fakultas.mkwu') }}" method="GET" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode atau nama..." style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; min-width:220px;">
                    
                    <select name="semester" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                        <option value="">Semua Semester</option>
                        @for($s = 1; $s <= 8; $s++)
                            <option value="{{ $s }}" {{ request('semester') == $s ? 'selected' : '' }}>Semester {{ $s }}</option>
                        @endfor
                    </select>

                    <select name="type" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                        <option value="">Semua Jenis</option>
                        <option value="THEORY" {{ request('type') === 'THEORY' ? 'selected' : '' }}>Teori (THEORY)</option>
                        <option value="PRACTICUM" {{ request('type') === 'PRACTICUM' ? 'selected' : '' }}>Praktikum (PRACTICUM)</option>
                    </select>

                    <select name="status" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                        <option value="">Filter Status: Semua</option>
                        <option value="ACTIVE" {{ request('status') === 'ACTIVE' ? 'selected' : '' }}>ACTIVE</option>
                        <option value="INACTIVE" {{ request('status') === 'INACTIVE' ? 'selected' : '' }}>INACTIVE</option>
                    </select>

                    <button type="submit" style="padding:8px 16px; background:#0f172a; color:#fff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer;">Filter</button>
                    <a href="{{ route('fakultas.mkwu') }}" style="padding:8px 14px; background:#f1f5f9; color:#475569; text-decoration:none; border-radius:6px; font-size:13px; font-weight:500;">Reset</a>
                </form>
            </div>

            {{-- Batch Action Bar (Bulk Delete) --}}
            <div id="batchActionBar" style="display:none; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:10px 16px; margin-bottom:14px; align-items:center; justify-content:space-between;">
                <div style="font-size:13px; font-weight:600; color:#1e40af; display:flex; align-items:center; gap:8px;">
                    <span id="selectedCountBadge" style="background:#2563eb; color:#fff; border-radius:12px; padding:2px 8px; font-size:11px;">0</span>
                    <span>data MKWU dipilih</span>
                </div>
                <button type="button" onclick="submitBulkDelete()" style="background:#ef4444; color:#fff; border:none; padding:7px 14px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    <span>Hapus Terpilih</span>
                </button>
            </div>

            {{-- Form Tersembunyi Bulk Delete --}}
            <form id="formBulkDelete" action="{{ route('fakultas.courses.bulk-delete') }}" method="POST" style="display:none;">
                @csrf
                <input type="hidden" name="category_code" value="UNV">
                <div id="bulkDeleteInputsContainer"></div>
            </form>

            {{-- Table MKWU --}}
            <div class="card" style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden;">
                <div class="table-responsive">
                    <table class="data-table" style="width:100%; border-collapse:collapse; font-size:13px;">
                        <thead>
                            <tr style="background:#f8fafc; text-align:left; border-bottom:1px solid #e2e8f0;">
                                <th style="padding:12px 14px; width:40px; text-align:center;">
                                    <input type="checkbox" id="selectAllCourses" onchange="toggleSelectAll(this)" style="cursor:pointer; width:16px; height:16px;">
                                </th>
                                <th style="padding:12px 16px;">Kode</th>
                                <th style="padding:12px 16px;">Nama Mata Kuliah</th>
                                <th style="padding:12px 16px;">SKS</th>
                                <th style="padding:12px 16px;">Semester</th>
                                <th style="padding:12px 16px;">Jenis</th>
                                <th style="padding:12px 16px;">Status</th>
                                <th style="padding:12px 16px; text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($courses as $c)
                                <tr style="border-bottom:1px solid #f1f5f9;">
                                    <td style="padding:12px 14px; text-align:center;">
                                        <input type="checkbox" class="course-checkbox" value="{{ $c->id }}" onchange="updateBatchActionBar()" style="cursor:pointer; width:16px; height:16px;">
                                    </td>
                                    <td style="padding:12px 16px; font-weight:700; color:#1e293b;">{{ $c->code }}</td>
                                    <td style="padding:12px 16px;">{{ $c->name }}</td>
                                    <td style="padding:12px 16px;">{{ $c->sks }}</td>
                                    <td style="padding:12px 16px;">Semester {{ $c->semester }}</td>
                                    <td style="padding:12px 16px;">
                                        <span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:600; background:{{ $c->type === 'THEORY' ? '#e0e7ff; color:#3730a3' : '#fef3c7; color:#92400e' }};">
                                            {{ $c->type === 'THEORY' ? 'Teori' : 'Praktikum' }}
                                        </span>
                                    </td>
                                    <td style="padding:12px 16px;">
                                        <form action="{{ route('fakultas.courses.toggle-status', $c) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" style="background:none; border:none; cursor:pointer; padding:0;" title="Klik untuk mengubah status">
                                                <span style="display:inline-block; padding:3px 10px; border-radius:12px; font-size:11px; font-weight:600; background:{{ $c->status === 'ACTIVE' ? '#dcfce7; color:#15803d' : '#fee2e2; color:#b91c1c' }};">
                                                    {{ $c->status }}
                                                </span>
                                            </button>
                                        </form>
                                    </td>
                                    <td style="padding:12px 16px; text-align:right;">
                                        <button type="button" 
                                            class="btn-edit-course"
                                            data-id="{{ $c->id }}"
                                            data-code="{{ $c->code }}"
                                            data-name="{{ $c->name }}"
                                            data-sks="{{ $c->sks }}"
                                            data-semester="{{ $c->semester }}"
                                            data-type="{{ $c->type }}"
                                            data-status="{{ $c->status }}"
                                            onclick="openEditModalFromBtn(this)" 
                                            style="padding:6px 12px; background:#f1f5f9; color:#0f172a; border:1px solid #cbd5e1; border-radius:4px; font-size:12px; font-weight:500; cursor:pointer; margin-right:4px;">
                                            Edit
                                        </button>
                                        <form action="{{ route('fakultas.courses.destroy', $c) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus MKWU {{ $c->code }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="padding:6px 10px; background:#fff; color:#ef4444; border:1px solid #fca5a5; border-radius:4px; font-size:12px; font-weight:500; cursor:pointer;">
                                                Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" style="padding:24px; text-align:center; color:#94a3b8;">
                                        Tidak ada data MKWU yang sesuai.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($courses->hasPages())
                    <div style="padding:16px; border-top:1px solid #e2e8f0;">
                        {{ $courses->links() }}
                    </div>
                @endif
            </div>
        </section>
    </main>
</div>

{{-- MODAL TAMBAH MKWU --}}
<div id="modalAddCourse" class="modal-backdrop">
    <div class="modal-window">
        <div class="modal-header-blue">
            <h3>Tambah MKWU Baru</h3>
        </div>
        <div class="modal-body">
            <form action="{{ route('fakultas.courses.store') }}" method="POST" class="modal-form">
                @csrf
                <input type="hidden" name="category_code" value="UNV">

                <div class="modal-form-row">
                    <label>Kode Mata Kuliah <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <input type="text" name="code" required placeholder="Contoh: UNV101" style="text-transform:uppercase; font-weight:600;">
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>Nama Mata Kuliah <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <input type="text" name="name" required placeholder="Contoh: Pendidikan Pancasila">
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>SKS & Semester <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container" style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div>
                            <input type="number" name="sks" min="1" max="6" value="2" required placeholder="SKS">
                            <span style="font-size:11px; color:#64748b;">SKS (1–6)</span>
                        </div>
                        <div>
                            <input type="number" name="semester" min="1" max="8" value="1" required placeholder="Semester">
                            <span style="font-size:11px; color:#64748b;">Semester (1–8)</span>
                        </div>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>Jenis Perkuliahan <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <select name="type" required>
                            <option value="THEORY">THEORY (Teori)</option>
                            <option value="PRACTICUM">PRACTICUM (Praktikum)</option>
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>Status</label>
                    <div class="modal-input-container">
                        <select name="status">
                            <option value="ACTIVE">ACTIVE</option>
                            <option value="INACTIVE">INACTIVE</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer-buttons" style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalAddCourse')">Batal</button>
                    <button type="submit" class="btn-modal-submit" style="background:#0860be; color:#fff; border:none; padding:8px 18px; border-radius:6px; font-weight:600; cursor:pointer;">Simpan MKWU</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL EDIT MKWU --}}
<div id="modalEditCourse" class="modal-backdrop">
    <div class="modal-window">
        <div class="modal-header-blue">
            <h3>Edit MKWU</h3>
        </div>
        <div class="modal-body">
            <form id="formEditCourse" method="POST" class="modal-form">
                @csrf
                @method('PUT')

                <div class="modal-form-row">
                    <label>Kode Mata Kuliah <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <input type="text" id="edit_code" name="code" required style="font-weight:600; text-transform:uppercase;">
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>Nama Mata Kuliah <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <input type="text" id="edit_name" name="name" required>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>SKS & Semester <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container" style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div>
                            <input type="number" id="edit_sks" name="sks" min="1" max="6" required>
                            <span style="font-size:11px; color:#64748b;">SKS (1–6)</span>
                        </div>
                        <div>
                            <input type="number" id="edit_semester" name="semester" min="1" max="8" required>
                            <span style="font-size:11px; color:#64748b;">Semester (1–8)</span>
                        </div>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>Jenis Perkuliahan <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <select id="edit_type" name="type" required>
                            <option value="THEORY">THEORY (Teori)</option>
                            <option value="PRACTICUM">PRACTICUM (Praktikum)</option>
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label>Status</label>
                    <div class="modal-input-container">
                        <select id="edit_status" name="status">
                            <option value="ACTIVE">ACTIVE</option>
                            <option value="INACTIVE">INACTIVE</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer-buttons" style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalEditCourse')">Batal</button>
                    <button type="submit" class="btn-modal-submit" style="background:#0860be; color:#fff; border:none; padding:8px 18px; border-radius:6px; font-weight:600; cursor:pointer;">Simpan Perubahan</button>
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

function openEditModal(course) {
    document.getElementById('formEditCourse').action = `/fakultas/courses/${course.id}`;
    document.getElementById('edit_code').value = course.code;
    document.getElementById('edit_name').value = course.name;
    document.getElementById('edit_sks').value = course.sks;
    document.getElementById('edit_semester').value = course.semester;
    document.getElementById('edit_type').value = course.type;
    if (document.getElementById('edit_status')) {
        document.getElementById('edit_status').value = course.status || 'ACTIVE';
    }
    openModal('modalEditCourse');
}

function openEditModalFromBtn(btn) {
    openEditModal({
        id: btn.dataset.id,
        code: btn.dataset.code,
        name: btn.dataset.name,
        sks: btn.dataset.sks,
        semester: btn.dataset.semester,
        type: btn.dataset.type,
        status: btn.dataset.status || 'ACTIVE'
    });
}

function toggleSelectAll(masterCheckbox) {
    const checkboxes = document.querySelectorAll('.course-checkbox');
    checkboxes.forEach(cb => cb.checked = masterCheckbox.checked);
    updateBatchActionBar();
}

function updateBatchActionBar() {
    const selected = document.querySelectorAll('.course-checkbox:checked');
    const bar = document.getElementById('batchActionBar');
    const badge = document.getElementById('selectedCountBadge');
    const master = document.getElementById('selectAllCourses');
    const all = document.querySelectorAll('.course-checkbox');

    if (badge) badge.textContent = selected.length;
    if (master && all.length > 0) {
        master.checked = (selected.length === all.length);
    }

    if (bar) {
        bar.style.display = selected.length > 0 ? 'flex' : 'none';
    }
}

function submitBulkDelete() {
    const selected = document.querySelectorAll('.course-checkbox:checked');
    if (selected.length === 0) {
        alert('Pilih minimal satu data mata kuliah untuk dihapus.');
        return;
    }

    if (!confirm(`Apakah Anda yakin ingin menghapus ${selected.length} mata kuliah MKWU yang dipilih?`)) {
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
