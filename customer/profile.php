<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = require_role('Customer');
$profile = fetch_one('SELECT * FROM Customers WHERE CustomerID = ?', [$user['id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) post('action');
    try {
        if ($action === 'profile') {
            $name = trim((string) post('CustomerName'));
            $city = trim((string) post('City'));
            $country = trim((string) post('Country'));
            $email = trim((string) post('Email'));
            if ($name === '' || $city === '' || $country === '' || !validate_email($email)) {
                throw new InvalidArgumentException('All profile fields are required.');
            }
            $dup = fetch_one(
                'SELECT CustomerID FROM Customers WHERE Email = ? AND CustomerID <> ?',
                [$email, $user['id']]
            );
            if ($dup) {
                throw new RuntimeException('That email is already in use.');
            }
            exec_sql(
                'UPDATE Customers SET CustomerName=?, City=?, Country=?, Email=? WHERE CustomerID=?',
                [$name, $city, $country, $email, $user['id']]
            );
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
            flash_set('success', 'Profile updated.');
        } elseif ($action === 'password') {
            $current = (string) post('current');
            $new = (string) post('new');
            $confirm = (string) post('confirm');
            if (!password_verify($current, $profile['Password'])) {
                throw new RuntimeException('Current password is incorrect.');
            }
            if (strlen($new) < 8 || $new !== $confirm) {
                throw new InvalidArgumentException('New password must be 8+ characters and match the confirmation.');
            }
            exec_sql(
                'UPDATE Customers SET Password = ? WHERE CustomerID = ?',
                [password_hash($new, PASSWORD_DEFAULT), $user['id']]
            );
            flash_set('success', 'Password changed.');
        }
    } catch (Throwable $e) {
        flash_set('danger', $e->getMessage());
    }
    redirect('customer/profile.php');
}

$profile = fetch_one('SELECT * FROM Customers WHERE CustomerID = ?', [$user['id']]);
render_header('Profile', $user);
?>
<div class="row g-3">
    <div class="col-lg-6">
        <form class="panel" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="profile">
            <h2 class="h5">Account details</h2>
            <div class="mb-2"><label class="form-label">Name</label><input class="form-control" name="CustomerName" required value="<?= h($profile['CustomerName']) ?>"></div>
            <div class="mb-2"><label class="form-label">City</label><input class="form-control" name="City" required value="<?= h($profile['City']) ?>"></div>
            <div class="mb-2"><label class="form-label">Country</label><input class="form-control" name="Country" required value="<?= h($profile['Country']) ?>"></div>
            <div class="mb-3"><label class="form-label">Email</label><input class="form-control" type="email" name="Email" required value="<?= h($profile['Email']) ?>"></div>
            <button class="btn btn-pine">Save profile</button>
        </form>
    </div>
    <div class="col-lg-6">
        <form class="panel" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="password">
            <h2 class="h5">Change password</h2>
            <div class="mb-2"><label class="form-label">Current</label><input class="form-control" type="password" name="current" required></div>
            <div class="mb-2"><label class="form-label">New</label><input class="form-control" type="password" name="new" required minlength="8"></div>
            <div class="mb-3"><label class="form-label">Confirm</label><input class="form-control" type="password" name="confirm" required minlength="8"></div>
            <button class="btn btn-clay">Update password</button>
        </form>
    </div>
</div>
<?php render_footer();
