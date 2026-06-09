<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_admin();

$errors = [];
$success = '';
$editFlight = null;
$action = $_POST['action'] ?? '';

$routes = db_fetch_all(
    'SELECT r.route_id, r.distance,
            src.city AS source_city, dst.city AS dest_city
     FROM routes r
     JOIN airports src ON r.source_id = src.airp_id
     JOIN airports dst ON r.dest_id = dst.airp_id
     ORDER BY src.city, dst.city'
);

$aircraft = db_fetch_all('SELECT tail_no, model, capacity FROM aircraft ORDER BY tail_no');

if ($action === 'delete' && isset($_POST['flight_id'])) {
    $flightId = (int) $_POST['flight_id'];
    try {
        $bookingCount = db_fetch_one(
            "SELECT COUNT(*) AS cnt FROM bookings WHERE flight_id = :id AND status = 'Confirmed'",
            [':id' => $flightId]
        );
        $count = (int) ($bookingCount['CNT'] ?? $bookingCount['cnt'] ?? 0);
        if ($count > 0) {
            $errors[] = 'Cannot delete flight with active bookings.';
        } else {
            db_query('DELETE FROM flights WHERE flight_id = :id', [':id' => $flightId]);
            flash_set('success', 'Flight deleted successfully.');
            redirect('admin/flights.php');
        }
    } catch (Throwable $e) {
        $errors[] = 'Unable to delete flight.';
    }
}

if ($action === 'save') {
    $flightId = (int) ($_POST['flight_id'] ?? 0);
    $routeId = (int) ($_POST['route_id'] ?? 0);
    $tailNo = trim($_POST['tail_no'] ?? '');
    $deptTime = trim($_POST['dept_time'] ?? '');
    $seatsAvail = (int) ($_POST['seats_avail'] ?? 0);

    if ($routeId <= 0) {
        $errors[] = 'Route is required.';
    }
    if ($tailNo === '') {
        $errors[] = 'Aircraft is required.';
    }
    if ($deptTime === '') {
        $errors[] = 'Departure time is required.';
    }
    if ($seatsAvail < 0) {
        $errors[] = 'Seats available cannot be negative.';
    }

    if (!$errors) {
        try {
            $deptTime = normalize_datetime($deptTime);

            if ($flightId > 0) {
                db_query(
                    'UPDATE flights SET route_id = :route_id, tail_no = :tail_no,
                     dept_time = :dept_time, seats_avail = :seats_avail
                     WHERE flight_id = :flight_id',
                    [
                        ':route_id'    => $routeId,
                        ':tail_no'     => $tailNo,
                        ':dept_time'   => $deptTime,
                        ':seats_avail' => $seatsAvail,
                        ':flight_id'   => $flightId,
                    ]
                );
                flash_set('success', 'Flight updated successfully.');
            } else {
                db_query(
                    'INSERT INTO flights (route_id, tail_no, dept_time, seats_avail)
                     VALUES (:route_id, :tail_no, :dept_time, :seats_avail)',
                    [
                        ':route_id'    => $routeId,
                        ':tail_no'     => $tailNo,
                        ':dept_time'   => $deptTime,
                        ':seats_avail' => $seatsAvail,
                    ]
                );
                flash_set('success', 'Flight created successfully.');
            }
            redirect('admin/flights.php');
        } catch (Throwable $e) {
            $errors[] = 'Unable to save flight.';
        }
    }
}

if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    try {
        $editFlight = db_fetch_one(
            'SELECT flight_id, route_id, tail_no, dept_time, seats_avail FROM flights WHERE flight_id = :id',
            [':id' => $editId]
        );
    } catch (Throwable $e) {
        $errors[] = 'Unable to load flight for editing.';
    }
}

$flights = [];
try {
    $flights = db_fetch_all(
        "SELECT f.flight_id, f.dept_time, f.seats_avail, f.tail_no,
                r.route_id, r.distance,
                src.city AS source_city, dst.city AS dest_city,
                a.model, a.capacity
         FROM flights f
         JOIN routes r ON f.route_id = r.route_id
         JOIN airports src ON r.source_id = src.airp_id
         JOIN airports dst ON r.dest_id = dst.airp_id
         JOIN aircraft a ON f.tail_no = a.tail_no
         ORDER BY f.dept_time DESC"
    );
} catch (Throwable $e) {
    $errors[] = 'Unable to load flights.';
}

$pageTitle = 'Flight Management';
require __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <h1>Flight Management</h1>
    <p>Create, edit, and delete flights</p>
</section>

<?php if ($flash = flash_get()): ?>
    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="admin-grid">
    <div class="card">
        <h2><?= $editFlight ? 'Edit Flight' : 'Add Flight' ?></h2>
        <form method="post" action="<?= e(url('admin/flights.php')) ?>" class="form" data-validate="flight" novalidate>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="flight_id" value="<?= e((string) ($editFlight['FLIGHT_ID'] ?? $editFlight['flight_id'] ?? 0)) ?>">

            <div class="form-group">
                <label for="route_id">Route</label>
                <select id="route_id" name="route_id" required>
                    <option value="">Select route</option>
                    <?php foreach ($routes as $route):
                        $rid = (int) ($route['ROUTE_ID'] ?? $route['route_id']);
                        $selected = $editFlight && $rid === (int) ($editFlight['ROUTE_ID'] ?? $editFlight['route_id']);
                    ?>
                        <option value="<?= $rid ?>" <?= $selected ? 'selected' : '' ?>>
                            <?= e(($route['SOURCE_CITY'] ?? $route['source_city']) . ' → ' . ($route['DEST_CITY'] ?? $route['dest_city']) . ' (' . ($route['DISTANCE'] ?? $route['distance']) . ' mi)') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="tail_no">Aircraft</label>
                <select id="tail_no" name="tail_no" required>
                    <option value="">Select aircraft</option>
                    <?php foreach ($aircraft as $plane):
                        $tail = $plane['TAIL_NO'] ?? $plane['tail_no'];
                        $selected = $editFlight && $tail === ($editFlight['TAIL_NO'] ?? $editFlight['tail_no']);
                    ?>
                        <option value="<?= e($tail) ?>" <?= $selected ? 'selected' : '' ?>>
                            <?= e($tail . ' — ' . ($plane['MODEL'] ?? $plane['model']) . ' (' . ($plane['CAPACITY'] ?? $plane['capacity']) . ' seats)') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="dept_time">Departure</label>
                <input type="datetime-local" id="dept_time" name="dept_time" required
                       value="<?php
                       if ($editFlight) {
                           $dt = $editFlight['DEPT_TIME'] ?? $editFlight['dept_time'];
                           echo e(date('Y-m-d\TH:i', strtotime((string) $dt)));
                       }
                       ?>">
            </div>

            <div class="form-group">
                <label for="seats_avail">Seats Available</label>
                <input type="number" id="seats_avail" name="seats_avail" min="0" required
                       value="<?= e((string) ($editFlight['SEATS_AVAIL'] ?? $editFlight['seats_avail'] ?? '')) ?>">
            </div>

            <button type="submit" class="btn btn-primary"><?= $editFlight ? 'Update Flight' : 'Create Flight' ?></button>
            <?php if ($editFlight): ?>
                <a class="btn btn-outline" href="<?= e(url('admin/flights.php')) ?>">Cancel Edit</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-wrap card">
        <h2>All Flights</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Route</th>
                    <th>Departure</th>
                    <th>Aircraft</th>
                    <th>Seats</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($flights as $flight):
                    $fid = (int) ($flight['FLIGHT_ID'] ?? $flight['flight_id']);
                ?>
                    <tr>
                        <td>#<?= $fid ?></td>
                        <td><?= e(($flight['SOURCE_CITY'] ?? $flight['source_city']) . ' → ' . ($flight['DEST_CITY'] ?? $flight['dest_city'])) ?></td>
                        <td><?= e(date('M j, Y g:i A', strtotime((string) ($flight['DEPT_TIME'] ?? $flight['dept_time'])))) ?></td>
                        <td><?= e($flight['MODEL'] ?? $flight['model']) ?></td>
                        <td><?= e((string) ($flight['SEATS_AVAIL'] ?? $flight['seats_avail'])) ?> / <?= e((string) ($flight['CAPACITY'] ?? $flight['capacity'])) ?></td>
                        <td class="actions">
                            <a class="btn btn-sm btn-outline" href="<?= e(url('admin/flights.php?edit=' . $fid)) ?>">Edit</a>
                            <form method="post" action="<?= e(url('admin/flights.php')) ?>" class="inline-form" onsubmit="return confirm('Delete this flight?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="flight_id" value="<?= $fid ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
