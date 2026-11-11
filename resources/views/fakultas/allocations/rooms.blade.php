@extends('layouts.app')

@section('title', 'APJAD - Alokasi Ruangan ke Program Studi')

@section('content')
<div class="admin-layout">
    @include('fakultas.sidebar', ['active' => 'allocations_rooms'])

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
                    <h1 class="page-title" style="margin-bottom:4px;">Alokasi Ruangan ke Program Studi</h1>
                    <p style="color:#64748b; font-size:14px; margin:0;">
                        Relasi Many-to-Many: Satu ruangan milik Fakultas dapat dialokasikan ke beberapa Program Studi (contoh: Room 4 &rarr; TI, TE).
                    </p>
                </div>
                <button type="button" onclick="openModal('modalAddAllocation')" style="display:inline-flex; align-items:center; gap:8px; background:#0284c7; color:#fff; padding:10px 18px; border-radius:6px; border:none; font-weight:600; cursor:pointer; font-size:14px;">
                    <span>+ Tambah Alokasi Ruangan</span>
                </button>
            </div>

            {{-- Filter Bar Alokasi Ruangan --}}
            <div class="card" style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:16px; margin-bottom:20px;">
                <form action="{{ route('fakultas.allocations.rooms') }}" method="GET" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
                    <select name="room_id" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; min-width:200px;">
                        <option value="">Semua Ruangan</option>
                        @foreach($rooms as $r)
                            <option value="{{ $r->id }}" {{ (string) request('room_id') === (string) $r->id ? 'selected' : '' }}>
                                {{ $r->name }} ({{ $r->capacity }} Kursi)
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
                    <a href="{{ route('fakultas.allocations.rooms') }}" style="padding:8px 14px; background:#f1f5f9; color:#475569; text-decoration:none; border-radius:6px; font-size:13px; font-weight:500;">Reset</a>
                </form>
            </div>

            {{-- Ringkasan Ruangan dan Prodi yang Dialokasikan --}}
            <div class="card" style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:18px; margin-bottom:24px;">
                <h3 style="margin:0 0 12px; font-size:15px; font-weight:600; color:#1e293b;">Daftar Ruangan & Program Studi Pengguna</h3>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:12px;">
                    @foreach($rooms as $r)
                        <div style="border:1px solid #e2e8f0; border-radius:8px; padding:12px; background:#f8fafc;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                <strong style="font-size:14px; color:#0f172a;">{{ $r->name }}</strong>
                                <span style="font-size:11px; padding:2px 8px; border-radius:10px; background:#e0f2fe; color:#0369a1; font-weight:600;">
                                    {{ $r->capacity }} Kursi
                                </span>
                            </div>
                            <div style="font-size:12px; color:#64748b; margin-bottom:6px;">
                                Alokasi Prodi:
                            </div>
                            <div style="display:flex; flex-wrap:wrap; gap:6px;">
                                @forelse($r->studyPrograms as $sp)
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

            {{-- Table Alokasi --}}
            <div class="card" style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden;">
                <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0;">
                    <h3 style="margin:0; font-size:15px; font-weight:600; color:#1e293b;">Tabel Alokasi Ruangan (Junction)</h3>
                </div>
                <div class="table-responsive">
                    <table class="data-table" style="width:100%; border-collapse:collapse; font-size:13px;">
                        <thead>
                            <tr style="background:#f8fafc; text-align:left; border-bottom:1px solid #e2e8f0;">
                                <th style="padding:12px 16px;">Ruangan</th>
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
                                        {{ $a->room->name ?? '-' }}
                                    </td>
                                    <td style="padding:12px 16px;">
                                        {{ $a->room->capacity ?? '-' }} Kursi
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
                                        <form action="{{ route('fakultas.allocations.rooms.destroy', $a) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus alokasi {{ $a->room->name ?? '' }} untuk {{ $a->studyProgram->kode ?? '' }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="redirect_to" value="allocations_page">
                                            <button type="submit" style="padding:6px 12px; background:#fff; color:#ef4444; border:1px solid #fca5a5; border-radius:4px; font-size:12px; font-weight:500; cursor:pointer;">
                                                Hapus Alokasi
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="padding:24px; text-align:center; color:#94a3b8;">
                                        Belum ada data alokasi ruangan ke Program Studi.
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

{{-- Modal Tambah Alokasi Ruangan --}}
<div id="modalAddAllocation" class="modal-backdrop">
    <div class="modal-window">
        <div class="modal-header-blue">
            <h3>Tambah Alokasi Ruangan ke Prodi</h3>
        </div>
        <div class="modal-body">
            <form action="{{ route('fakultas.allocations.rooms.store') }}" method="POST" class="modal-form">
                @csrf
                <input type="hidden" name="redirect_to" value="allocations_page">

                <div class="modal-form-row">
                    <label>Pilih Ruangan <span style="color:#ef4444;">*</span></label>
                    <div class="modal-input-container">
                        <select name="room_id" required>
                            <option value="">-- Pilih Ruangan (ACTIVE) --</option>
                            @foreach($activeRooms ?? $rooms as $r)
                                <option value="{{ $r->id }}">{{ $r->name }} (Kapasitas: {{ $r->capacity }} Kursi)</option>
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
                    <button type="button" class="btn-modal-cancel" onclick="closeModal('modalAddAllocation')">Batal</button>
                    <button type="submit" class="btn-modal-submit" style="background:#0284c7; color:#fff; border:none; padding:8px 18px; border-radius:6px; font-weight:600; cursor:pointer;">Simpan Alokasi</button>
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
