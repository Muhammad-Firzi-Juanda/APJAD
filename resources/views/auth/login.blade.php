@extends('layouts.app')

@section('title', 'APJAD - Login | Universitas Maritim Raja Ali Haji')

@section('content')
<!-- Success Toast Popup (Matching Image 2) -->
<div id="loginSuccessToast" class="login-success-toast" style="{{ session('show_success') ? 'display: flex;' : 'display: none;' }}">
    <span>Login Berhasil</span>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="20 6 9 17 4 12"></polyline>
    </svg>
</div>

<div class="login-container">
    <!-- Left Side: UMRAH Branding -->
    <div class="login-left">
        <img src="{{ asset('assets/images/umrah_logo.png') }}" alt="Logo UMRAH" class="logo-img">
        <h1 class="brand-title">UMRAH</h1>
        <h2 class="brand-subtitle">Universitas Maritim<br>Raja Ali Haji</h2>
    </div>

    <!-- Right Side: Diagonal Blue Background -->
    <div class="login-right-bg"></div>

    <!-- Right Side: Content & Form -->
    <div class="login-right-content">
        <div class="login-form-wrapper">
            <h1 class="app-title">APJAD</h1>

            <form action="{{ route('login.submit') }}" method="POST" class="login-form">
                @csrf
                <div class="input-group">
                    <label for="email">Email</label>
                    <div class="input-field-box">
                        <span class="input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </span>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                    </div>
                </div>

                <div class="input-group">
                    <label for="password">Password</label>
                    <div class="input-field-box">
                        <span class="input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </span>
                        <input type="password" id="password" name="password" required autocomplete="current-password">
                        <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibility()">
                            <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login">Login</button>
            </form>
        </div>
    </div>
</div>

<!-- Error Modal Popup -->
@if (session('show_error') || $errors->any())
<div class="error-popup-backdrop" id="loginErrorModal">
    <div class="error-popup-card">
        <div class="error-popup-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </div>
        <p class="error-message">Email atau Password Anda Salah</p>
        <button type="button" class="btn-popup-ok" onclick="closeErrorPopup()">OK</button>
    </div>
</div>
@endif

<script>
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
    } else {
        passwordInput.type = 'password';
        eyeIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
    }
}
</script>

@endsection
