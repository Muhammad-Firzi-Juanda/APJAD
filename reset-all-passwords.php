<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$users = [
    [
        'email' => 'admin@siwalan.com',
        'password' => Hash::make('admin123'),
    ],
    [
        'email' => 'randtian@gmail.com',
        'password' => Hash::make('password123'),
    ],
    [
        'email' => 'bdarell@gmail.com',
        'password' => Hash::make('password123'),
    ],
];

foreach ($users as $user) {
    DB::table('users')
        ->where('email', $user['email'])
        ->update(['password' => $user['password']]);
}

echo "✓ All passwords updated!\n";
echo "\nLogin credentials:\n";
echo "1. Email: admin@siwalan.com | Password: admin123 (Super Admin)\n";
echo "2. Email: randtian@gmail.com | Password: password123 (Jurusan)\n";
echo "3. Email: bdarell@gmail.com | Password: password123 (Fakultas)\n";
