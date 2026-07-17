<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_passenger();

$airports = [];
$flights = [];
$errors = [];
$sourceId = (int) ($_GET['source_id'] ?? $_POST['source_id'] ?? 0);
$destId = (int) ($_GET['dest_id'] ?? $_POST['dest_id'] ?? 0);
$travelDate = trim($_GET['travel_date'] ?? $_POST['travel_date'] ?? '');

try {
    $airports = db_fetch_all(
        'SELECT airp_id, airport_name, city, country FROM airports ORDER BY city'
    );
} catch (Throwable $e) {
    $errors[] = 'Unable to load airports.';
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($sourceId || $destId || $travelDate !== '')) {
    if ($sourceId <= 0) {
        $errors[] = 'Please select a departure airport.';
    }
    if ($destId <= 0) {
        $errors[] = 'Please select a destination airport.';
    }
    if ($sourceId > 0 && $destId > 0 && $sourceId === $destId) {
        $errors[] = 'Departure and destination must be different.';
    }
    if ($travelDate === '') {
        $errors[] = 'Please select a travel date.';
    }

    if (!$errors) {
        try {
            $flights = db_fetch_all(
                "SELECT f.flight_id, f.dept_time, f.seats_avail,
                        r.distance, r.route_id,
                        src.airport_name AS source_name, src.city AS source_city,
                        dst.airport_name AS dest_name, dst.city AS dest_city,
                        a.model, a.capacity, a.tail_no
                 FROM flights f
                 JOIN routes r ON f.route_id = r.route_id
                 JOIN airports src ON r.source_id = src.airp_id
                 JOIN airports dst ON r.dest_id = dst.airp_id
                 JOIN aircraft a ON f.tail_no = a.tail_no
                 WHERE r.source_id = :source_id
                   AND r.dest_id = :dest_id
                   AND TRUNC(f.dept_time) = TO_DATE(:travel_date, 'YYYY-MM-DD')
                   AND f.seats_avail > 0
                 ORDER BY f.dept_time",
                [
                    ':source_id'   => $sourceId,
                    ':dest_id'     => $destId,
                    ':travel_date' => $travelDate,
                ]
            );

            foreach ($flights as &$flight) {
                $routeId = (int) ($flight['ROUTE_ID'] ?? $flight['route_id']);
                $flight['fare'] = calculate_fare($routeId);
            }
            unset($flight);

            if (!$flights) {
                flash_set('info', 'No flights found for the selected route and date.');
            }
        } catch (Throwable $e) {
            $errors[] = 'Search failed. Please try again.';
        }
    }
}

$pageTitle = 'Search Flights';
require __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <h1>Search Flights</h1>
    <p>Find available flights by route and departure date</p>
</section>

<?php if ($flash = flash_get()): ?>
    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="card search-form-card">
    <form method="get" action="<?= e(url('passenger/search.php')) ?>" class="form form-inline-grid" data-validate="search" novalidate>
        <div class="form-group">
            <label for="source_id">From</label>
            <select id="source_id" name="source_id" required>
                <option value="">Select airport</option>
                <?php foreach ($airports as $airport):
                    $id = (int) ($airport['AIRP_ID'] ?? $airport['airp_id']);
                    $label = ($airport['CITY'] ?? $airport['city']) . ' — ' . ($airport['AIRPORT_NAME'] ?? $airport['airport_name']);
                ?>
                    <option value="<?= $id ?>" <?= $sourceId === $id ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="dest_id">To</label>
            <select id="dest_id" name="dest_id" required>
                <option value="">Select airport</option>
                <?php foreach ($airports as $airport):
                    $id = (int) ($airport['AIRP_ID'] ?? $airport['airp_id']);
                    $label = ($airport['CITY'] ?? $airport['city']) . ' — ' . ($airport['AIRPORT_NAME'] ?? $airport['airport_name']);
                ?>
                    <option value="<?= $id ?>" <?= $destId === $id ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="travel_date">Date</label>
            <input type="date" id="travel_date" name="travel_date" value="<?= e($travelDate) ?>" required>
        </div>
        <div class="form-group form-actions">
            <button type="submit" class="btn btn-primary">Search</button>
        </div>
    </form>
</div>

<?php if ($flights): ?>
    <div class="table-wrap card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Flight</th>
                    <th>Route</th>
                    <th>Departure</th>
                    <th>Aircraft</th>
                    <th>Seats</th>
                    <th>Fare</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($flights as $flight):
                    $flightId = (int) ($flight['FLIGHT_ID'] ?? $flight['flight_id']);
                    $deptTime = $flight['DEPT_TIME'] ?? $flight['dept_time'];
                ?>
                    <tr>
                        <td>#<?= $flightId ?></td>
                        <td><?= e(($flight['SOURCE_CITY'] ?? $flight['source_city']) . ' → ' . ($flight['DEST_CITY'] ?? $flight['dest_city'])) ?></td>
                        <td><?= e(format_oracle_ts($deptTime)) ?></td>
                        <td><?= e($flight['MODEL'] ?? $flight['model']) ?> (<?= e($flight['TAIL_NO'] ?? $flight['tail_no']) ?>)</td>
                        <td><?= e((string) ($flight['SEATS_AVAIL'] ?? $flight['seats_avail'])) ?></td>
                        <td>$<?= number_format((float) $flight['fare'], 2) ?></td>
                        <td><a class="btn btn-sm btn-primary" href="<?= e(url('passenger/book.php?flight_id=' . $flightId)) ?>">Book</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
