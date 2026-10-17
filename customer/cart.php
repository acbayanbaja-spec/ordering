<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = require_role('Customer');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) post('action');
    $pid = (int) post('ProductID');
    if ($action === 'update') {
        $qty = (int) post('qty');
        if ($qty < 1) {
            unset($_SESSION['cart'][$pid]);
        } else {
            $p = fetch_one('SELECT Stock, ProductName FROM Products WHERE ProductID = ?', [$pid]);
            if ($p && $qty > (int) $p['Stock']) {
                flash_set('danger', 'Only ' . $p['Stock'] . ' available for ' . $p['ProductName']);
            } else {
                $_SESSION['cart'][$pid] = $qty;
            }
        }
    } elseif ($action === 'remove') {
        unset($_SESSION['cart'][$pid]);
    } elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
    }
    redirect('customer/cart.php');
}

$rows = [];
$total = 0.0;
foreach (cart() as $pid => $qty) {
    $p = fetch_one(
        'SELECT p.*, c.CategoryName FROM Products p JOIN Categories c ON c.CategoryID = p.CategoryID WHERE p.ProductID = ?',
        [$pid]
    );
    if (!$p) {
        continue;
    }
    $line = (float) $p['Price'] * (int) $qty;
    $total += $line;
    $rows[] = ['p' => $p, 'qty' => (int) $qty, 'line' => $line];
}

render_header('Cart', $user);
?>
<div class="panel">
    <?php if (!$rows): ?>
        <p>Your cart is empty. <a href="<?= h(base_url('customer/shop.php')) ?>">Browse products</a></p>
    <?php else: ?>
        <table class="table align-middle">
            <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Line</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): $p = $row['p']; ?>
                <tr>
                    <td><?= h($p['ProductName']) ?><br><small><?= h($p['CategoryName']) ?></small></td>
                    <td><?= money_php((float) $p['Price']) ?></td>
                    <td>
                        <form method="post" class="d-flex gap-1">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="ProductID" value="<?= (int) $p['ProductID'] ?>">
                            <input class="form-control form-control-sm" style="width:80px" type="number" name="qty" min="1" max="<?= (int) $p['Stock'] ?>" value="<?= $row['qty'] ?>">
                            <button class="btn btn-sm btn-outline-secondary">Update</button>
                        </form>
                    </td>
                    <td><?= money_php($row['line']) ?></td>
                    <td>
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="ProductID" value="<?= (int) $p['ProductID'] ?>">
                            <button class="btn btn-sm btn-outline-danger">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="d-flex justify-content-between align-items-center">
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="clear">
                <button class="btn btn-link">Clear cart</button>
            </form>
            <div>
                <strong class="fs-4"><?= money_php($total) ?></strong>
                <a class="btn btn-pine ms-2" href="<?= h(base_url('customer/checkout.php')) ?>">Checkout</a>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php render_footer();
