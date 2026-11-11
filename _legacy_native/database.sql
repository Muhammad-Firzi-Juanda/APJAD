-- Database: jadwal_ku_db
CREATE DATABASE IF NOT EXISTS `jadwal_ku_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `jadwal_ku_db`;

-- Table structure for users
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(50) NOT NULL DEFAULT 'Jurusan',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial Seed Data
-- Passwords:
-- antony@gmail.com -> admin123
-- randtian@gmail.com -> password123
-- bdarell@gmail.com -> password123
INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`) VALUES
(1, 'Antony', 'antony@gmail.com', '$2y$10$wE4iL/q5hWk6EreN/0rPveVz/qL.4GfF1q1j3r1F0Z6i5sKkLpW2W', 'Super Admin'),
(2, 'Revi Aedrian', 'randtian@gmail.com', '$2y$10$X8m1ZqEsmUoGZ0q5T7wOgeuB8y/8jHl0x0Y9W1X2Y3Z4A5B6C7D8E', 'Jurusan'),
(3, 'Brian Darell', 'bdarell@gmail.com', '$2y$10$X8m1ZqEsmUoGZ0q5T7wOgeuB8y/8jHl0x0Y9W1X2Y3Z4A5B6C7D8E', 'Fakultas')
ON DUPLICATE KEY UPDATE `nama`=VALUES(`nama`);
