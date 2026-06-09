<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_login();

$user = current_user();

if (is_admin()) {
    redirect('admin/flights.php');
}

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>

<section class="dashboard">
    <?php if ($flash = flash_get()): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <div class="hero card">
        <h1>Welcome, <?= e($user['name']) ?></h1>
        <p>You are logged in as a passenger.</p>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
