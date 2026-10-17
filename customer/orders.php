<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = require_role('Customer');
$orders = fetch_all(
    'SELECT * FROM Orders WHERE CustomerID = ? ORDER BY OrderDate DESC',
    [$user['id']]
);

render_header('My orders', $user);
?>
<?php foreach ($orders as $o): ?>
    <?php
    $lines = fetch_all(
        'SELECT od.*, p.ProductName FROM OrderDetails od
         JOIN Products p ON p.ProductID = od.ProductID
         WHERE od.OrderID = ?',
        [$o['OrderID']]
    );
    ?>
    <div class="panel mb-3">
        <div class="d-flex justify-content-between">
            <strong>Order #<?= (int) $o['OrderID'] ?></strong>
            <span><?= h($o['OrderDate']) ?> · <?= h($o['Status']) ?> · <?= h($o['PaymentMethod']) ?> · <?= money_php((float) $o['TotalAmount']) ?></span>
        </div>
        <table class="table table-sm mt-2 mb-0">
            <thead><tr><th>Product</th><th>Qty</th><th>Unit</th><th>Line</th></tr></thead>
            <tbody>
            <?php foreach ($lines as $line): ?>
                <tr>
                    <td><?= h($line['ProductName']) ?></td>
                    <td><?= (int) $line['Quantity'] ?></td>
                    <td><?= money_php((float) $line['UnitPrice']) ?></td>
                    <td><?= money_php((float) $line['UnitPrice'] * (int) $line['Quantity']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>
<?php if (!$orders): ?>
    <div class="panel">You have not placed any orders yet.</div>
<?php endif; ?>
<?php render_footer();
