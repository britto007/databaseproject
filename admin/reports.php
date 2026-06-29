<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_admin();

$errors = [];
$revenueByMonth = [];
$popularRoutes = [];
$occupancy = [];

try {
    $revenueByMonth = get_revenue_by_month();
} catch (Throwable $e) {
    $errors[] = 'Unable to load revenue report.';
}

try {
    $popularRoutes = get_popular_routes();
} catch (Throwable $e) {
    $errors[] = 'Unable to load popular routes report.';
}

try {
    $occupancy = get_flight_occupancy();
} catch (Throwable $e) {
    $errors[] = 'Unable to load occupancy report.';
}

$totalRevenue = 0.0;
foreach ($revenueByMonth as $row) {
    $totalRevenue += (float) ($row['total_revenue'] ?? 0);
}

$currentAdminPage = 'reports';
$pageTitle = 'Reports';
require __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <h1>Reports</h1>
    <p>Revenue, route popularity, and flight occupancy</p>
</section>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="reports-grid">
    <div class="card report-wide">
        <h2>Total Revenue by Month</h2>
        <p class="subtitle">Based on completed payments (grouped by flight departure month)</p>
        <?php if (!$revenueByMonth): ?>
            <p class="empty">No completed payment data yet.</p>
        <?php else: ?>
            <p class="report-summary">Overall revenue: <strong>$<?= e(number_format($totalRevenue, 2)) ?></strong></p>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Payments</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($revenueByMonth as $row): ?>
                            <tr>
                                <td><?= e($row['month_label']) ?></td>
                                <td><?= e((string) ($row['payment_count'] ?? 0)) ?></td>
                                <td>$<?= e(number_format((float) ($row['total_revenue'] ?? 0), 2)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Most Popular Routes</h2>
        <p class="subtitle">Confirmed bookings per route</p>
        <?php if (!$popularRoutes): ?>
            <p class="empty">No booking data yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Route</th>
                            <th>Bookings</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($popularRoutes as $row): ?>
                            <tr>
                                <td><?= e($row['source_city'] . ' -> ' . $row['dest_city']) ?></td>
                                <td><?= e((string) ($row['booking_count'] ?? 0)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="card report-wide">
        <h2>Flight Occupancy</h2>
        <p class="subtitle">Percentage of seats booked vs aircraft capacity</p>
        <?php if (!$occupancy): ?>
            <p class="empty">No flights found.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Flight</th>
                            <th>Departure</th>
                            <th>Booked</th>
                            <th>Occupancy</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($occupancy as $row):
                            $pct = (float) ($row['occupancy_pct'] ?? 0);
                        ?>
                            <tr>
                                <td><?= e('#' . ($row['flight_id'] ?? '') . ' — ' . $row['source_city'] . ' -> ' . $row['dest_city']) ?></td>
                                <td><?= e(format_db_datetime($row['dept_time'] ?? '')) ?></td>
                                <td><?= e((string) ($row['seats_booked'] ?? 0)) ?> / <?= e((string) ($row['capacity'] ?? 0)) ?></td>
                                <td>
                                    <span class="progress-bar" aria-hidden="true">
                                        <span class="progress-fill" style="width: <?= e(min(100, max(0, $pct))) ?>%"></span>
                                    </span>
                                    <?= e(number_format($pct, 1)) ?>%
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
