@extends('layouts.app')

@section('title', 'APJAD - Master Data Program Studi')

@section('content')
<div class="admin-layout">
    @include('admin.sidebar', ['active' => 'master-prodi'])

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

            @if (session('flash_error'))
                <div class="flash-alert error">
                    <span>{{ session('flash_error') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;">✕</button>
                </div>
            @endif

            @if ($errors->any())
                <div class="flash-alert error">
                    <span>{{ $errors->first() }}</span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;">✕</button>
                </div>
            @endif

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h1 class="page-title" style="margin-bottom: 4px;">Master Data Program Studi</h1>
                    <p style="color: #64748b; font-size: 0.95rem; margin: 0;">
                        {{ ($canManageMasterData ?? false) ? 'Kelola daftar Program Studi beserta relasi Jurusan dan Fakultas induknya.' : 'Daftar Program Studi beserta relasi Jurusan dan Fakultas induknya (Mode Read-Only).' }}
                    </p>
                </div>
                @if($canManageMasterData ?? false)
                <button type="button" class="btn-primary btn-add" onclick="openModal('modalTambahProdi')" style="display: inline-flex; align-items: center; gap: 8px; border: none; background: #0860be; color: #ffffff; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 0.92rem; cursor: pointer; box-shadow: 0 2px 6px rgba(8, 96, 190, 0.3);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Tambah Program Studi</span>
                </button>
                @else
                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 8px; background: #f1f5f9; color: #475569; font-weight: 700; font-size: 0.84rem; border: 1px solid #cbd5e1;">
                    Read-Only
                </span>
                @endif
            </div>

            <!-- Filter & Search Toolbar -->
            <div style="background: #ffffff; border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                <form action="{{ route('master-data.prodi.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 14px; align-items: center; justify-content: space-between;">
                    <div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <label for="filter_fakultas" style="font-size: 0.85rem; font-weight: 700; color: #475569;">Fakultas:</label>
                            <select id="filter_fakultas" name="fakultas_id" onchange="this.form.submit()" style="padding: 7px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; background: #ffffff; color: #1e293b; font-weight: 500; cursor: pointer;">
                                <option value="">Semua Fakultas</option>
                                @foreach($allFakultas as $fak)
                                    <option value="{{ $fak->id }}" {{ (string) $filterFakultasId === (string) $fak->id ? 'selected' : '' }}>
                                        {{ $fak->kode }} — {{ $fak->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div style="display: flex; align-items: center; gap: 8px;">
                            <label for="filter_jurusan" style="font-size: 0.85rem; font-weight: 700; color: #475569;">Jurusan:</label>
                            <select id="filter_jurusan" name="jurusan_id" onchange="this.form.submit()" style="padding: 7px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; background: #ffffff; color: #1e293b; font-weight: 500; cursor: pointer;">
                                <option value="">Semua Jurusan</option>
                                @foreach($allJurusan as $jur)
                                    @if(empty($filterFakultasId) || (int) $jur->fakultas_id === (int) $filterFakultasId)
                                        <option value="{{ $jur->id }}" {{ (string) $filterJurusanId === (string) $jur->id ? 'selected' : '' }}>
                                            {{ $jur->kode }} — {{ $jur->nama }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div style="display: flex; align-items: center; gap: 8px;">
                            <label for="filter_status" style="font-size: 0.85rem; font-weight: 700; color: #475569;">Status:</label>
                            <select id="filter_status" name="status" onchange="this.form.submit()" style="padding: 7px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; background: #ffffff; color: #1e293b; font-weight: 500; cursor: pointer;">
                                <option value="">Semua Status</option>
                                <option value="ACTIVE" {{ $filterStatus === 'ACTIVE' ? 'selected' : '' }}>ACTIVE</option>
                                <option value="INACTIVE" {{ $filterStatus === 'INACTIVE' ? 'selected' : '' }}>INACTIVE</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: flex; gap: 8px; align-items: center;">
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari kode / nama prodi..." style="padding: 7px 12px 7px 32px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; width: 220px;">
                            <svg style="position: absolute; left: 10px; width: 15px; height: 15px; color: #94a3b8;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                        </div>
                        <button type="submit" style="padding: 7px 14px; background: #0860be; color: #fff; border: none; border-radius: 6px; font-size: 0.82rem; font-weight: 600; cursor: pointer;">
                            Cari
                        </button>
                        @if(!empty($filterFakultasId) || !empty($filterJurusanId) || !empty($filterStatus) || !empty($search))
                            <a href="{{ route('master-data.prodi.index') }}" style="padding: 6px 12px; background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.82rem; font-weight: 600; text-decoration: none;">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Tabel Program Studi -->
            <div class="table-card" style="background: #ffffff; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); padding: 20px;">
                <div class="table-responsive">
                    <table class="user-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid #e2e8f0; text-align: left;">
                                <th style="padding: 12px 14px; color: #64748b; font-size: 0.85rem; text-transform: uppercase;">Kode Prodi</th>
                                <th style="padding: 12px 14px; color: #64748b; font-size: 0.85rem; text-transform: uppercase;">Nama Program Studi</th>
                                <th style="padding: 12px 14px; color: #64748b; font-size: 0.85rem; text-transform: uppercase;">Jurusan Induk</th>
                                <th style="padding: 12px 14px; color: #64748b; font-size: 0.85rem; text-transform: uppercase;">Fakultas Induk</th>
                                <th style="padding: 12px 14px; color: #64748b; font-size: 0.85rem; text-transform: uppercase;">Status</th>
                                <th style="padding: 12px 14px; color: #64748b; font-size: 0.85rem; text-transform: uppercase; text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($prodiList as $item)
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 14px; font-weight: 700; color: #7e22ce;">
                                        {{ $item->kode }}
                                    </td>
                                    <td style="padding: 14px;">
                                        <div style="font-weight: 600; color: #0f172a;">{{ $item->nama }}</div>
                                    </td>
                                    <td style="padding: 14px; font-size: 0.88rem; color: #334155;">
                                        <div style="font-weight: 600;">{{ $item->jurusan?->nama ?? '-' }}</div>
                                        <small style="color: #94a3b8;">Kode: {{ $item->jurusan?->kode ?? '-' }}</small>
                                    </td>
                                    <td style="padding: 14px; font-size: 0.88rem; color: #334155;">
                                        <div style="font-weight: 600;">{{ $item->fakultas?->nama ?? '-' }}</div>
                                        <small style="color: #94a3b8;">Kode: {{ $item->fakultas?->kode ?? '-' }}</small>
                                    </td>
                                    <td style="padding: 14px;">
                                        @if($item->isActive())
                                            <span style="display:inline-flex; align-items:center; gap:5px; padding: 4px 10px; border-radius: 6px; font-size: 0.78rem; font-weight: 700; background: #dcfce7; color: #15803d;">
                                                ACTIVE
                                            </span>
                                        @else
                                            <span style="display:inline-flex; align-items:center; gap:5px; padding: 4px 10px; border-radius: 6px; font-size: 0.78rem; font-weight: 700; background: #fee2e2; color: #b91c1c;">
                                                INACTIVE
                                            </span>
                                        @endif
                                    </td>
                                    <td style="padding: 14px; text-align: right;">
                                        @if($canManageMasterData ?? false)
                                        <div style="display: inline-flex; gap: 6px; align-items: center;">
                                            <button type="button" class="btn-action edit-btn" title="Edit Program Studi"
                                                data-id="{{ $item->id }}"
                                                data-fakultas-id="{{ $item->fakultas_id }}"
                                                data-jurusan-id="{{ $item->jurusan_id }}"
                                                data-kode="{{ $item->kode }}"
                                                data-nama="{{ $item->nama }}"
                                                data-status="{{ $item->status }}"
                                                onclick="openEditProdiModal(this)"
                                                style="padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; color: #0284c7; font-size: 0.8rem; font-weight: 600; cursor: pointer;">
                                                Edit
                                            </button>

                                            <form action="{{ route('master-data.prodi.toggle-status', $item) }}" method="POST" style="display:inline;">
                                                @csrf
                                                <button type="submit" style="padding: 6px 10px; border: 1px solid {{ $item->isActive() ? '#fca5a5' : '#86efac' }}; border-radius: 6px; background: {{ $item->isActive() ? '#fef2f2' : '#f0fdf4' }}; color: {{ $item->isActive() ? '#dc2626' : '#16a34a' }}; font-size: 0.8rem; font-weight: 600; cursor: pointer;">
                                                    {{ $item->isActive() ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>

                                            <form action="{{ route('master-data.prodi.destroy', $item) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Program Studi ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" style="padding: 6px 10px; border: 1px solid #fecaca; border-radius: 6px; background: #fff; color: #dc2626; font-size: 0.8rem; font-weight: 600; cursor: pointer;">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                        @else
                                        <span style="font-size: 0.8rem; color: #94a3b8; font-weight: 600;">Read-Only</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="padding: 28px; text-align: center; color: #94a3b8;">
                                        Belum ada data Program Studi.
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

<!-- Modal Tambah Program Studi (Dropdown Bertingkat Fakultas -> Jurusan) -->
<div id="modalTambahProdi" class="modal-backdrop">
    <div class="modal-window">
        <div class="modal-header-blue">
            <h3>Tambah Program Studi</h3>
        </div>
        <div class="modal-body">
            <form action="{{ route('master-data.prodi.store') }}" method="POST" class="modal-form">
                @csrf
                <div class="modal-form-row">
                    <label for="add_fakultas_id">Pilih Fakultas</label>
                    <div class="modal-input-container">
                        <select id="add_fakultas_id" name="fakultas_id" required onchange="filterJurusanOptions('add_fakultas_id', 'add_jurusan_id')" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                            <option value="">-- Pilih Fakultas --</option>
                            @foreach($fakultasList as $fak)
                                <option value="{{ $fak->id }}">{{ $fak->kode }} — {{ $fak->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="add_jurusan_id">Pilih Jurusan</label>
                    <div class="modal-input-container">
                        <select id="add_jurusan_id" name="jurusan_id" required style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                            <option value="">-- Pilih Jurusan --</option>
                            @foreach($jurusanList as $jur)
                                <option value="{{ $jur->id }}" data-fakultas="{{ $jur->fakultas_id }}">
                                    {{ $jur->kode }} — {{ $jur->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="add_kode">Kode Prodi</label>
                    <div class="modal-input-container">
                        <input type="text" id="add_kode" name="kode" placeholder="Contoh: TI" required maxlength="20">
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="add_nama">Nama Program Studi</label>
                    <div class="modal-input-container">
                        <input type="text" id="add_nama" name="nama" placeholder="Contoh: S1 Teknik Informatika" required maxlength="100">
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="add_status">Status</label>
                    <div class="modal-input-container">
                        <select id="add_status" name="status" required style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                            <option value="ACTIVE">ACTIVE</option>
                            <option value="INACTIVE">INACTIVE</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer-buttons">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalTambahProdi')">Batal</button>
                    <button type="submit" class="btn-modal-submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Program Studi (Dropdown Bertingkat Fakultas -> Jurusan) -->
<div id="modalEditProdi" class="modal-backdrop">
    <div class="modal-window">
        <div class="modal-header-blue">
            <h3>Edit Program Studi</h3>
        </div>
        <div class="modal-body">
            <form id="formEditProdi" method="POST" class="modal-form">
                @csrf
                @method('PUT')
                <div class="modal-form-row">
                    <label for="edit_fakultas_id">Pilih Fakultas</label>
                    <div class="modal-input-container">
                        <select id="edit_fakultas_id" name="fakultas_id" required onchange="filterJurusanOptions('edit_fakultas_id', 'edit_jurusan_id')" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                            <option value="">-- Pilih Fakultas --</option>
                            @foreach($fakultasList as $fak)
                                <option value="{{ $fak->id }}">{{ $fak->kode }} — {{ $fak->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="edit_jurusan_id">Pilih Jurusan</label>
                    <div class="modal-input-container">
                        <select id="edit_jurusan_id" name="jurusan_id" required style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                            <option value="">-- Pilih Jurusan --</option>
                            @foreach($jurusanList as $jur)
                                <option value="{{ $jur->id }}" data-fakultas="{{ $jur->fakultas_id }}">
                                    {{ $jur->kode }} — {{ $jur->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="edit_kode">Kode Prodi</label>
                    <div class="modal-input-container">
                        <input type="text" id="edit_kode" name="kode" required maxlength="20">
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="edit_nama">Nama Program Studi</label>
                    <div class="modal-input-container">
                        <input type="text" id="edit_nama" name="nama" required maxlength="100">
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="edit_status">Status</label>
                    <div class="modal-input-container">
                        <select id="edit_status" name="status" required style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                            <option value="ACTIVE">ACTIVE</option>
                            <option value="INACTIVE">INACTIVE</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer-buttons">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalEditProdi')">Batal</button>
                    <button type="submit" class="btn-modal-submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Profile -->
<div id="modalEditProfile" class="modal-backdrop">
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
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="profile_password_baru">Password Baru</label>
                    <div class="modal-input-container">
                        <input type="password" id="profile_password_baru" name="password_baru" placeholder="Kosongkan jika tidak diubah">
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="profile_password_baru_confirmation">Konfirmasi Password Baru</label>
                    <div class="modal-input-container">
                        <input type="password" id="profile_password_baru_confirmation" name="password_baru_confirmation" placeholder="Ulangi password baru">
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

<script>
function openModal(modalId) {
    document.getElementById(modalId).classList.add('show');
}
function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}
function filterJurusanOptions(fakultasSelectId, jurusanSelectId, selectedJurusanId = null) {
    const fakultasId = document.getElementById(fakultasSelectId).value;
    const jurusanSelect = document.getElementById(jurusanSelectId);
    const options = jurusanSelect.querySelectorAll('option[data-fakultas]');

    options.forEach(opt => {
        const match = !fakultasId || String(opt.getAttribute('data-fakultas')) === String(fakultasId);
        opt.style.display = match ? '' : 'none';
        opt.disabled = !match;
    });

    if (selectedJurusanId !== null) {
        jurusanSelect.value = selectedJurusanId;
    } else if (jurusanSelect.selectedOptions.length && jurusanSelect.selectedOptions[0].disabled) {
        jurusanSelect.value = '';
    }
}
function openEditProdiModal(btn) {
    const data = btn.dataset ? {
        id: btn.dataset.id,
        fakultas_id: btn.dataset.fakultasId,
        jurusan_id: btn.dataset.jurusanId,
        kode: btn.dataset.kode,
        nama: btn.dataset.nama,
        status: btn.dataset.status
    } : btn;
    document.getElementById('formEditProdi').action = '/master-data/prodi/' + data.id;
    document.getElementById('edit_fakultas_id').value = data.fakultas_id || '';
    filterJurusanOptions('edit_fakultas_id', 'edit_jurusan_id', data.jurusan_id || '');
    document.getElementById('edit_kode').value = data.kode || '';
    document.getElementById('edit_nama').value = data.nama || '';
    document.getElementById('edit_status').value = data.status || 'ACTIVE';
    openModal('modalEditProdi');
}
</script>
@endsection

