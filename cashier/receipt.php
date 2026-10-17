<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = require_role('Admin', 'Cashier');
$id = filter_var(get('id'), FILTER_VALIDATE_INT);
if (!$id) {
    redirect($user['role'] === 'Admin' ? 'admin/orders.php' : 'cashier/orders.php');
}

$order = fetch_one(
    'SELECT o.*, c.CustomerName, c.City, c.Email,
            CONCAT(e.FirstName, " ", e.LastName) AS CashierName
     FROM Orders o
     JOIN Customers c ON c.CustomerID = o.CustomerID
     LEFT JOIN Employees e ON e.EmployeeID = o.EmployeeID
     WHERE o.OrderID = ?',
    [$id]
);
if (!$order) {
    flash_set('danger', 'Receipt not found.');
    redirect('cashier/orders.php');
}
$lines = fetch_all(
    'SELECT od.*, p.ProductName FROM OrderDetails od
     JOIN Products p ON p.ProductID = od.ProductID
     WHERE od.OrderID = ?',
    [$id]
);

render_header('Receipt #' . $id, $user);
?>
<div class="no-print mb-3">
    <button class="btn btn-pine" onclick="printPage()">Print receipt</button>
</div>
<div class="receipt">
    <div class="text-center mb-3">
        <div>🐾</div>
        <h2 class="h4 mb-0">PawBowl</h2>
        <small>Pet Food Ordering</small>
    </div>
    <p>
        Order #<?= (int) $order['OrderID'] ?><br>
        <?= h($order['OrderDate']) ?><br>
        Status: <?= h($order['Status']) ?><br>
        Payment: <?= h($order['PaymentMethod']) ?>
    </p>
    <p>
        Customer: <?= h($order['CustomerName']) ?><br>
        <?= h($order['City']) ?> · <?= h($order['Email']) ?><br>
        Cashier: <?= h($order['CashierName'] ?? 'Online order') ?>
    </p>
    <table class="table table-sm">
        <thead><tr><th>Item</th><th>Qty</th><th>Amount</th></tr></thead>
        <tbody>
        <?php foreach ($lines as $line): ?>
            <tr>
                <td><?= h($line['ProductName']) ?><br><small><?= money_php((float) $line['UnitPrice']) ?></small></td>
                <td><?= (int) $line['Quantity'] ?></td>
                <td><?= money_php((float) $line['UnitPrice'] * (int) $line['Quantity']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p class="fs-5 text-end"><strong>Total <?= money_php((float) $order['TotalAmount']) ?></strong></p>
    <p class="text-center small text-muted mb-0">Thank you for feeding them well.</p>
</div>
<?php render_footer();
