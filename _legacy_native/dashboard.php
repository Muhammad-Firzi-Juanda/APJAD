<?php
// dashboard.php
session_start();
require_once __DIR__.'/config/database.php';

// Check if user is logged in
if (! isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$currentUserName = $_SESSION['nama'] ?? 'Antony';
$currentUserRole = $_SESSION['role'] ?? 'Super Admin';

// Fetch all users from database
$stmt = $pdo->query('SELECT * FROM users ORDER BY id ASC');
$users = $stmt->fetchAll();

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Ku - Super Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar Navigation (Matching Image 3) -->
        <aside class="admin-sidebar">
            <div class="sidebar-top">
                <div class="sidebar-brand">
                    <h2>Jadwal Ku</h2>
                </div>
                <nav class="sidebar-nav">
                    <a href="dashboard.php" class="sidebar-link active">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span>Manajemen Akun</span>
                    </a>
                </nav>
            </div>

            <div class="sidebar-bottom">
                <button type="button" class="sidebar-link" onclick="openModal('modalGantiPw')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <span>Ganti Password</span>
                </button>
                <a href="actions/logout.php" class="sidebar-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="admin-main">
            <!-- Header Bar -->
            <header class="admin-header">
                <div class="user-badge">
                    <span><?= htmlspecialchars($currentUserRole) ?>, <span class="user-name"><?= htmlspecialchars($currentUserName) ?></span></span>
                </div>
            </header>

            <!-- Dashboard Body -->
            <section class="admin-content">
                <?php if ($flashSuccess) { ?>
                    <div class="flash-alert success">
                        <span><?= htmlspecialchars($flashSuccess) ?></span>
                        <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;">✕</button>
                    </div>
                <?php } ?>

                <?php if ($flashError) { ?>
                    <div class="flash-alert error">
                        <span><?= htmlspecialchars($flashError) ?></span>
                        <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;">✕</button>
                    </div>
                <?php } ?>

                <h1 class="page-title">Manajemen User</h1>

                <!-- User Management Table (Matching Image 3) -->
                <div class="table-card">
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th style="width: 25%;">Nama</th>
                                <th style="width: 35%;">Email</th>
                                <th style="width: 20%;">Role</th>
                                <th style="width: 20%;">
                                    <div class="action-header-cell">
                                        <span>Aksi</span>
                                        <button type="button" class="btn-add-account" onclick="openModal('modalTambah')" title="Tambah Akun Baru">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                            </svg>
                                        </button>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)) { ?>
                                <tr>
                                    <td colspan="4" style="padding: 24px; color: #64748b;">Belum ada data user.</td>
                                </tr>
                            <?php } else { ?>
                                <?php foreach ($users as $u) { ?>
                                    <tr>
                                        <td><?= htmlspecialchars($u['nama']) ?></td>
                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                        <td><?= htmlspecialchars($u['role']) ?></td>
                                        <td>
                                            <div class="action-buttons-group">
                                                <!-- Edit button (Matching Image 3) -->
                                                <button type="button" class="btn-action edit-btn" title="Edit Data Akun"
                                                    onclick="openEditModal(<?= htmlspecialchars(json_encode([
                                                        'id' => $u['id'],
                                                        'nama' => $u['nama'],
                                                        'email' => $u['email'],
                                                        'role' => $u['role'],
                                                    ])) ?>)">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                    </svg>
                                                </button>

                                                <!-- Delete button (Matching Image 3) -->
                                                <button type="button" class="btn-action delete-btn" title="Hapus User"
                                                    onclick="openDeleteModal(<?= (int) $u['id'] ?>)">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                                                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                                                        <line x1="10" y1="11" x2="14" y2="15"></line>
                                                        <line x1="14" y1="11" x2="10" y2="15"></line>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <!-- =========================================================================
         MODAL 1: TAMBAH AKUN BARU (MATCHING IMAGE 4)
         ========================================================================= -->
    <div class="modal-backdrop" id="modalTambah">
        <div class="modal-window">
            <div class="modal-header-blue">
                <h3>Tambah Akun Baru</h3>
            </div>
            <div class="modal-body">
                <form action="actions/user_action.php" method="POST" class="modal-form">
                    <input type="hidden" name="action" value="create">

                    <div class="modal-form-row">
                        <label for="create_nama">Nama</label>
                        <div class="modal-input-container">
                            <input type="text" id="create_nama" name="nama" required>
                        </div>
                    </div>

                    <div class="modal-form-row">
                        <label for="create_email">E-mail</label>
                        <div class="modal-input-container">
                            <input type="email" id="create_email" name="email" required>
                        </div>
                    </div>

                    <div class="modal-form-row">
                        <label for="create_role">Role</label>
                        <div class="modal-input-container">
                            <select id="create_role" name="role" required>
                                <option value="Jurusan">Jurusan</option>
                                <option value="Fakultas">Fakultas</option>
                                <option value="Super Admin">Super Admin</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-form-row">
                        <label for="create_password">Password</label>
                        <div class="modal-input-container">
                            <input type="password" id="create_password" name="password" required>
                        </div>
                    </div>

                    <div class="modal-footer-buttons">
                        <button type="button" class="btn-modal-cancel" onclick="closeModal('modalTambah')">Batal</button>
                        <button type="submit" class="btn-modal-submit">Buat</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- =========================================================================
         MODAL 2: EDIT DATA AKUN (MATCHING IMAGE 5)
         ========================================================================= -->
    <div class="modal-backdrop" id="modalEdit">
        <div class="modal-window">
            <div class="modal-header-blue">
                <h3>Edit Data Akun</h3>
            </div>
            <div class="modal-body">
                <form action="actions/user_action.php" method="POST" class="modal-form">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" id="edit_user_id" name="user_id">

                    <div class="modal-form-row">
                        <label for="edit_nama">Nama</label>
                        <div class="modal-input-container">
                            <input type="text" id="edit_nama" name="nama" required>
                        </div>
                    </div>

                    <div class="modal-form-row">
                        <label for="edit_email">E-mail</label>
                        <div class="modal-input-container">
                            <input type="email" id="edit_email" name="email" required>
                        </div>
                    </div>

                    <div class="modal-form-row">
                        <label for="edit_role">Role</label>
                        <div class="modal-input-container">
                            <select id="edit_role" name="role" required>
                                <option value="Jurusan">Jurusan</option>
                                <option value="Fakultas">Fakultas</option>
                                <option value="Super Admin">Super Admin</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-form-row">
                        <label for="edit_password">Password</label>
                        <div class="modal-input-container">
                            <input type="password" id="edit_password" name="password" placeholder="Kosongkan jika tidak diubah">
                        </div>
                    </div>

                    <div class="modal-footer-buttons">
                        <button type="button" class="btn-modal-cancel" onclick="closeModal('modalEdit')">Batal</button>
                        <button type="submit" class="btn-modal-submit">Buat</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- =========================================================================
         MODAL 3: GANTI PASSWORD (MATCHING IMAGE 6)
         ========================================================================= -->
    <div class="modal-backdrop" id="modalGantiPw">
        <div class="modal-window">
            <div class="modal-header-blue">
                <h3>Ganti Password</h3>
            </div>
            <div class="modal-body">
                <form action="actions/change_password.php" method="POST" class="modal-form">
                    <div class="modal-form-row">
                        <label for="password_lama">Password lama</label>
                        <div class="modal-input-container">
                            <input type="password" id="password_lama" name="password_lama" required>
                        </div>
                    </div>

                    <div class="modal-form-row">
                        <label for="password_baru">Password Baru</label>
                        <div class="modal-input-container">
                            <input type="password" id="password_baru" name="password_baru" required>
                        </div>
                    </div>

                    <div class="modal-footer-buttons">
                        <button type="button" class="btn-modal-cancel" onclick="closeModal('modalGantiPw')">Batal</button>
                        <button type="submit" class="btn-modal-submit">Buat</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- =========================================================================
         MODAL 4: KONFIRMASI HAPUS (MATCHING IMAGE 7)
         ========================================================================= -->
    <div class="modal-backdrop" id="modalHapus">
        <div class="modal-confirm-card">
            <h4 class="confirm-question">Yakin ingin menghapus user ini?</h4>
            <form action="actions/user_action.php" method="POST" id="formDeleteUser">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_user_id" name="user_id">

                <div class="confirm-actions">
                    <button type="submit" class="btn-confirm-yes">Ya</button>
                    <button type="button" class="btn-confirm-cancel" onclick="closeModal('modalHapus')">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>
