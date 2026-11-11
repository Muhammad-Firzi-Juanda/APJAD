<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$password = bcrypt('Admin@12345');

DB::table('users')
    ->where('email', 'admin@gmail.com')
    ->update(['password' => $password]);

echo "✓ Password updated successfully!\n";
echo "Email: admin@gmail.com\n";
echo "Password: Admin@12345\n";
