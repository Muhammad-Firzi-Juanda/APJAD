{{--
    smart-user-badge.blade.php
    Menampilkan user badge di header yang tepat sesuai role.
--}}
@php
    $badgeUser = Auth::user();
    if ($badgeUser->role === \App\Enums\UserRole::ADMIN || $badgeUser->role === \App\Enums\UserRole::SUPER_ADMIN) {
        $badgeLabel = $badgeUser->role . ', ';
        $badgeName  = $badgeUser->nama;
        $badgeScope = 'Admin';
    } elseif ($badgeUser->role === \App\Enums\UserRole::FAKULTAS) {
        $badgeLabel = 'Fakultas, ';
        $badgeName  = $badgeUser->nama;
        $badgeScope = $badgeUser->fakultas->kode ?? 'FTTK';
    } elseif ($badgeUser->role === \App\Enums\UserRole::JURUSAN) {
        $badgeLabel = 'Jurusan, ';
        $badgeName  = $badgeUser->nama;
        $badgeScope = $badgeUser->jurusan->kode ?? '-';
    } elseif ($badgeUser->role === \App\Enums\UserRole::PRODI) {
        $badgeLabel = 'Prodi, ';
        $badgeName  = $badgeUser->nama;
        $badgeScope = $badgeUser->prodi->kode ?? '-';
    } else {
        $badgeLabel = $badgeUser->role . ', ';
        $badgeName  = $badgeUser->nama;
        $badgeScope = '-';
    }
@endphp
<div class="user-badge">
    <span>{{ $badgeLabel }}<span class="user-name">{{ $badgeName }}</span> ({{ $badgeScope }})</span>
</div>

