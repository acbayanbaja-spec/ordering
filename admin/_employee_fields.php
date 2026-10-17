<?php
$e = $edit ?? [];
$isEdit = isset($edit);
?>
<div class="row">
    <div class="col-md-6 mb-2"><label class="form-label">First name</label><input class="form-control" name="FirstName" required value="<?= h($e['FirstName'] ?? '') ?>"></div>
    <div class="col-md-6 mb-2"><label class="form-label">Last name</label><input class="form-control" name="LastName" required value="<?= h($e['LastName'] ?? '') ?>"></div>
</div>
<div class="mb-2">
    <label class="form-label">Department</label>
    <select class="form-select" name="DepartmentID" required>
        <?php foreach ($departments as $d): ?>
            <option value="<?= (int) $d['DepartmentID'] ?>" <?= ((int) ($e['DepartmentID'] ?? 0) === (int) $d['DepartmentID']) ? 'selected' : '' ?>><?= h($d['DepartmentName']) ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="row">
    <div class="col-md-6 mb-2"><label class="form-label">Salary</label><input class="form-control" type="number" step="0.01" min="0" name="Salary" required value="<?= h((string) ($e['Salary'] ?? '')) ?>"></div>
    <div class="col-md-6 mb-2"><label class="form-label">Hire date</label><input class="form-control" type="date" name="HireDate" required value="<?= h((string) ($e['HireDate'] ?? date('Y-m-d'))) ?>"></div>
</div>
<div class="mb-2">
    <label class="form-label">Job title</label>
    <select class="form-select" name="JobTitle" required>
        <?php foreach (['Manager', 'IT', 'Sales Rep'] as $t): ?>
            <option <?= (($e['JobTitle'] ?? '') === $t) ? 'selected' : '' ?>><?= $t ?></option>
        <?php endforeach; ?>
    </select>
    <div class="form-text">Manager/IT → Admin login. Sales Rep → Cashier login.</div>
</div>
<div class="mb-2">
    <label class="form-label">Manager</label>
    <select class="form-select" name="ManagerID">
        <option value="">None</option>
        <?php foreach ($managers as $m): ?>
            <option value="<?= (int) $m['EmployeeID'] ?>" <?= ((int) ($e['ManagerID'] ?? 0) === (int) $m['EmployeeID']) ? 'selected' : '' ?>>
                <?= h($m['FirstName'] . ' ' . $m['LastName']) ?> (#<?= (int) $m['EmployeeID'] ?>)
            </option>
        <?php endforeach; ?>
    </select>
</div>
<div class="mb-2">
    <label class="form-label">Status</label>
    <select class="form-select" name="Status">
        <option <?= (($e['Status'] ?? 'Active') === 'Active') ? 'selected' : '' ?>>Active</option>
        <option <?= (($e['Status'] ?? '') === 'Inactive') ? 'selected' : '' ?>>Inactive</option>
    </select>
</div>
<div class="mb-2">
    <label class="form-label">Password <?= $isEdit ? '(leave blank to keep)' : '' ?></label>
    <input class="form-control" type="password" name="password" <?= $isEdit ? '' : 'required minlength="8"' ?> minlength="8">
</div>
