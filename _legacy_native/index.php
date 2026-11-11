<?php
// index.php
session_start();
require_once __DIR__.'/config/database.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$showError = false;
$emailInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailInput = trim($_POST['email'] ?? '');
    $passwordInput = $_POST['password'] ?? '';

    if (! empty($emailInput) && ! empty($passwordInput)) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$emailInput]);
        $user = $stmt->fetch();

        if ($user) {
            // Verify hash or plain text fallback
            $isValid = password_verify($passwordInput, $user['password']) || ($passwordInput === $user['password']);
            if ($isValid) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                header('Location: dashboard.php');
                exit;
            } else {
                $showError = true;
            }
        } else {
            $showError = true;
        }
    } else {
        $showError = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Ku - Login | Universitas Maritim Raja Ali Haji</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <!-- Left Side: UMRAH Branding -->
        <div class="login-left">
            <img src="assets/images/umrah_logo.png" alt="Logo UMRAH" class="logo-img">
            <h1 class="brand-title">UMRAH</h1>
            <h2 class="brand-subtitle">Universitas Maritim<br>Raja Ali Haji</h2>
        </div>

        <!-- Right Side: Diagonal Blue Background -->
        <div class="login-right-bg"></div>

        <!-- Right Side: Content & Form -->
        <div class="login-right-content">
            <div class="login-form-wrapper">
                <h1 class="app-title">Jadwal Ku</h1>

                <form action="index.php" method="POST" class="login-form">
                    <div class="input-group">
                        <label for="email">Email</label>
                        <div class="input-field-box">
                            <span class="input-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </span>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($emailInput) ?>" required autocomplete="email">
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
                        </div>
                    </div>

                    <button type="submit" class="btn-login">Login</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Error Modal Popup (Matching Image 2) -->
    <?php if ($showError) { ?>
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
    <?php } ?>

    <script src="assets/js/app.js"></script>
</body>
</html>
