<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $sessionDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pawbowl_sessions';
    if (!is_dir($sessionDir)) {
        @mkdir($sessionDir, 0777, true);
    }
    if (is_dir($sessionDir) && is_writable($sessionDir)) {
        session_save_path($sessionDir);
    }
    session_start();
}

require_once __DIR__ . '/helpers.php';

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash_set('warning', 'Please log in to continue.');
        redirect('index.php');
    }
    return $user;
}

function require_role(string ...$roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        $home = match ($user['role']) {
            'Admin' => 'admin/dashboard.php',
            'Cashier' => 'cashier/pos.php',
            default => 'customer/shop.php',
        };
        flash_set('danger', 'You do not have access to that page.');
        redirect($home);
    }
    return $user;
}

function login_employee(string $employeeId, string $password): bool
{
    $id = filter_var($employeeId, FILTER_VALIDATE_INT);
    if ($id === false) {
        return false;
    }

    $emp = fetch_one(
        'SELECT EmployeeID, FirstName, LastName, Role, Status, Password, JobTitle
         FROM Employees WHERE EmployeeID = ?',
        [$id]
    );
    if (!$emp || !password_verify($password, $emp['Password'])) {
        return false;
    }
    if ($emp['Status'] !== 'Active') {
        flash_set('danger', 'This employee account is inactive.');
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $emp['EmployeeID'],
        'name' => $emp['FirstName'] . ' ' . $emp['LastName'],
        'role' => $emp['Role'],
        'type' => 'employee',
        'job' => $emp['JobTitle'],
    ];
    return true;
}

function login_customer(string $email, string $password): bool
{
    $cust = fetch_one(
        'SELECT CustomerID, CustomerName, Email, Password FROM Customers WHERE Email = ?',
        [$email]
    );
    if (!$cust || !password_verify($password, $cust['Password'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $cust['CustomerID'],
        'name' => $cust['CustomerName'],
        'email' => $cust['Email'],
        'role' => 'Customer',
        'type' => 'customer',
    ];
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return true;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function cart(): array
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}
