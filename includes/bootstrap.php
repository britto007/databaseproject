<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$config = require APP_ROOT . '/config/database.php';
define('APP_BASE', rtrim($config['base_path'] ?? '', '/'));

require_once APP_ROOT . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once APP_ROOT . '/includes/auth.php';

/**
 * Build application URL from site root.
 */
function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    if (APP_BASE === '') {
        return '/' . $path;
    }
    return APP_BASE . '/' . $path;
}
