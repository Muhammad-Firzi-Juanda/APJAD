<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || $request->user()->role !== UserRole::SUPER_ADMIN) {
            abort(403, 'Akses ditolak. Hanya Super Admin yang diizinkan.');
        }

        if (! $request->user()->isActive()) {
            abort(403, 'Akun Anda sedang dinonaktifkan. Silakan hubungi administrator sistem.');
        }

        return $next($request);
    }
}
