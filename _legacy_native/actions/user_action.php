<?php

// actions/user_action.php
session_start();
require_once __DIR__.'/../config/database.php';

// Check if user is logged in
if (! isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? 'Jurusan');
        $password = $_POST['password'] ?? '';

        if (empty($nama) || empty($email) || empty($password)) {
            $_SESSION['flash_error'] = 'Semua field harus diisi!';
            header('Location: ../dashboard.php');
            exit;
        }

        // Check if email already exists
        $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            $_SESSION['flash_error'] = 'Email sudah terdaftar!';
            header('Location: ../dashboard.php');
            exit;
        }

        // Insert new user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $insertStmt = $pdo->prepare('INSERT INTO users (nama, email, role, password) VALUES (?, ?, ?, ?)');
        $insertStmt->execute([$nama, $email, $role, $hashedPassword]);

        $_SESSION['flash_success'] = 'Akun berhasil dibuat!';
        header('Location: ../dashboard.php');
        exit;
    }

    if ($action === 'update') {
        $id = (int) ($_POST['user_id'] ?? 0);
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? 'Jurusan');
        $password = $_POST['password'] ?? '';

        if ($id <= 0 || empty($nama) || empty($email)) {
            $_SESSION['flash_error'] = 'Data tidak valid!';
            header('Location: ../dashboard.php');
            exit;
        }

        // Check if email is used by another user
        $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $checkStmt->execute([$email, $id]);
        if ($checkStmt->fetch()) {
            $_SESSION['flash_error'] = 'Email sudah digunakan oleh user lain!';
            header('Location: ../dashboard.php');
            exit;
        }

        if (! empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET nama = ?, email = ?, role = ?, password = ? WHERE id = ?');
            $stmt->execute([$nama, $email, $role, $hashedPassword, $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE users SET nama = ?, email = ?, role = ? WHERE id = ?');
            $stmt->execute([$nama, $email, $role, $id]);
        }

        // Update session name if updating own profile
        if ($_SESSION['user_id'] == $id) {
            $_SESSION['nama'] = $nama;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $role;
        }

        $_SESSION['flash_success'] = 'Data akun berhasil diperbarui!';
        header('Location: ../dashboard.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['user_id'] ?? 0);

        if ($id <= 0) {
            $_SESSION['flash_error'] = 'ID user tidak valid!';
            header('Location: ../dashboard.php');
            exit;
        }

        // Prevent self-deletion
        if ($id == $_SESSION['user_id']) {
            $_SESSION['flash_error'] = 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif!';
            header('Location: ../dashboard.php');
            exit;
        }

        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);

        $_SESSION['flash_success'] = 'User berhasil dihapus!';
        header('Location: ../dashboard.php');
        exit;
    }
}

header('Location: ../dashboard.php');
exit;
