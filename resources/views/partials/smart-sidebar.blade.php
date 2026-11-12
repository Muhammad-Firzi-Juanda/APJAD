{{--
    smart-sidebar.blade.php
    Memilih sidebar yang tepat berdasarkan role user yang sedang login.
    Variabel $active & $activeFakultas harus di-pass oleh view pemanggil.
    --}}
@php
    $sidebarRole = Auth::user()->role ?? null;
    $sidebarActiveFakultas = $activeFakultas ?? $active ?? 'dashboard';

    // Map active key untuk sidebar Admin:
    // Halaman-halaman fakultas yang diakses Admin perlu active key Admin yang tepat
    $adminActiveMap = [
        'mkwu'                     => 'mk-fakultas',
        'mkwf'                     => 'mk-fakultas',
        'penjadwalan'              => 'jadwal-fakultas',
        'penjadwalan-prodi'        => 'penjadwalan',
        'dashboard'                => 'dashboard',
        'rooms'                    => 'dashboard',
        'laboratories'             => 'dashboard',
        'allocations_rooms'        => 'dashboard',
        'allocations_laboratories' => 'dashboard',
    ];
    $aktiveFakulKey = $activeFakultas ?? $active ?? 'dashboard';
    $sidebarActiveAdmin = $adminActiveMap[$aktiveFakulKey] ?? ($active ?? 'dashboard');
@endphp

@if ($sidebarRole === \App\Enums\UserRole::ADMIN || $sidebarRole === \App\Enums\UserRole::SUPER_ADMIN)
    @include('admin.sidebar', ['active' => $sidebarActiveAdmin])
@elseif ($sidebarRole === \App\Enums\UserRole::FAKULTAS)
    @include('fakultas.sidebar', ['active' => $sidebarActiveFakultas])
@elseif ($sidebarRole === \App\Enums\UserRole::JURUSAN)
    @include('jurusan.sidebar', ['active' => $sidebarActiveFakultas])
@elseif ($sidebarRole === \App\Enums\UserRole::PRODI)
    @include('prodi.sidebar', ['active' => $sidebarActiveFakultas])
@else
    @include('fakultas.sidebar', ['active' => $sidebarActiveFakultas])
@endif
