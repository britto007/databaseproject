<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $user = current_user();
    return $user !== null && ($user['role'] ?? '') === 'admin';
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash_set('error', 'Please log in to continue.');
        redirect('login.php');
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        flash_set('error', 'Administrator access required.');
        redirect('dashboard.php');
    }
}

function require_passenger(): void
{
    require_login();
    if (is_admin()) {
        redirect('admin/flights.php');
    }
}

function login_user(array $user): void
{
    $_SESSION['user'] = [
        'pass_id' => (int) ($user['PASS_ID'] ?? $user['pass_id'] ?? 0),
        'name'    => $user['NAME'] ?? $user['name'] ?? '',
        'email'   => $user['EMAIL'] ?? $user['email'] ?? '',
        'phone'   => $user['PHONE'] ?? $user['phone'] ?? '',
        'role'    => $user['ROLE'] ?? $user['role'] ?? 'passenger',
    ];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function register_passenger(string $name, string $email, string $phone, string $password): void
{
    $existing = db_fetch_one(
        'SELECT pass_id FROM passengers WHERE email = :email',
        [':email' => $email]
    );

    if ($existing) {
        throw new RuntimeException('Email is already registered.');
    }

    db_query(
        'INSERT INTO passengers (name, email, phone, password, role)
         VALUES (:name, :email, :phone, :password, :role)',
        [
            ':name'     => $name,
            ':email'    => $email,
            ':phone'    => $phone,
            ':password' => $password,
            ':role'     => 'passenger',
        ]
    );
}

function authenticate(string $email, string $password): ?array
{
    $user = db_fetch_one(
        'SELECT pass_id, name, email, phone, password, role FROM passengers WHERE email = :email',
        [':email' => $email]
    );

    if (!$user) {
        return null;
    }

    $stored = $user['PASSWORD'] ?? $user['password'] ?? '';
    if ($stored === '') {
        return null;
    }

    if (!password_verify($password, $stored) && $password !== $stored) {
        return null;
    }

    return $user;
}
