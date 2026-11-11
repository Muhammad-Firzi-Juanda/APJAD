<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Bootstrap Akun Sistem APJAD
    |--------------------------------------------------------------------------
    |
    | Mengatur kredensial awal akun Super Admin (pembuat akun operasional)
    | dan akun tunggal Admin (Global Administrator APJAD) melalui variabel
    | environment agar tidak hardcoded di kode sumber.
    |
    */

    'super_admin' => [
        'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
        'email' => env('SUPER_ADMIN_EMAIL', 'admin@siwalan.com'),
        'password' => env('SUPER_ADMIN_PASSWORD', 'admin123'),
    ],

    'admin' => [
        'name' => env('ADMIN_NAME', 'Admin Akun'),
        'email' => env('ADMIN_EMAIL', 'admin@apjad.com'),
        'password' => env('ADMIN_PASSWORD', 'admin123'),
    ],
];

