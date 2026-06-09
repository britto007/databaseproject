<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'FlightBook';
$bodyClass = $bodyClass ?? '';
$user = current_user();
$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | FlightBook</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<header class="site-header">
    <div class="container header-inner">
        <a class="logo" href="<?= e(url('index.php')) ?>">FlightBook</a>
        <nav class="main-nav">
            <?php if ($user): ?>
                <span class="nav-user">Hello, <?= e($user['name']) ?></span>
                <?php if (is_admin()): ?>
                    <a href="<?= e(url('admin/flights.php')) ?>">Flights</a>
                <?php endif; ?>
                <a href="<?= e(url('dashboard.php')) ?>">Dashboard</a>
                <a href="<?= e(url('logout.php')) ?>" class="btn btn-outline btn-sm">Logout</a>
            <?php else: ?>
                <a href="<?= e(url('login.php')) ?>">Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container main-content">
