<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_admin();

$stats = [
    'airports' => 0,
    'routes'   => 0,
    'aircraft' => 0,
    'flights'  => 0,
    'bookings' => 0,
];

try {
    $stats = get_admin_stats();
} catch (Throwable $e) {
    flash_set('error', 'Unable to load dashboard statistics.');
}

$currentAdminPage = 'home';
$pageTitle = 'Admin Dashboard';
require __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <h1>Admin Dashboard</h1>
    <p>Manage flights, routes, aircraft, airports, and view reports</p>
</section>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-value"><?= e((string) $stats['airports']) ?></span>
        <span class="stat-label">Airports</span>
    </div>
    <div class="stat-card">
        <span class="stat-value"><?= e((string) $stats['routes']) ?></span>
        <span class="stat-label">Routes</span>
    </div>
    <div class="stat-card">
        <span class="stat-value"><?= e((string) $stats['aircraft']) ?></span>
        <span class="stat-label">Aircraft</span>
    </div>
    <div class="stat-card">
        <span class="stat-value"><?= e((string) $stats['flights']) ?></span>
        <span class="stat-label">Flights</span>
    </div>
    <div class="stat-card">
        <span class="stat-value"><?= e((string) $stats['bookings']) ?></span>
        <span class="stat-label">Active Bookings</span>
    </div>
</div>

<div class="action-grid">
    <a class="action-card" href="<?= e(url('admin/flights.php')) ?>">
        <h3>Flights</h3>
        <p>Create, edit, and delete scheduled flights.</p>
    </a>
    <a class="action-card" href="<?= e(url('admin/routes.php')) ?>">
        <h3>Routes</h3>
        <p>Manage source, destination, and distance.</p>
    </a>
    <a class="action-card" href="<?= e(url('admin/aircraft.php')) ?>">
        <h3>Aircraft</h3>
        <p>Maintain tail numbers, models, and capacity.</p>
    </a>
    <a class="action-card" href="<?= e(url('admin/airports.php')) ?>">
        <h3>Airports</h3>
        <p>Add and update airport locations.</p>
    </a>
    <a class="action-card" href="<?= e(url('admin/reports.php')) ?>">
        <h3>Reports</h3>
        <p>Revenue, popular routes, and occupancy.</p>
    </a>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
