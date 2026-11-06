<?php
$statusLabels = ['pending' => 'Pending', 'assigned' => 'Assigned', 'accepted' => 'Accepted', 'in_progress' => 'In progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
?>
<div class="page-head">
    <div>
        <p class="eyebrow">Service history</p>
        <h1>My requests</h1>
        <p><?= number_format($total) ?> request<?= $total === 1 ? '' : 's' ?> match your filters.</p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-primary" href="<?= e(url('/customer/requests/new')) ?>"><?= icon('plus') ?> New request</a>
    </div>
</div>

<div class="chips">
    <a class="chip <?= $status === '' ? 'active' : '' ?>" href="<?= e(query_url(['status' => null, 'page' => null])) ?>">
        All <span class="chip-count"><?= array_sum($counts) ?></span>
    </a>
    <?php foreach ($counts as $key => $count) { ?>
        <a class="chip <?= $status === $key ? 'active' : '' ?>" href="<?= e(query_url(['status' => $key, 'page' => null])) ?>">
            <?= e($statusLabels[$key] ?? ucfirst($key)) ?> <span class="chip-count"><?= $count ?></span>
        </a>
    <?php } ?>
</div>

<form class="filter-bar" method="get" action="<?= e(url('/customer/requests')) ?>">
    <?php if ($status !== '') { ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php } ?>
    <div class="field field-grow">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Ticket number, title, or service">
    </div>
    <button class="btn btn-primary btn-sm" type="submit"><?= icon('search') ?> Search</button>
    <?php if ($search !== '' || $status !== '') { ?>
        <a class="filter-reset" href="<?= e(url('/customer/requests')) ?>">Reset</a>
    <?php } ?>
</form>

<?php if ($requests) { ?>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Service</th>
                    <th>Technician</th>
                    <th>Preferred</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request) { ?>
                    <tr>
                        <td>
                            <a class="table-ticket" href="<?= e(url('/customer/requests/'.$request['id'])) ?>"><?= e($request['ticket_no']) ?></a>
                            <div class="cell-muted"><?= e(format_date($request['created_at'], 'M j, Y')) ?></div>
                        </td>
                        <td>
                            <div class="table-main">
                                <strong><?= e($request['title']) ?></strong>
                                <small><?= e($request['category_name']) ?> · <?= e(ucfirst($request['service_type'])) ?></small>
                            </div>
                        </td>
                        <td><?= e($request['technician_name'] ?: 'Not yet assigned') ?></td>
                        <td class="cell-muted"><?= e($request['preferred_date'] ? format_date($request['preferred_date'], 'M j, Y') : 'Flexible') ?></td>
                        <td><?= status_badge($request['status']) ?></td>
                        <td class="text-right">
                            <a class="icon-btn" href="<?= e(url('/customer/requests/'.$request['id'])) ?>" title="Open ticket"><?= icon('chevron-right') ?></a>
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
            <?= icon('clipboard') ?>
            <h3>No requests found</h3>
            <p><?= $search !== '' || $status !== '' ? 'Try a different filter or search term.' : 'Create your first service request to get started.' ?></p>
            <a class="btn btn-primary btn-sm" href="<?= e(url('/customer/requests/new')) ?>">Create a request</a>
        </div>
    </div>
<?php } ?>
