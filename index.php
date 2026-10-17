<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    $role = current_user()['role'];
    if ($role === 'Admin') {
        redirect('admin/dashboard.php');
    }
    if ($role === 'Cashier') {
        redirect('cashier/pos.php');
    }
    redirect('customer/shop.php');
}

$error = '';
$tab = get('tab', 'staff') === 'customer' ? 'customer' : 'staff';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $tab = post('login_type') === 'customer' ? 'customer' : 'staff';
    if ($tab === 'staff') {
        $ok = login_employee(trim((string) post('employee_id')), (string) post('password'));
        if ($ok) {
            $role = current_user()['role'];
            redirect($role === 'Admin' ? 'admin/dashboard.php' : 'cashier/pos.php');
        }
        $flashFail = flash_get();
        $error = $flashFail['message'] ?? 'Invalid employee ID or password.';
    } else {
        $email = trim((string) post('email'));
        $ok = login_customer($email, (string) post('password'));
        if ($ok) {
            redirect('customer/shop.php');
        }
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login · PawBowl</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,700&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= h(base_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="auth-body">
<div class="auth-wrap">
    <section class="auth-hero">
        <div>
            <div class="paw">🐾</div>
            <h1>PawBowl<br>Pet Food Ordering</h1>
            <p>Role-based dashboards for Admin, Cashier, and Customer. Track stock, ring up sales, and reorder kibble without the chaos.</p>
        </div>
        <div class="small text-muted">
            Staff log in with your Employee ID. Customers: <a href="<?= h(base_url('register.php')) ?>">register here</a>.<br>
            Demo — Admin employee <strong>1</strong> / <code>Admin@123</code> ·
            Cashier employee <strong>2</strong> / <code>Cashier@123</code>
        </div>
    </section>
    <div class="auth-card">
        <ul class="nav nav-pills mb-3">
            <li class="nav-item"><a class="nav-link <?= $tab === 'staff' ? 'active' : '' ?>" href="?tab=staff">Staff</a></li>
            <li class="nav-item"><a class="nav-link <?= $tab === 'customer' ? 'active' : '' ?>" href="?tab=customer">Customer</a></li>
        </ul>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>
        <?php $flash = flash_get(); if ($flash): ?>
            <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
        <?php endif; ?>

        <?php if ($tab === 'staff'): ?>
            <form method="post" novalidate class="needs-validation">
                <?= csrf_field() ?>
                <input type="hidden" name="login_type" value="staff">
                <div class="mb-3">
                    <label class="form-label">Employee ID</label>
                    <input class="form-control" name="employee_id" required pattern="[0-9]+" value="<?= h((string) post('employee_id')) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input class="form-control" type="password" name="password" required minlength="6">
                </div>
                <button class="btn btn-pine w-100" type="submit">Sign in</button>
            </form>
        <?php else: ?>
            <form method="post" novalidate class="needs-validation">
                <?= csrf_field() ?>
                <input type="hidden" name="login_type" value="customer">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input class="form-control" type="email" name="email" required value="<?= h((string) post('email')) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input class="form-control" type="password" name="password" required minlength="6">
                </div>
                <button class="btn btn-clay w-100" type="submit">Sign in</button>
            </form>
            <p class="mt-3 mb-0">New here? <a href="<?= h(base_url('register.php')) ?>">Create a customer account</a></p>
        <?php endif; ?>
    </div>
</div>
<script src="<?= h(base_url('assets/js/app.js')) ?>"></script>
</body>
</html>
