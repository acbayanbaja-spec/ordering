<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = require_role('Cashier');

if (!isset($_SESSION['pos_cart'])) {
    $_SESSION['pos_cart'] = [];
}
if (!isset($_SESSION['pos_customer_id'])) {
    $_SESSION['pos_customer_id'] = null;
}

$q = trim((string) get('q'));
$cat = (int) get('cat');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) post('action');
    try {
        if ($action === 'add') {
            $pid = require_post_int('ProductID');
            $qty = max(1, (int) post('qty', 1));
            $p = fetch_one('SELECT ProductID, ProductName, Stock FROM Products WHERE ProductID = ?', [$pid]);
            if (!$p) {
                throw new RuntimeException('Product not found.');
            }
            $newQty = (int) ($_SESSION['pos_cart'][$pid] ?? 0) + $qty;
            if ($newQty > (int) $p['Stock']) {
                throw new RuntimeException($p['ProductName'] . ' only has ' . $p['Stock'] . ' in stock.');
            }
            $_SESSION['pos_cart'][$pid] = $newQty;
        } elseif ($action === 'update_qty') {
            $pid = require_post_int('ProductID');
            $qty = (int) post('qty');
            if ($qty < 1) {
                unset($_SESSION['pos_cart'][$pid]);
            } else {
                $p = fetch_one('SELECT Stock, ProductName FROM Products WHERE ProductID = ?', [$pid]);
                if ($qty > (int) $p['Stock']) {
                    throw new RuntimeException('Only ' . $p['Stock'] . ' available for ' . $p['ProductName']);
                }
                $_SESSION['pos_cart'][$pid] = $qty;
            }
        } elseif ($action === 'remove') {
            unset($_SESSION['pos_cart'][require_post_int('ProductID')]);
        } elseif ($action === 'clear') {
            $_SESSION['pos_cart'] = [];
        } elseif ($action === 'select_customer') {
            $_SESSION['pos_customer_id'] = require_post_int('CustomerID');
        } elseif ($action === 'add_customer') {
            $name = trim((string) post('CustomerName'));
            $city = trim((string) post('City'));
            $country = trim((string) post('Country')) ?: 'Philippines';
            $email = trim((string) post('Email'));
            if ($name === '' || $city === '' || !validate_email($email)) {
                throw new InvalidArgumentException('Customer name, city, and a valid email are required.');
            }
            $tempPass = bin2hex(random_bytes(4)) . 'Aa1!';
            exec_sql(
                'INSERT INTO Customers (CustomerName, City, Country, Email, Password) VALUES (?, ?, ?, ?, ?)',
                [$name, $city, $country, $email, password_hash($tempPass, PASSWORD_DEFAULT)]
            );
            $_SESSION['pos_customer_id'] = (int) last_id();
            flash_set('success', 'Customer created. Temporary password: ' . $tempPass);
        } elseif ($action === 'place') {
            if (!$_SESSION['pos_customer_id']) {
                throw new RuntimeException('Select or add a customer first.');
            }
            $lines = [];
            foreach ($_SESSION['pos_cart'] as $pid => $qty) {
                $lines[] = ['product_id' => (int) $pid, 'qty' => (int) $qty];
            }
            $paymentMethod = (string) post('payment_method');
            if (!is_valid_payment_method($paymentMethod)) {
                throw new InvalidArgumentException('Please choose a valid payment method.');
            }
            $orderId = create_order((int) $_SESSION['pos_customer_id'], (int) $user['id'], $lines, $paymentMethod);
            $_SESSION['pos_cart'] = [];
            flash_set('success', 'Order #' . $orderId . ' placed. Payment: ' . $paymentMethod . '.');
            redirect('cashier/receipt.php?id=' . $orderId);
        }
    } catch (Throwable $e) {
        flash_set('danger', $e->getMessage());
    }
    redirect('cashier/pos.php');
}

$catSql = 'SELECT p.*, c.CategoryName FROM Products p JOIN Categories c ON c.CategoryID = p.CategoryID WHERE 1=1';
$params = [];
if ($q !== '') {
    $catSql .= ' AND p.ProductName LIKE ?';
    $params[] = '%' . $q . '%';
}
if ($cat > 0) {
    $catSql .= ' AND p.CategoryID = ?';
    $params[] = $cat;
}
$catSql .= ' ORDER BY p.ProductName';
$products = fetch_all($catSql, $params);
$categories = fetch_all('SELECT * FROM Categories ORDER BY CategoryName');
$customers = fetch_all('SELECT CustomerID, CustomerName, Email FROM Customers ORDER BY CustomerName');

$cartRows = [];
$total = 0.0;
foreach ($_SESSION['pos_cart'] as $pid => $qty) {
    $p = fetch_one('SELECT * FROM Products WHERE ProductID = ?', [$pid]);
    if (!$p) {
        continue;
    }
    $line = (float) $p['Price'] * (int) $qty;
    $total += $line;
    $cartRows[] = ['p' => $p, 'qty' => (int) $qty, 'line' => $line];
}

$selectedCustomer = $_SESSION['pos_customer_id']
    ? fetch_one('SELECT * FROM Customers WHERE CustomerID = ?', [$_SESSION['pos_customer_id']])
    : null;

render_header('Point of sale', $user);
?>
<div class="pos-layout">
    <div>
        <form class="panel mb-3 d-flex gap-2" method="get">
            <input class="form-control" name="q" placeholder="Search products" value="<?= h($q) ?>">
            <select class="form-select" name="cat" style="max-width: 200px;">
                <option value="0">All categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int) $c['CategoryID'] ?>" <?= $cat === (int) $c['CategoryID'] ? 'selected' : '' ?>><?= h($c['CategoryName']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-pine">Search</button>
        </form>
        <div class="product-grid">
            <?php foreach ($products as $p): ?>
                <div class="product-card">
                    <small class="text-muted"><?= h($p['CategoryName']) ?></small>
                    <h3><?= h($p['ProductName']) ?></h3>
                    <div class="price"><?= money_php((float) $p['Price']) ?></div>
                    <div class="<?= (int) $p['Stock'] === 0 ? 'stock-out' : ((int) $p['Stock'] <= LOW_STOCK_THRESHOLD ? 'stock-low' : '') ?>">
                        Stock: <?= (int) $p['Stock'] ?>
                    </div>
                    <form method="post" class="d-flex gap-2 mt-auto">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="ProductID" value="<?= (int) $p['ProductID'] ?>">
                        <input class="form-control form-control-sm" type="number" name="qty" value="1" min="1" max="<?= (int) $p['Stock'] ?>" <?= (int) $p['Stock'] === 0 ? 'disabled' : '' ?>>
                        <button class="btn btn-sm btn-clay" <?= (int) $p['Stock'] === 0 ? 'disabled' : '' ?>>
                            <?= (int) $p['Stock'] === 0 ? 'Out of stock' : 'Add' ?>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="cart-box panel">
        <h2 class="h5">Cart</h2>
        <?php if ($selectedCustomer): ?>
            <p>Customer: <strong><?= h($selectedCustomer['CustomerName']) ?></strong></p>
        <?php else: ?>
            <p class="text-danger">No customer selected.</p>
        <?php endif; ?>
        <form method="post" class="mb-2">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="select_customer">
            <div class="input-group">
                <select class="form-select" name="CustomerID" required>
                    <option value="">Choose customer</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= (int) $c['CustomerID'] ?>" <?= ((int) ($_SESSION['pos_customer_id'] ?? 0) === (int) $c['CustomerID']) ? 'selected' : '' ?>>
                            <?= h($c['CustomerName']) ?> (<?= h($c['Email']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-outline-secondary">Use</button>
            </div>
        </form>
        <button class="btn btn-sm btn-outline-secondary mb-3" data-bs-toggle="modal" data-bs-target="#newCust">Add customer</button>

        <?php foreach ($cartRows as $row): $p = $row['p']; ?>
            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                <div>
                    <strong><?= h($p['ProductName']) ?></strong><br>
                    <small><?= money_php((float) $p['Price']) ?></small>
                </div>
                <form method="post" class="d-flex gap-1">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_qty">
                    <input type="hidden" name="ProductID" value="<?= (int) $p['ProductID'] ?>">
                    <input class="form-control form-control-sm" style="width:70px" type="number" name="qty" min="0" max="<?= (int) $p['Stock'] ?>" value="<?= $row['qty'] ?>">
                    <button class="btn btn-sm btn-outline-secondary">Set</button>
                </form>
            </div>
        <?php endforeach; ?>
        <p class="mt-3 fs-5">Total: <strong><?= money_php($total) ?></strong></p>
        <form method="post" class="d-grid gap-2">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="place">
            <div>
                <label class="form-label">Payment method</label>
                <select class="form-select" name="payment_method" required>
                    <?php foreach (payment_method_options('staff') as $method): ?>
                        <option value="<?= h($method) ?>"><?= h($method) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-pine" <?= ($total <= 0 || !$selectedCustomer) ? 'disabled' : '' ?>>Place order</button>
        </form>
        <form method="post" class="mt-2">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="clear">
            <button class="btn btn-link">Clear cart</button>
        </form>
    </div>
</div>

<div class="modal fade" id="newCust">
    <div class="modal-dialog">
        <form class="modal-content" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_customer">
            <div class="modal-header"><h5 class="modal-title">Walk-in customer</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label">Name</label><input class="form-control" name="CustomerName" required></div>
                <div class="mb-2"><label class="form-label">City</label><input class="form-control" name="City" required></div>
                <div class="mb-2"><label class="form-label">Country</label><input class="form-control" name="Country" value="Philippines"></div>
                <div class="mb-2"><label class="form-label">Email</label><input class="form-control" type="email" name="Email" required></div>
            </div>
            <div class="modal-footer"><button class="btn btn-pine">Save and select</button></div>
        </form>
    </div>
</div>
<?php render_footer();
