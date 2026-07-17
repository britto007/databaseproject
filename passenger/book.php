<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_passenger();

$user = current_user();
$flightId = (int) ($_GET['flight_id'] ?? $_POST['flight_id'] ?? 0);
$errors = [];
$flight = null;
$fare = 0.0;
$takenSeats = [];

if ($flightId <= 0) {
    flash_set('error', 'Invalid flight selected.');
    redirect('passenger/search.php');
}

try {
    $flight = db_fetch_one(
        "SELECT f.flight_id, f.dept_time, f.seats_avail, f.route_id,
                r.distance, r.source_id, r.dest_id,
                src.airport_name AS source_name, src.city AS source_city,
                dst.airport_name AS dest_name, dst.city AS dest_city,
                a.model, a.capacity, a.tail_no
         FROM flights f
         JOIN routes r ON f.route_id = r.route_id
         JOIN airports src ON r.source_id = src.airp_id
         JOIN airports dst ON r.dest_id = dst.airp_id
         JOIN aircraft a ON f.tail_no = a.tail_no
         WHERE f.flight_id = :flight_id AND f.seats_avail > 0",
        [':flight_id' => $flightId]
    );

    if (!$flight) {
        flash_set('error', 'Flight not available.');
        redirect('passenger/search.php');
    }

    $routeId = (int) ($flight['ROUTE_ID'] ?? $flight['route_id']);
    $fare = calculate_fare($routeId);

    $seatRows = db_fetch_all(
        "SELECT seat_no FROM bookings
         WHERE flight_id = :flight_id AND status = 'Confirmed'",
        [':flight_id' => $flightId]
    );
    foreach ($seatRows as $row) {
        $takenSeats[] = $row['SEAT_NO'] ?? $row['seat_no'];
    }
} catch (Throwable $e) {
    flash_set('error', 'Unable to load flight details.');
    redirect('passenger/search.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookId = (int) ($_POST['book_id'] ?? 0);
    $payId = (int) ($_POST['pay_id'] ?? 0);
    $seatNo = strtoupper(trim($_POST['seat_no'] ?? ''));
    $payMethod = trim($_POST['pay_method'] ?? '');

    if ($bookId <= 0) {
        $errors[] = 'Booking ID is required.';
    }
    if ($payId <= 0) {
        $errors[] = 'Payment ID is required.';
    }
    if (!preg_match('/^[0-9]{1,2}[A-F]$/', $seatNo)) {
        $errors[] = 'Seat must be in format like 12A (row + letter A-F).';
    }
    if (in_array($seatNo, $takenSeats, true)) {
        $errors[] = 'Selected seat is already booked.';
    }
    $allowedMethods = ['Credit Card', 'Debit Card', 'PayPal', 'Cash'];
    if (!in_array($payMethod, $allowedMethods, true)) {
        $errors[] = 'Please select a valid payment method.';
    }

    if (!$errors) {
        try {
            db()->beginTransaction();

            process_booking($bookId, (int) $user['pass_id'], $flightId, $seatNo, $fare);
            process_payment($payId, $bookId, $fare, $payMethod);

            db()->commit();
            flash_set('success', 'Booking confirmed! Reference #' . $bookId);
            redirect('passenger/trips.php');
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            $errors[] = db_error_message($e);
        }
    }
}

$pageTitle = 'Book Flight';
require __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <h1>Confirm Booking</h1>
    <p>Review flight details and complete payment</p>
</section>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="booking-layout">
    <div class="card booking-summary">
        <h2>Flight #<?= e((string) ($flight['FLIGHT_ID'] ?? $flight['flight_id'])) ?></h2>
        <dl class="detail-list">
            <dt>Route</dt>
            <dd><?= e(($flight['SOURCE_CITY'] ?? $flight['source_city']) . ' → ' . ($flight['DEST_CITY'] ?? $flight['dest_city'])) ?></dd>
            <dt>Departure</dt>
            <dd><?= e(format_oracle_ts($flight['DEPT_TIME'] ?? $flight['dept_time'])) ?></dd>
            <dt>Aircraft</dt>
            <dd><?= e($flight['MODEL'] ?? $flight['model']) ?> (<?= e($flight['TAIL_NO'] ?? $flight['tail_no']) ?>)</dd>
            <dt>Distance</dt>
            <dd><?= e((string) ($flight['DISTANCE'] ?? $flight['distance'])) ?> miles</dd>
            <dt>Seats Available</dt>
            <dd><?= e((string) ($flight['SEATS_AVAIL'] ?? $flight['seats_avail'])) ?></dd>
            <dt>Fare</dt>
            <dd class="fare">$<?= number_format($fare, 2) ?></dd>
        </dl>
    </div>

    <div class="card">
        <form method="post" action="<?= e(url('passenger/book.php')) ?>" class="form" data-validate="booking" novalidate>
            <input type="hidden" name="flight_id" value="<?= $flightId ?>">
            <div class="form-group">
                <label for="book_id">Booking ID</label>
                <input type="number" id="book_id" name="book_id" min="1" required>
            </div>
            <div class="form-group">
                <label for="pay_id">Payment ID</label>
                <input type="number" id="pay_id" name="pay_id" min="1" required>
            </div>
            <div class="form-group">
                <label for="seat_no">Seat Number</label>
                <input type="text" id="seat_no" name="seat_no" placeholder="e.g. 12A" required maxlength="3" pattern="[0-9]{1,2}[A-Fa-f]">
                <small>Format: row number + seat letter (A-F). Taken: <?= e(implode(', ', $takenSeats) ?: 'none') ?></small>
            </div>
            <div class="form-group">
                <label for="pay_method">Payment Method</label>
                <select id="pay_method" name="pay_method" required>
                    <option value="">Select method</option>
                    <option value="Credit Card">Credit Card</option>
                    <option value="Debit Card">Debit Card</option>
                    <option value="PayPal">PayPal</option>
                    <option value="Cash">Cash</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Pay &amp; Confirm Booking</button>
            <a class="btn btn-outline btn-block" href="<?= e(url('passenger/search.php')) ?>">Back to Search</a>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
