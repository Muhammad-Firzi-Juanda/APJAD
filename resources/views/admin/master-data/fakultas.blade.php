@extends('layouts.app')

@section('title', 'APJAD - Master Data Fakultas')

@section('content')
<div class="admin-layout">
    @include('admin.sidebar', ['active' => 'master-fakultas'])

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
                    <h1 class="page-title" style="margin-bottom: 4px;">Master Data Fakultas</h1>
                    <p style="color: #64748b; font-size: 0.95rem; margin: 0;">
                        {{ ($canManageMasterData ?? false) ? 'Kelola daftar Fakultas di lingkungan universitas sebagai induk organisasi Jurusan dan Program Studi.' : 'Daftar Fakultas di lingkungan universitas (Mode Read-Only).' }}
                    </p>
                </div>
                @if($canManageMasterData ?? false)
                <button type="button" class="btn-primary btn-add" onclick="openModal('modalTambahFakultas')" style="display: inline-flex; align-items: center; gap: 8px; border: none; background: #0860be; color: #ffffff; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 0.92rem; cursor: pointer; box-shadow: 0 2px 6px rgba(8, 96, 190, 0.3);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Tambah Fakultas</span>
                </button>
                @else
                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 8px; background: #f1f5f9; color: #475569; font-weight: 700; font-size: 0.84rem; border: 1px solid #cbd5e1;">
                    Read-Only
                </span>
                @endif
            </div>

            <!-- Filter & Search Toolbar -->
            <div style="background: #ffffff; border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                <form action="{{ route('master-data.fakultas.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 14px; align-items: center; justify-content: space-between;">
                    <div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: center;">
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
                            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari kode / nama fakultas..." style="padding: 7px 12px 7px 32px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; width: 220px;">
                            <svg style="position: absolute; left: 10px; width: 15px; height: 15px; color: #94a3b8;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                        </div>
                        <button type="submit" style="padding: 7px 14px; background: #0860be; color: #fff; border: none; border-radius: 6px; font-size: 0.82rem; font-weight: 600; cursor: pointer;">
                            Cari
                        </button>
                        @if(!empty($filterStatus) || !empty($search))
                            <a href="{{ route('master-data.fakultas.index') }}" style="padding: 6px 12px; background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.82rem; font-weight: 600; text-decoration: none;">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Tabel Fakultas -->
            <div class="table-card" style="background: #ffffff; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); padding: 20px;">
                <div class="table-responsive">
                    <table class="user-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid #e2e8f0; text-align: left;">
                                <th style="padding: 12px 14px; color: #64748b; font-size: 0.85rem; text-transform: uppercase;">Kode</th>
                                <th style="padding: 12px 14px; color: #64748b; font-size: 0.85rem; text-transform: uppercase;">Nama Fakultas</th>
                                <th style="padding: 12px 14px; color: #64748b; font-size: 0.85rem; text-transform: uppercase;">Jumlah Jurusan</th>
                                <th style="padding: 12px 14px; color: #64748b; font-size: 0.85rem; text-transform: uppercase;">Jumlah Prodi</th>
                                <th style="padding: 12px 14px; color: #64748b; font-size: 0.85rem; text-transform: uppercase;">Status</th>
                                <th style="padding: 12px 14px; color: #64748b; font-size: 0.85rem; text-transform: uppercase; text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($fakultasList as $item)
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 14px; font-weight: 700; color: #0284c7;">
                                        {{ $item->kode }}
                                    </td>
                                    <td style="padding: 14px;">
                                        <div style="font-weight: 600; color: #0f172a;">{{ $item->nama }}</div>
                                    </td>
                                    <td style="padding: 14px; font-size: 0.88rem; color: #334155;">
                                        <span style="display: inline-block; padding: 3px 10px; border-radius: 999px; background: #fef3c7; color: #b45309; font-weight: 600; font-size: 0.78rem;">
                                            {{ $item->jurusans_count }} Jurusan
                                        </span>
                                    </td>
                                    <td style="padding: 14px; font-size: 0.88rem; color: #334155;">
                                        <span style="display: inline-block; padding: 3px 10px; border-radius: 999px; background: #f3e8ff; color: #7e22ce; font-weight: 600; font-size: 0.78rem;">
                                            {{ $item->prodi_count }} Prodi
                                        </span>
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
                                            <button type="button" class="btn-action edit-btn" title="Edit Fakultas"
                                                data-id="{{ $item->id }}"
                                                data-kode="{{ $item->kode }}"
                                                data-nama="{{ $item->nama }}"
                                                data-status="{{ $item->status }}"
                                                onclick="openEditFakultasModal(this)"
                                                style="padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; color: #0284c7; font-size: 0.8rem; font-weight: 600; cursor: pointer;">
                                                Edit
                                            </button>

                                            <form action="{{ route('master-data.fakultas.toggle-status', $item) }}" method="POST" style="display:inline;">
                                                @csrf
                                                <button type="submit" style="padding: 6px 10px; border: 1px solid {{ $item->isActive() ? '#fca5a5' : '#86efac' }}; border-radius: 6px; background: {{ $item->isActive() ? '#fef2f2' : '#f0fdf4' }}; color: {{ $item->isActive() ? '#dc2626' : '#16a34a' }}; font-size: 0.8rem; font-weight: 600; cursor: pointer;">
                                                    {{ $item->isActive() ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>

                                            <form action="{{ route('master-data.fakultas.destroy', $item) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Fakultas ini?');">
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
                                        Belum ada data Fakultas.
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

<!-- Modal Tambah Fakultas -->
<div id="modalTambahFakultas" class="modal-backdrop">
    <div class="modal-window">
        <div class="modal-header-blue">
            <h3>Tambah Fakultas</h3>
        </div>
        <div class="modal-body">
            <form action="{{ route('master-data.fakultas.store') }}" method="POST" class="modal-form">
                @csrf
                <div class="modal-form-row">
                    <label for="add_kode">Kode Fakultas</label>
                    <div class="modal-input-container">
                        <input type="text" id="add_kode" name="kode" placeholder="Contoh: FTTK" required maxlength="20">
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="add_nama">Nama Fakultas</label>
                    <div class="modal-input-container">
                        <input type="text" id="add_nama" name="nama" placeholder="Contoh: Fakultas Teknik dan Teknologi Kemaritiman" required maxlength="100">
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
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalTambahFakultas')">Batal</button>
                    <button type="submit" class="btn-modal-submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Fakultas -->
<div id="modalEditFakultas" class="modal-backdrop">
    <div class="modal-window">
        <div class="modal-header-blue">
            <h3>Edit Fakultas</h3>
        </div>
        <div class="modal-body">
            <form id="formEditFakultas" method="POST" class="modal-form">
                @csrf
                @method('PUT')
                <div class="modal-form-row">
                    <label for="edit_kode">Kode Fakultas</label>
                    <div class="modal-input-container">
                        <input type="text" id="edit_kode" name="kode" required maxlength="20">
                    </div>
                </div>

                <div class="modal-form-row">
                    <label for="edit_nama">Nama Fakultas</label>
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
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalEditFakultas')">Batal</button>
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
function openEditFakultasModal(btn) {
    const data = btn.dataset ? btn.dataset : btn;
    document.getElementById('formEditFakultas').action = '/master-data/fakultas/' + data.id;
    document.getElementById('edit_kode').value = data.kode || '';
    document.getElementById('edit_nama').value = data.nama || '';
    document.getElementById('edit_status').value = data.status || 'ACTIVE';
    openModal('modalEditFakultas');
}
</script>
@endsection

