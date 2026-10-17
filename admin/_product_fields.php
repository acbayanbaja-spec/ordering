<?php
$e = $edit ?? [];
?>
<div class="mb-3">
    <label class="form-label">Product name</label>
    <input class="form-control" name="ProductName" required value="<?= h($e['ProductName'] ?? '') ?>">
</div>
<div class="mb-3">
    <label class="form-label">Category</label>
    <select class="form-select" name="CategoryID" required>
        <option value="">Select</option>
        <?php foreach ($categories as $c): ?>
            <option value="<?= (int) $c['CategoryID'] ?>" <?= ((int) ($e['CategoryID'] ?? 0) === (int) $c['CategoryID']) ? 'selected' : '' ?>>
                <?= h($c['CategoryName']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<div class="mb-3">
    <label class="form-label">Price</label>
    <input class="form-control" type="number" step="0.01" min="0" name="Price" required value="<?= h((string) ($e['Price'] ?? '')) ?>">
</div>
<div class="mb-3">
    <label class="form-label">Stock</label>
    <input class="form-control" type="number" min="0" name="Stock" required value="<?= h((string) ($e['Stock'] ?? '0')) ?>">
</div>
