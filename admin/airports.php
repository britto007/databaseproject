<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_admin();

$errors = [];
$editAirport = null;
$action = $_POST['action'] ?? '';

if ($action === 'delete' && isset($_POST['airp_id'])) {
    $airpId = (int) $_POST['airp_id'];
    try {
        delete_airport($airpId);
        flash_set('success', 'Airport deleted successfully.');
        redirect('admin/airports.php');
    } catch (Throwable $e) {
        $errors[] = db_error_message($e);
    }
}

if ($action === 'save') {
    $airpId = (int) ($_POST['airp_id'] ?? 0);
    $name = trim($_POST['airport_name'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $country = trim($_POST['country'] ?? '');

    if ($name === '') {
        $errors[] = 'Airport name is required.';
    }
    if ($city === '') {
        $errors[] = 'City is required.';
    }
    if ($country === '') {
        $errors[] = 'Country is required.';
    }

    if (!$errors) {
        try {
            if ($airpId > 0) {
                update_airport($airpId, $name, $city, $country);
                flash_set('success', 'Airport updated successfully.');
            } else {
                add_airport($name, $city, $country);
                flash_set('success', 'Airport created successfully.');
            }
            redirect('admin/airports.php');
        } catch (Throwable $e) {
            $errors[] = db_error_message($e);
        }
    }
}

if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    try {
        $editAirport = db_fetch_one(
            'SELECT airp_id, airport_name, city, country FROM airports WHERE airp_id = :id',
            [':id' => $editId]
        );
    } catch (Throwable $e) {
        $errors[] = 'Unable to load airport for editing.';
    }
}

$airports = [];
try {
    $airports = db_fetch_all(
        'SELECT airp_id, airport_name, city, country FROM airports ORDER BY country, city, airport_name'
    );
} catch (Throwable $e) {
    $errors[] = 'Unable to load airports.';
}

$currentAdminPage = 'airports';
$pageTitle = 'Airport Management';
require __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <h1>Airport Management</h1>
    <p>Create, edit, and delete airports</p>
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
        <h2><?= $editAirport ? 'Edit Airport' : 'Add Airport' ?></h2>
        <form method="post" action="<?= e(url('admin/airports.php')) ?>" class="form" novalidate>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="airp_id" value="<?= e((string) ($editAirport['airp_id'] ?? 0)) ?>">

            <div class="form-group">
                <label for="airport_name">Airport Name</label>
                <input type="text" id="airport_name" name="airport_name" required maxlength="150"
                       value="<?= e($editAirport['airport_name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="city">City</label>
                <input type="text" id="city" name="city" required maxlength="100"
                       value="<?= e($editAirport['city'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="country">Country</label>
                <input type="text" id="country" name="country" required maxlength="100"
                       value="<?= e($editAirport['country'] ?? '') ?>">
            </div>

            <button type="submit" class="btn btn-primary"><?= $editAirport ? 'Update Airport' : 'Create Airport' ?></button>
            <?php if ($editAirport): ?>
                <a class="btn btn-outline" href="<?= e(url('admin/airports.php')) ?>">Cancel Edit</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-wrap card">
        <h2>All Airports</h2>
        <?php if (!$airports): ?>
            <p class="empty">No airports found.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Airport</th>
                        <th>City</th>
                        <th>Country</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($airports as $airport):
                        $id = (int) $airport['airp_id'];
                    ?>
                        <tr>
                            <td>#<?= $id ?></td>
                            <td><?= e($airport['airport_name']) ?></td>
                            <td><?= e($airport['city']) ?></td>
                            <td><?= e($airport['country']) ?></td>
                            <td class="actions">
                                <a class="btn btn-sm btn-outline" href="<?= e(url('admin/airports.php?edit=' . $id)) ?>">Edit</a>
                                <form method="post" action="<?= e(url('admin/airports.php')) ?>" class="inline-form"
                                      onsubmit="return confirm('Delete this airport?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="airp_id" value="<?= $id ?>">
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
