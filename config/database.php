<?php
/**
 * Database configuration for MySQL connection.
 * Copy config/database.example.php to config/database.local.php and edit credentials.
 */

declare(strict_types=1);

$configFile = __DIR__ . '/database.local.php';
if (file_exists($configFile)) {
    return require $configFile;
}

return [
    'host'      => 'localhost',
    'port'      => '3306',
    'database'  => 'flightbook1',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8mb4',
    'base_path' => '/demo',
];
