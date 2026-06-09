<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || !is_valid_email($email)) {
        $errors[] = 'Valid email is required.';
    }
    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (!$errors) {
        try {
            $user = authenticate($email, $password);
            if ($user) {
                login_user($user);
                flash_set('success', 'Welcome back, ' . ($user['NAME'] ?? $user['name']) . '!');
                redirect('dashboard.php');
            }
            $errors[] = 'Invalid email or password.';
        } catch (Throwable $e) {
            $errors[] = 'Login failed. Please try again later.';
        }
    }
}

$pageTitle = 'Login';
require __DIR__ . '/includes/header.php';
?>

<section class="auth-card card">
    <h1>Sign In</h1>
    <p class="subtitle">Login to access your account</p>

    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('login.php')) ?>" class="form" novalidate>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>" required autocomplete="email">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Login</button>
    </form>

    <p class="hint">Demo admin: admin@flightbook.com / password</p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
