<?php
/**
 * Configuration — copy this file to `config.php` and fill in your values.
 * `config.php` is git-ignored so your secrets stay out of version control.
 */
return [
    // ----- Database (default XAMPP MySQL: user "root", empty password) -----
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'name'     => 'randy_db',
        'user'     => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],

    // ----- First admin account (created by setup.php) -----
    'admin' => [
        'email'     => 'admin@randyspaintdrywall.com',
        'password'  => 'changeme123',     // CHANGE after first login
        'full_name' => 'Randy Peay',
    ],

    // ----- Google Gemini (optional) -----
    // Leave empty to use the built-in scripted bot (no API key needed).
    'gemini' => [
        'api_key' => '',
        'model'   => 'gemini-1.5-flash',
    ],

    // ----- Email notifications (Gmail SMTP) -----
    // Leave app_password blank to disable (bookings still work; email is skipped).
    'email' => [
        'user'         => 'ranpaintingservices@gmail.com',
        'app_password' => '',   // 16-char Gmail App Password (no spaces)
        'to'           => 'ranpaintingservices@gmail.com',
    ],

    // Base URL path the app is served from (e.g. "/randy" under XAMPP htdocs).
    'base_path' => '/randy',
];
