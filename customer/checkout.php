<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = require_role('Customer');

$lines = [];
$total = 0.0;
foreach (cart() as $pid => $qty) {
    $p = fetch_one('SELECT * FROM Products WHERE ProductID = ?', [$pid]);
    if (!$p) {
        continue;
    }
    $line = (float) $p['Price'] * (int) $qty;
    $total += $line;
    $lines[] = ['p' => $p, 'qty' => (int) $qty, 'line' => $line];
}

if (!$lines) {
    flash_set('warning', 'Your cart is empty.');
    redirect('customer/cart.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    try {
        $orderLines = [];
        foreach (cart() as $pid => $qty) {
            $orderLines[] = ['product_id' => (int) $pid, 'qty' => (int) $qty];
        }
        $paymentMethod = (string) post('payment_method');
        $orderId = create_order((int) $user['id'], null, $orderLines, $paymentMethod);
        $_SESSION['cart'] = [];
        flash_set('success', 'Order #' . $orderId . ' placed. Payment: ' . $paymentMethod . '.');
        redirect('customer/orders.php');
    } catch (Throwable $e) {
        flash_set('danger', $e->getMessage());
        redirect('customer/checkout.php');
    }
}

$profile = fetch_one('SELECT * FROM Customers WHERE CustomerID = ?', [$user['id']]);
render_header('Checkout', $user);
?>
<div class="row g-3">
    <div class="col-lg-7 panel">
        <h2 class="h5">Order summary</h2>
        <ul class="list-group list-group-flush">
            <?php foreach ($lines as $row): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span><?= h($row['p']['ProductName']) ?> × <?= $row['qty'] ?></span>
                    <span><?= money_php($row['line']) ?></span>
                </li>
            <?php endforeach; ?>
            <li class="list-group-item d-flex justify-content-between"><strong>Total</strong><strong><?= money_php($total) ?></strong></li>
        </ul>
    </div>
    <div class="col-lg-5 panel">
        <h2 class="h5">Ship to</h2>
        <p><?= h($profile['CustomerName']) ?><br><?= h($profile['City']) ?>, <?= h($profile['Country']) ?><br><?= h($profile['Email']) ?></p>
        <form method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Payment method</label>
                <select class="form-select" name="payment_method" required>
                    <?php foreach (payment_method_options('customer') as $method): ?>
                        <option value="<?= h($method) ?>"><?= h($method) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-clay w-100">Place order</button>
        </form>
        <p class="small text-muted mt-2 mb-0">Stock is reserved when the order is placed. EmployeeID is left empty for online orders.</p>
    </div>
</div>
<?php render_footer();
