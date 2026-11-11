<?php

// config/database.php

$host = '127.0.0.1';
$port = '3306';
$db_name = 'jadwal_ku_db';
$username = 'root';
$password = '';

try {
    // Connect to server first without specifying DB
    $pdo_server = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Create database if not exists
    $pdo_server->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

    // Connect to specific database
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db_name};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Create users table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `nama` VARCHAR(100) NOT NULL,
            `email` VARCHAR(150) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `role` VARCHAR(50) NOT NULL DEFAULT 'Jurusan',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Seed default users if table is empty
    $stmt = $pdo->query('SELECT COUNT(*) as cnt FROM `users`');
    $rowCount = $stmt->fetch()['cnt'];

    if ($rowCount == 0) {
        $insertStmt = $pdo->prepare('INSERT INTO `users` (`nama`, `email`, `password`, `role`) VALUES (?, ?, ?, ?)');

        $defaultUsers = [
            ['Antony', 'antony@gmail.com', password_hash('admin123', PASSWORD_DEFAULT), 'Super Admin'],
            ['Revi Aedrian', 'randtian@gmail.com', password_hash('password123', PASSWORD_DEFAULT), 'Jurusan'],
            ['Brian Darell', 'bdarell@gmail.com', password_hash('password123', PASSWORD_DEFAULT), 'Fakultas'],
        ];

        foreach ($defaultUsers as $u) {
            $insertStmt->execute($u);
        }
    }

} catch (PDOException $e) {
    exit('Database Connection Error: '.$e->getMessage());
}
