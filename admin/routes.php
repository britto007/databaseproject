<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_admin();

$errors = [];
$editRoute = null;
$action = $_POST['action'] ?? '';

$airports = db_fetch_all(
    'SELECT airp_id, airport_name, city, country FROM airports ORDER BY city, airport_name'
);

if ($action === 'delete' && isset($_POST['route_id'])) {
    $routeId = (int) $_POST['route_id'];
    try {
        delete_route($routeId);
        flash_set('success', 'Route deleted successfully.');
        redirect('admin/routes.php');
    } catch (Throwable $e) {
        $errors[] = db_error_message($e);
    }
}

if ($action === 'save') {
    $routeId = (int) ($_POST['route_id'] ?? 0);
    $sourceId = (int) ($_POST['source_id'] ?? 0);
    $destId = (int) ($_POST['dest_id'] ?? 0);
    $distance = (float) ($_POST['distance'] ?? 0);

    if ($sourceId <= 0 || $destId <= 0) {
        $errors[] = 'Source and destination airports are required.';
    }
    if ($sourceId === $destId) {
        $errors[] = 'Source and destination must be different.';
    }
    if ($distance <= 0) {
        $errors[] = 'Distance must be greater than zero.';
    }

    if (!$errors) {
        try {
            if ($routeId > 0) {
                update_route($routeId, $sourceId, $destId, $distance);
                flash_set('success', 'Route updated successfully.');
            } else {
                add_route($sourceId, $destId, $distance);
                flash_set('success', 'Route created successfully.');
            }
            redirect('admin/routes.php');
        } catch (Throwable $e) {
            $errors[] = db_error_message($e);
        }
    }
}

if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    try {
        $editRoute = db_fetch_one(
            'SELECT route_id, source_id, dest_id, distance FROM routes WHERE route_id = :id',
            [':id' => $editId]
        );
    } catch (Throwable $e) {
        $errors[] = 'Unable to load route for editing.';
    }
}

$routes = [];
try {
    $routes = db_fetch_all(
        'SELECT r.route_id, r.distance,
                src.airp_id AS source_id, src.city AS source_city, src.airport_name AS source_name,
                dst.airp_id AS dest_id, dst.city AS dest_city, dst.airport_name AS dest_name
         FROM routes r
         JOIN airports src ON r.source_id = src.airp_id
         JOIN airports dst ON r.dest_id = dst.airp_id
         ORDER BY src.city, dst.city'
    );
} catch (Throwable $e) {
    $errors[] = 'Unable to load routes.';
}

$currentAdminPage = 'routes';
$pageTitle = 'Route Management';
require __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <h1>Route Management</h1>
    <p>Create, edit, and delete flight routes</p>
</section>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="admin-grid">
    <div class="card">
        <h2><?= $editRoute ? 'Edit Route' : 'Add Route' ?></h2>
        <form method="post" action="<?= e(url('admin/routes.php')) ?>" class="form" data-validate="route" novalidate>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="route_id" value="<?= e((string) ($editRoute['route_id'] ?? 0)) ?>">

            <div class="form-group">
                <label for="source_id">Source Airport</label>
                <select id="source_id" name="source_id" required>
                    <option value="">Select source</option>
                    <?php foreach ($airports as $airport):
                        $aid = (int) $airport['airp_id'];
                        $selected = $editRoute && $aid === (int) ($editRoute['source_id'] ?? 0);
                    ?>
                        <option value="<?= $aid ?>" <?= $selected ? 'selected' : '' ?>>
                            <?= e($airport['city'] . ' — ' . $airport['airport_name'] . ' (' . $airport['country'] . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="dest_id">Destination Airport</label>
                <select id="dest_id" name="dest_id" required>
                    <option value="">Select destination</option>
                    <?php foreach ($airports as $airport):
                        $aid = (int) $airport['airp_id'];
                        $selected = $editRoute && $aid === (int) ($editRoute['dest_id'] ?? 0);
                    ?>
                        <option value="<?= $aid ?>" <?= $selected ? 'selected' : '' ?>>
                            <?= e($airport['city'] . ' — ' . $airport['airport_name'] . ' (' . $airport['country'] . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="distance">Distance (miles)</label>
                <input type="number" id="distance" name="distance" min="0.01" step="0.01" required
                       value="<?= e((string) ($editRoute['distance'] ?? '')) ?>">
            </div>

            <button type="submit" class="btn btn-primary"><?= $editRoute ? 'Update Route' : 'Create Route' ?></button>
            <?php if ($editRoute): ?>
                <a class="btn btn-outline" href="<?= e(url('admin/routes.php')) ?>">Cancel Edit</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-wrap card">
        <h2>All Routes</h2>
        <?php if (!$routes): ?>
            <p class="empty">No routes found.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Route</th>
                        <th>Distance</th>
                        <th>Fare Est.</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($routes as $route):
                        $rid = (int) $route['route_id'];
                    ?>
                        <tr>
                            <td>#<?= $rid ?></td>
                            <td><?= e($route['source_city'] . ' -> ' . $route['dest_city']) ?></td>
                            <td><?= e(number_format((float) $route['distance'], 0)) ?> mi</td>
                            <td>$<?= e(number_format(calculate_fare($rid), 2)) ?></td>
                            <td class="actions">
                                <a class="btn btn-sm btn-outline" href="<?= e(url('admin/routes.php?edit=' . $rid)) ?>">Edit</a>
                                <form method="post" action="<?= e(url('admin/routes.php')) ?>" class="inline-form"
                                      onsubmit="return confirm('Delete this route?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="route_id" value="<?= $rid ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
