<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user());
        }

        return view('auth.login');
    }

    private function redirectByRole(User $user): RedirectResponse
    {
        return match ($user->role) {
            UserRole::ADMIN => redirect()->route('dashboard'),
            UserRole::FAKULTAS => redirect()->route('fakultas.dashboard'),
            UserRole::JURUSAN => redirect()->route('jurusan.dashboard'),
            UserRole::PRODI => redirect()->route('prodi.dashboard'),
            default => redirect()->route('dashboard'),
        };
    }

    public function login(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'email' => 'required|email|max:150',
            'password' => 'required|string|min:1',
        ], [
            'email.required' => 'Email wajib diisi!',
            'email.email' => 'Format email tidak valid!',
            'password.required' => 'Password wajib diisi!',
        ]);

        $email = trim($request->input('email'));
        $password = $request->input('password');

        if (Auth::attempt(['email' => $email, 'password' => $password])) {
            $user = Auth::user();

            if (! $user->isActive()) {
                Auth::logout();

                $errorMsg = 'Akun Anda sedang dinonaktifkan. Silakan hubungi administrator sistem.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMsg,
                    ], 403);
                }

                return redirect()->route('login')
                    ->withInput($request->only('email'))
                    ->with('flash_error', $errorMsg);
            }

            $request->session()->regenerate();

            $redirectRoute = match ($user->role) {
                UserRole::ADMIN => route('dashboard'),
                UserRole::FAKULTAS => route('fakultas.dashboard'),
                UserRole::JURUSAN => route('jurusan.dashboard'),
                UserRole::PRODI => route('prodi.dashboard'),
                default => route('dashboard'),
            };

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Login Berhasil',
                    'redirect' => $redirectRoute,
                    'must_change_password' => (bool) $user->must_change_password,
                ]);
            }

            $response = redirect()->intended($redirectRoute);
            if ($user->must_change_password) {
                $response->with('flash_warning', 'Anda masuk menggunakan password sementara. Demi keamanan, Anda wajib segera mengganti password melalui menu Edit Profile.');
            }

            return $response;
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau Password Anda Salah',
            ], 422);
        }

        return redirect()->route('login')
            ->withInput($request->only('email'))
            ->with('show_error', true);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'password_lama' => 'required',
            'password_baru' => 'required|min:6|confirmed',
        ], [
            'password_lama.required' => 'Password lama wajib diisi!',
            'password_baru.required' => 'Password baru wajib diisi!',
            'password_baru.min' => 'Password baru minimal 6 karakter!',
            'password_baru.confirmed' => 'Konfirmasi password tidak cocok!',
        ]);

        $user = Auth::user();

        if (! Hash::check($request->input('password_lama'), $user->password)) {
            return redirect()->back()->with('flash_error', 'Password lama yang Anda masukkan salah!');
        }

        $user->password = $request->input('password_baru');
        $user->must_change_password = false;
        $user->save();

        return redirect()->back(fallback: route('dashboard'))->with('flash_success', 'Password berhasil diubah!');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'nama' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'password_lama' => 'nullable',
            'password_baru' => 'nullable|min:6|confirmed',
        ], [
            'nama.required' => 'Nama wajib diisi!',
            'email.required' => 'Email wajib diisi!',
            'email.unique' => 'Email sudah digunakan oleh user lain!',
            'password_baru.min' => 'Password baru minimal 6 karakter!',
            'password_baru.confirmed' => 'Konfirmasi password tidak cocok!',
        ]);

        if (! empty($request->input('password_baru'))) {
            if (empty($request->input('password_lama'))) {
                return redirect()->back()->with('flash_error', 'Password lama wajib diisi untuk mengubah password!');
            }

            if (! Hash::check($request->input('password_lama'), $user->password)) {
                return redirect()->back()->with('flash_error', 'Password lama yang Anda masukkan salah!');
            }

            $user->password = $request->input('password_baru');
            $user->must_change_password = false;
        }

        $user->nama = trim($request->input('nama'));
        $user->email = trim($request->input('email'));
        $user->save();

        return redirect()->back(fallback: route('dashboard'))->with('flash_success', 'Profile berhasil diperbarui!');
    }
}
