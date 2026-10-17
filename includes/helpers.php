<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money_php(float|int|string $amount): string
{
    return '₱' . number_format((float) $amount, 2);
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(400);
        exit('Invalid request token. Please go back and try again.');
    }
}

function post(string $key, $default = ''): mixed
{
    return $_POST[$key] ?? $default;
}

function get(string $key, $default = ''): mixed
{
    return $_GET[$key] ?? $default;
}

function require_post_int(string $key, int $min = 1): int
{
    $value = filter_var($_POST[$key] ?? null, FILTER_VALIDATE_INT);
    if ($value === false || $value < $min) {
        throw new InvalidArgumentException('Invalid ' . $key);
    }
    return $value;
}

function validate_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function fetch_all(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetch_one(string $sql, array $params = []): ?array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function exec_sql(string $sql, array $params = []): int
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

function last_id(): string
{
    return db()->lastInsertId();
}

function role_from_title(string $title): string
{
    return str_contains(strtolower($title), 'sales rep') ? 'Cashier' : 'Admin';
}

/**
 * Payment methods selectable per audience.
 * Customer (online) can also pick Cash on Delivery; staff (POS) are in-store.
 */
function payment_method_options(string $audience): array
{
    if ($audience === 'customer') {
        return ['Cash on Delivery', 'Cash', 'GCash'];
    }
    return ['Cash', 'GCash'];
}

function is_valid_payment_method(string $method): bool
{
    return in_array(
        $method,
        array_merge(payment_method_options('customer'), payment_method_options('staff')),
        true
    );
}

function restore_stock_for_order(int $orderId): void
{
    $items = fetch_all(
        'SELECT ProductID, Quantity FROM OrderDetails WHERE OrderID = ?',
        [$orderId]
    );
    foreach ($items as $item) {
        exec_sql(
            'UPDATE Products SET Stock = Stock + ? WHERE ProductID = ?',
            [(int) $item['Quantity'], (int) $item['ProductID']]
        );
    }
}

function deduct_stock_for_order(int $orderId): void
{
    $items = fetch_all(
        'SELECT od.ProductID, od.Quantity, p.ProductName, p.Stock
         FROM OrderDetails od
         JOIN Products p ON p.ProductID = od.ProductID
         WHERE od.OrderID = ?',
        [$orderId]
    );
    foreach ($items as $item) {
        if ((int) $item['Stock'] < (int) $item['Quantity']) {
            throw new RuntimeException('Not enough stock for ' . $item['ProductName']);
        }
        exec_sql(
            'UPDATE Products SET Stock = Stock - ? WHERE ProductID = ? AND Stock >= ?',
            [(int) $item['Quantity'], (int) $item['ProductID'], (int) $item['Quantity']]
        );
    }
}

/**
 * Place an order inside a transaction.
 * $lines = [ ['product_id' => int, 'qty' => int], ... ]
 */
function create_order(int $customerId, ?int $employeeId, array $lines, string $paymentMethod = 'Cash'): int
{
    if (count($lines) === 0) {
        throw new InvalidArgumentException('Cart is empty.');
    }

    if (!is_valid_payment_method($paymentMethod)) {
        $paymentMethod = 'Cash';
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $total = 0.0;
        $resolved = [];

        foreach ($lines as $line) {
            $pid = (int) $line['product_id'];
            $qty = (int) $line['qty'];
            if ($pid < 1 || $qty < 1) {
                throw new InvalidArgumentException('Invalid cart line.');
            }

            $product = fetch_one(
                'SELECT ProductID, ProductName, Price, Stock FROM Products WHERE ProductID = ? FOR UPDATE',
                [$pid]
            );
            if (!$product) {
                throw new RuntimeException('Product not found.');
            }
            if ((int) $product['Stock'] < $qty) {
                throw new RuntimeException(
                    $product['ProductName'] . ' has only ' . $product['Stock'] . ' in stock.'
                );
            }

            $unit = (float) $product['Price'];
            $total += $unit * $qty;
            $resolved[] = [
                'product_id' => $pid,
                'qty' => $qty,
                'unit' => $unit,
            ];
        }

        exec_sql(
            'INSERT INTO Orders (CustomerID, EmployeeID, OrderDate, TotalAmount, Status, PaymentMethod)
             VALUES (?, ?, NOW(), ?, ?, ?)',
            [$customerId, $employeeId, $total, 'Completed', $paymentMethod]
        );
        $orderId = (int) last_id();

        foreach ($resolved as $line) {
            exec_sql(
                'INSERT INTO OrderDetails (OrderID, ProductID, Quantity, UnitPrice)
                 VALUES (?, ?, ?, ?)',
                [$orderId, $line['product_id'], $line['qty'], $line['unit']]
            );
            $updated = exec_sql(
                'UPDATE Products SET Stock = Stock - ? WHERE ProductID = ? AND Stock >= ?',
                [$line['qty'], $line['product_id'], $line['qty']]
            );
            if ($updated !== 1) {
                throw new RuntimeException('Stock update failed.');
            }
        }

        $pdo->commit();
        return $orderId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function update_order_status(int $orderId, string $newStatus): void
{
    $allowed = ['Completed', 'Cancelled', 'Returned'];
    if (!in_array($newStatus, $allowed, true)) {
        throw new InvalidArgumentException('Invalid status.');
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $order = fetch_one('SELECT * FROM Orders WHERE OrderID = ? FOR UPDATE', [$orderId]);
        if (!$order) {
            throw new RuntimeException('Order not found.');
        }
        $old = $order['Status'];
        if ($old === $newStatus) {
            $pdo->commit();
            return;
        }

        $wasActive = $old === 'Completed';
        $willBeActive = $newStatus === 'Completed';

        if ($wasActive && !$willBeActive) {
            restore_stock_for_order($orderId);
        } elseif (!$wasActive && $willBeActive) {
            deduct_stock_for_order($orderId);
        }

        exec_sql('UPDATE Orders SET Status = ? WHERE OrderID = ?', [$newStatus, $orderId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
