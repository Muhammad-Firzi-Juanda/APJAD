-- Reset admin password to: Admin@12345
-- Hash: $2y$12$xKoL.xIBjpXFZZgZk5x8WeGzThjLQ8mqPrIZGWbqIrVrz.UtXD7HK

UPDATE users SET password = '$2y$12$xKoL.xIBjpXFZZgZk5x8WeGzThjLQ8mqPrIZGWbqIrVrz.UtXD7HK' WHERE email = 'admin@gmail.com';

-- Verify:
SELECT id, nama, email, role FROM users WHERE email = 'admin@gmail.com';
