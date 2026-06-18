<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict'
]);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

initializeStorage();

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireLogin(): void
{
    if (!currentUser()) {
        redirect('login.php');
    }
}

function requireRole(array $roles): void
{
    requireLogin();
    if (!in_array(currentUser()['role'], $roles, true)) {
        setError('You do not have permission to access that page.');
        redirect('dashboard.php');
    }
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): void
{
    if (!isset($_POST['csrf'], $_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        setError('Security check failed. Please try again.');
        redirect('login.php');
    }
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function setMessage(string $message): void
{
    $_SESSION['message'] = $message;
}

function setError(string $message): void
{
    $_SESSION['error'] = $message;
}

function flashMessages(): void
{
    $message = $_SESSION['message'] ?? '';
    $error = $_SESSION['error'] ?? '';
    unset($_SESSION['message'], $_SESSION['error']);

    if ($message) {
        echo '<div class="alert success">' . e($message) . '</div>';
    }
    if ($error) {
        echo '<div class="alert error">' . e($error) . '</div>';
    }
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function strongPassword(string $password): bool
{
    return strlen($password) >= 8
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/[0-9]/', $password)
        && preg_match('/[^A-Za-z0-9]/', $password);
}

function validDate(string $date): bool
{
    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    return $parsed && $parsed->format('Y-m-d') === $date;
}

function studentRegistrationDate(array $student): string
{
    if (!empty($student['registrationDate'])) {
        return $student['registrationDate'];
    }

    return substr($student['registeredAt'] ?? date('Y-m-d'), 0, 10);
}

function loggedInRedirectAwayFromAuthPages(): void
{
    if (currentUser()) {
        redirect('dashboard.php');
    }
}
