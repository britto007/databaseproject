<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_admin();

$errors = [];
$editAircraft = null;
$action = $_POST['action'] ?? '';

if ($action === 'delete' && isset($_POST['tail_no'])) {
    $tailNo = trim($_POST['tail_no'] ?? '');
    try {
        delete_aircraft($tailNo);
        flash_set('success', 'Aircraft deleted successfully.');
        redirect('admin/aircraft.php');
    } catch (Throwable $e) {
        $errors[] = db_error_message($e);
    }
}

if ($action === 'save') {
    $originalTail = trim($_POST['original_tail_no'] ?? '');
    $tailNo = trim($_POST['tail_no'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $capacity = (int) ($_POST['capacity'] ?? 0);
    $isEdit = $originalTail !== '';

    if ($tailNo === '') {
        $errors[] = 'Tail number is required.';
    }
    if ($model === '') {
        $errors[] = 'Model is required.';
    }
    if ($capacity <= 0) {
        $errors[] = 'Capacity must be greater than zero.';
    }

    if (!$errors) {
        try {
            if ($isEdit) {
                update_aircraft($originalTail, $model, $capacity);
                flash_set('success', 'Aircraft updated successfully.');
            } else {
                add_aircraft($tailNo, $model, $capacity);
                flash_set('success', 'Aircraft created successfully.');
            }
            redirect('admin/aircraft.php');
        } catch (Throwable $e) {
            $errors[] = db_error_message($e);
        }
    }
}

if (isset($_GET['edit'])) {
    $tail = trim($_GET['edit']);
    try {
        $editAircraft = db_fetch_one(
            'SELECT tail_no, model, capacity FROM aircraft WHERE tail_no = :tail_no',
            [':tail_no' => $tail]
        );
    } catch (Throwable $e) {
        $errors[] = 'Unable to load aircraft for editing.';
    }
}

$aircraftList = [];
try {
    $aircraftList = db_fetch_all(
        'SELECT tail_no, model, capacity FROM aircraft ORDER BY tail_no'
    );
} catch (Throwable $e) {
    $errors[] = 'Unable to load aircraft.';
}

$currentAdminPage = 'aircraft';
$pageTitle = 'Aircraft Management';
require __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <h1>Aircraft Management</h1>
    <p>Create, edit, and delete aircraft</p>
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
        <h2><?= $editAircraft ? 'Edit Aircraft' : 'Add Aircraft' ?></h2>
        <form method="post" action="<?= e(url('admin/aircraft.php')) ?>" class="form" novalidate>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="original_tail_no" value="<?= e($editAircraft['tail_no'] ?? '') ?>">

            <div class="form-group">
                <label for="tail_no">Tail Number</label>
                <input type="text" id="tail_no" name="tail_no" required maxlength="20"
                       value="<?= e($editAircraft['tail_no'] ?? '') ?>"
                       <?= $editAircraft ? 'readonly' : '' ?>>
            </div>

            <div class="form-group">
                <label for="model">Model</label>
                <input type="text" id="model" name="model" required maxlength="100"
                       value="<?= e($editAircraft['model'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="capacity">Capacity</label>
                <input type="number" id="capacity" name="capacity" min="1" required
                       value="<?= e((string) ($editAircraft['capacity'] ?? '')) ?>">
            </div>

            <button type="submit" class="btn btn-primary"><?= $editAircraft ? 'Update Aircraft' : 'Create Aircraft' ?></button>
            <?php if ($editAircraft): ?>
                <a class="btn btn-outline" href="<?= e(url('admin/aircraft.php')) ?>">Cancel Edit</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-wrap card">
        <h2>All Aircraft</h2>
        <?php if (!$aircraftList): ?>
            <p class="empty">No aircraft found.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tail No</th>
                        <th>Model</th>
                        <th>Capacity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($aircraftList as $plane): ?>
                        <tr>
                            <td><?= e($plane['tail_no']) ?></td>
                            <td><?= e($plane['model']) ?></td>
                            <td><?= e((string) $plane['capacity']) ?></td>
                            <td class="actions">
                                <a class="btn btn-sm btn-outline"
                                   href="<?= e(url('admin/aircraft.php?edit=' . rawurlencode($plane['tail_no']))) ?>">Edit</a>
                                <form method="post" action="<?= e(url('admin/aircraft.php')) ?>" class="inline-form"
                                      onsubmit="return confirm('Delete this aircraft?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="tail_no" value="<?= e($plane['tail_no']) ?>">
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
