<?php
/**
 * Konfigurasi aplikasi — path & database
 */
define('APP_ROOT', dirname(__DIR__));

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'passwordbaru');
define('DB_NAME', 'siakad_lms');

function base_url(string $path = ''): string
{
    static $base = null;
    if ($base === null) {
        $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
        $appRoot = str_replace('\\', '/', realpath(APP_ROOT));
        $base = rtrim(str_replace($docRoot, '', $appRoot), '/');
        if ($base === '') {
            $base = '';
        }
    }
    $path = ltrim(str_replace('\\', '/', $path), '/');
    return $base . ($path !== '' ? '/' . $path : '');
}

function asset(string $path): string
{
    return base_url('assets/' . ltrim($path, '/'));
}

function redirect_to(string $path): void
{
    $url = str_starts_with($path, 'http') ? $path : base_url($path);
    header('Location: ' . $url);
    exit;
}
