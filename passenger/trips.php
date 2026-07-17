<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_passenger();

$user = current_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $bookId = (int) $_POST['cancel_id'];

    try {
        cancel_booking($bookId, (int) $user['pass_id']);
        flash_set('success', 'Booking #' . $bookId . ' has been cancelled.');
        redirect('passenger/trips.php');
    } catch (Throwable $e) {
        $errors[] = db_error_message($e);
    }
}

$bookings = [];
try {
    $bookings = db_fetch_all(
        "SELECT b.book_id, b.seat_no, b.status, b.fare,
                f.flight_id, f.dept_time,
                src.city AS source_city, dst.city AS dest_city,
                p.pay_method, p.status AS pay_status
         FROM bookings b
         JOIN flights f ON b.flight_id = f.flight_id
         JOIN routes r ON f.route_id = r.route_id
         JOIN airports src ON r.source_id = src.airp_id
         JOIN airports dst ON r.dest_id = dst.airp_id
         LEFT JOIN payments p ON b.book_id = p.book_id
         WHERE b.pass_id = :pass_id
         ORDER BY f.dept_time DESC",
        [':pass_id' => (int) $user['pass_id']]
    );
} catch (Throwable $e) {
    $errors[] = 'Unable to load your trips.';
}

$pageTitle = 'My Trips';
require __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <h1>My Trips</h1>
    <p>View and manage your flight bookings</p>
</section>

<?php if ($flash = flash_get()): ?>
    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<?php if (!$bookings): ?>
    <div class="card empty-state">
        <p>You have no bookings yet.</p>
        <a class="btn btn-primary" href="<?= e(url('passenger/search.php')) ?>">Search Flights</a>
    </div>
<?php else: ?>
    <div class="table-wrap card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Booking</th>
                    <th>Flight</th>
                    <th>Route</th>
                    <th>Departure</th>
                    <th>Seat</th>
                    <th>Fare</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking):
                    $bookId = (int) ($booking['BOOK_ID'] ?? $booking['book_id']);
                    $status = $booking['STATUS'] ?? $booking['status'];
                ?>
                    <tr>
                        <td>#<?= $bookId ?></td>
                        <td>#<?= e((string) ($booking['FLIGHT_ID'] ?? $booking['flight_id'])) ?></td>
                        <td><?= e(($booking['SOURCE_CITY'] ?? $booking['source_city']) . ' → ' . ($booking['DEST_CITY'] ?? $booking['dest_city'])) ?></td>
                        <td><?= e(format_oracle_ts($booking['DEPT_TIME'] ?? $booking['dept_time'])) ?></td>
                        <td><?= e($booking['SEAT_NO'] ?? $booking['seat_no']) ?></td>
                        <td>$<?= number_format((float) ($booking['FARE'] ?? $booking['fare']), 2) ?></td>
                        <td><span class="badge badge-<?= strtolower($status) === 'confirmed' ? 'success' : 'muted' ?>"><?= e($status) ?></span></td>
                        <td><?= e(($booking['PAY_METHOD'] ?? $booking['pay_method'] ?? '—') . ' / ' . ($booking['PAY_STATUS'] ?? $booking['pay_status'] ?? '—')) ?></td>
                        <td>
                            <?php if ($status === 'Confirmed'): ?>
                                <form method="post" action="<?= e(url('passenger/trips.php')) ?>" class="inline-form" onsubmit="return confirm('Cancel this booking?');">
                                    <input type="hidden" name="cancel_id" value="<?= $bookId ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                                </form>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
