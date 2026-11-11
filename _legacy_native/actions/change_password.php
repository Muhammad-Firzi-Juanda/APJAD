<?php

// actions/change_password.php
session_start();
require_once __DIR__.'/../config/database.php';

if (! isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $user_id = $_SESSION['user_id'];

    if (empty($password_lama) || empty($password_baru)) {
        $_SESSION['flash_error'] = 'Password lama dan password baru wajib diisi!';
        header('Location: ../dashboard.php');
        exit;
    }

    // Fetch user password
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (! $user || ! password_verify($password_lama, $user['password'])) {
        $_SESSION['flash_error'] = 'Password lama yang Anda masukkan salah!';
        header('Location: ../dashboard.php');
        exit;
    }

    // Update with new hashed password
    $newHash = password_hash($password_baru, PASSWORD_DEFAULT);
    $updateStmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
    $updateStmt->execute([$newHash, $user_id]);

    $_SESSION['flash_success'] = 'Password berhasil diubah!';
    header('Location: ../dashboard.php');
    exit;
}

header('Location: ../dashboard.php');
exit;
