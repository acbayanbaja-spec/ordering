<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = require_role('Cashier');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    try {
        $id = require_post_int('OrderID');
        $order = fetch_one('SELECT * FROM Orders WHERE OrderID = ? AND EmployeeID = ?', [$id, $user['id']]);
        if (!$order) {
            throw new RuntimeException('You can only change orders you placed.');
        }
        $status = (string) post('Status');
        if (!in_array($status, ['Cancelled', 'Returned'], true)) {
            throw new InvalidArgumentException('Cashiers can cancel or return orders only.');
        }
        update_order_status($id, $status);
        flash_set('success', 'Order #' . $id . ' marked ' . $status . '. Stock restored if it was completed.');
    } catch (Throwable $e) {
        flash_set('danger', $e->getMessage());
    }
    redirect('cashier/orders.php');
}

$view = (string) get('view', 'today');
if ($view === 'today') {
    $orders = fetch_all(
        'SELECT o.*, c.CustomerName
         FROM Orders o JOIN Customers c ON c.CustomerID = o.CustomerID
         WHERE DATE(o.OrderDate) = CURDATE()
         ORDER BY o.OrderDate DESC'
    );
    $heading = "Today's orders";
} else {
    $orders = fetch_all(
        'SELECT o.*, c.CustomerName
         FROM Orders o JOIN Customers c ON c.CustomerID = o.CustomerID
         WHERE o.EmployeeID = ?
         ORDER BY o.OrderDate DESC',
        [$user['id']]
    );
    $heading = 'My order history';
}

render_header('Orders', $user);
?>
<div class="mb-3">
    <a class="btn btn-sm <?= $view === 'today' ? 'btn-pine' : 'btn-outline-secondary' ?>" href="?view=today">Today</a>
    <a class="btn btn-sm <?= $view === 'mine' ? 'btn-pine' : 'btn-outline-secondary' ?>" href="?view=mine">My history</a>
</div>
<h2 class="h5"><?= h($heading) ?></h2>
<div class="panel table-responsive">
    <table class="table align-middle">
        <thead><tr><th>ID</th><th>When</th><th>Customer</th><th>Total</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td>#<?= (int) $o['OrderID'] ?></td>
                <td><?= h($o['OrderDate']) ?></td>
                <td><?= h($o['CustomerName']) ?></td>
                <td><?= money_php((float) $o['TotalAmount']) ?></td>
                <td><?= h($o['Status']) ?></td>
                <td class="text-end">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= h(base_url('cashier/receipt.php?id=' . $o['OrderID'])) ?>">Receipt</a>
                    <?php if ($o['Status'] === 'Completed' && (int) $o['EmployeeID'] === (int) $user['id']): ?>
                        <form class="d-inline" method="post" onsubmit="return confirm('Cancel this order and restore stock?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="OrderID" value="<?= (int) $o['OrderID'] ?>">
                            <input type="hidden" name="Status" value="Cancelled">
                            <button class="btn btn-sm btn-outline-danger">Cancel</button>
                        </form>
                        <form class="d-inline" method="post" onsubmit="return confirm('Process a return and restore stock?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="OrderID" value="<?= (int) $o['OrderID'] ?>">
                            <input type="hidden" name="Status" value="Returned">
                            <button class="btn btn-sm btn-outline-warning">Return</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php render_footer();
