<?php

declare(strict_types=1);

/**
 * Get shared PDO MySQL connection.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = require __DIR__ . '/../config/database.php';
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $cfg['host'],
        $cfg['port'],
        $cfg['database'],
        $cfg['charset']
    );

    try {
        $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
    }

    return $pdo;
}

/**
 * Execute a prepared statement and return the statement.
 *
 * @param array<int|string, mixed> $params
 */
function db_query(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Fetch all rows from a prepared query.
 *
 * @param array<int|string, mixed> $params
 * @return array<int, array<string, mixed>>
 */
function db_fetch_all(string $sql, array $params = []): array
{
    return db_query($sql, $params)->fetchAll();
}

/**
 * Fetch a single row.
 *
 * @param array<int|string, mixed> $params
 * @return array<string, mixed>|false
 */
function db_fetch_one(string $sql, array $params = [])
{
    return db_query($sql, $params)->fetch();
}

/**
 * Call MySQL stored procedure Process_Booking.
 */
function process_booking(int $passId, int $flightId, string $seatNo, float $fare): int
{
    $stmt = db()->prepare(
        'CALL Process_Booking(:pass_id, :flight_id, :seat_no, :fare, @book_id)'
    );
    $stmt->execute([
        ':pass_id'   => $passId,
        ':flight_id' => $flightId,
        ':seat_no'   => $seatNo,
        ':fare'      => $fare,
    ]);

    while ($stmt->nextRowset()) {
        continue;
    }

    $row = db_fetch_one('SELECT @book_id AS book_id');

    return (int) ($row['book_id'] ?? 0);
}

/**
 * Call MySQL stored procedure Cancel_Booking.
 */
function cancel_booking(int $bookId, int $passId): void
{
    $stmt = db()->prepare('CALL Cancel_Booking(:book_id, :pass_id)');
    $stmt->execute([
        ':book_id' => $bookId,
        ':pass_id' => $passId,
    ]);

    while ($stmt->nextRowset()) {
        continue;
    }
}

/**
 * Call MySQL function Calculate_Fare.
 */
function calculate_fare(int $routeId): float
{
    $row = db_fetch_one(
        'SELECT Calculate_Fare(:route_id, NULL) AS fare',
        [':route_id' => $routeId]
    );

    return (float) ($row['fare'] ?? 0);
}

/**
 * Normalize datetime-local input for MySQL DATETIME.
 */
function normalize_datetime(string $value): string
{
    $value = str_replace('T', ' ', trim($value));

    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
        return $value . ':00';
    }

    return $value;
}

/**
 * Extract a readable database error message.
 */
function db_error_message(Throwable $e): string
{
    $message = $e->getMessage();

    if (preg_match('/SQLSTATE\[45000\]: (.+?): (.+)/', $message, $matches)) {
        return $matches[2];
    }

    if (preg_match('/1451|1452|1062/', $message)) {
        return 'This action conflicts with existing records.';
    }

    return 'Operation failed. Please try again.';
}

/**
 * Escape HTML output.
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect helper (uses url() when bootstrap is loaded).
 */
function redirect(string $path): never
{
    if (!str_starts_with($path, 'http') && function_exists('url')) {
        $path = url($path);
    }
    header('Location: ' . $path);
    exit;
}

/**
 * Flash message helpers.
 */
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Validate email format.
 */
function is_valid_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone (basic).
 */
function is_valid_phone(string $phone): bool
{
    return (bool) preg_match('/^[0-9+\-\s()]{7,20}$/', $phone);
}
