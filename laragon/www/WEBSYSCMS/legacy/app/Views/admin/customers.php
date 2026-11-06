<?php $hasFilters = $filters['q'] !== '' || $filters['status'] !== ''; ?>
<div class="page-head">
    <div>
        <p class="eyebrow">People</p>
        <h1>Customers</h1>
        <p><?= number_format($total) ?> customer account<?= $total === 1 ? '' : 's' ?> match the current view.</p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-ghost" href="<?= e(url('/admin/requests')) ?>"><?= icon('clipboard') ?> Service requests</a>
    </div>
</div>

<form class="filter-bar" method="get" action="<?= e(url('/admin/customers')) ?>">
    <div class="field field-grow">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="Name, email, or phone">
    </div>
    <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">All statuses</option>
            <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
    </div>
    <button class="btn btn-primary btn-sm" type="submit"><?= icon('search') ?> Search</button>
    <?php if ($hasFilters) { ?>
        <a class="filter-reset" href="<?= e(url('/admin/customers')) ?>">Reset</a>
    <?php } ?>
</form>

<?php if ($customers) { ?>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Requests</th>
                    <th>Total spent</th>
                    <th>Last request</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer) { ?>
                    <tr>
                        <td>
                            <div class="table-user">
                                <span class="avatar avatar-sm" style="background: <?= e($customer['avatar_color'] ?: '#2563eb') ?>"><?= e(initials($customer['name'])) ?></span>
                                <span>
                                    <strong><?= e($customer['name']) ?></strong>
                                    <small><?= e($customer['email']) ?></small>
                                </span>
                            </div>
                        </td>
                        <td><?= e($customer['phone'] ?: '—') ?></td>
                        <td><?= (int) $customer['jobs'] ?></td>
                        <td class="cell-money"><?= e(money($customer['spent'])) ?></td>
                        <td class="cell-muted"><?= e($customer['last_request'] ? format_date($customer['last_request'], 'M j, Y') : 'No requests') ?></td>
                        <td><?= status_badge($customer['status']) ?></td>
                        <td class="text-right">
                            <a class="icon-btn" href="<?= e(url('/admin/customers/'.$customer['id'])) ?>" title="Open customer"><?= icon('chevron-right') ?></a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <?php require BASE_PATH.'/app/Views/partials/pagination.php'; ?>
<?php } else { ?>
    <div class="panel">
        <div class="empty-state">
            <?= icon('users') ?>
            <h3>No customers found</h3>
            <p><?= $hasFilters ? 'Try a different search or status filter.' : 'Registered customers will appear here.' ?></p>
        </div>
    </div>
<?php } ?>
