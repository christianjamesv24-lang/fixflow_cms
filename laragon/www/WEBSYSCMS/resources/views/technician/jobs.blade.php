@extends('layouts.app')

@section('content')
<?php
$statusLabels = ['pending' => 'Pending', 'assigned' => 'Assigned', 'accepted' => 'Accepted', 'in_progress' => 'In progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
?>
<div class="page-head">
    <div>
        <p class="eyebrow">Work queue</p>
        <h1>Assigned jobs</h1>
        <p><?= number_format($total) ?> job<?= $total === 1 ? '' : 's' ?> match your filters.</p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-ghost" href="<?= e(url('/technician/schedule')) ?>"><?= icon('calendar') ?> Schedule</a>
    </div>
</div>

<div class="chips">
    <a class="chip <?= $status === '' ? 'active' : '' ?>" href="<?= e(query_url(['status' => null, 'page' => null])) ?>">
        All <span class="chip-count"><?= array_sum($counts) ?></span>
    </a>
    <?php foreach ($counts as $key => $count): ?>
        <a class="chip <?= $status === $key ? 'active' : '' ?>" href="<?= e(query_url(['status' => $key, 'page' => null])) ?>">
            <?= e($statusLabels[$key] ?? ucfirst($key)) ?> <span class="chip-count"><?= $count ?></span>
        </a>
    <?php endforeach; ?>
</div>

<form class="filter-bar" method="get" action="<?= e(url('/technician/jobs')) ?>">
    <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <div class="field field-grow">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Ticket, customer, or appliance">
    </div>
    <button class="btn btn-primary btn-sm" type="submit"><?= icon('search') ?> Search</button>
    <?php if ($search !== '' || $status !== ''): ?>
        <a class="filter-reset" href="<?= e(url('/technician/jobs')) ?>">Reset</a>
    <?php endif; ?>
</form>

<?php if ($jobs): ?>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Job</th>
                    <th>Customer</th>
                    <th>Preferred</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jobs as $job): ?>
                    <tr class="<?= $job['priority'] === 'urgent' ? 'row-alert' : '' ?>">
                        <td>
                            <a class="table-ticket" href="<?= e(url('/technician/jobs/' . $job['id'])) ?>"><?= e($job['ticket_no']) ?></a>
                            <div class="cell-muted"><?= e(format_date($job['created_at'], 'M j, Y')) ?></div>
                        </td>
                        <td>
                            <div class="table-main">
                                <strong><?= e($job['title']) ?></strong>
                                <small><?= e($job['category_name']) ?></small>
                            </div>
                        </td>
                        <td>
                            <div class="table-user">
                                <span class="avatar avatar-xs"><?= e(initials($job['customer_name'])) ?></span>
                                <span><strong><?= e($job['customer_name']) ?></strong><small><?= e($job['customer_phone'] ?: '') ?></small></span>
                            </div>
                        </td>
                        <td class="cell-muted"><?= e($job['preferred_date'] ? format_date($job['preferred_date'], 'M j') : 'Flexible') ?></td>
                        <td><?= status_badge($job['priority']) ?></td>
                        <td><?= status_badge($job['status']) ?></td>
                        <td class="text-right">
                            <a class="icon-btn" href="<?= e(url('/technician/jobs/' . $job['id'])) ?>" title="Open job"><?= icon('chevron-right') ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    @include('partials.pagination')
<?php else: ?>
    <div class="panel">
        <div class="empty-state">
            <?= icon('clipboard') ?>
            <h3>No jobs found</h3>
            <p><?= $search !== '' || $status !== '' ? 'Try a different filter or search term.' : 'New assignments will appear here.' ?></p>
        </div>
    </div>
<?php endif; ?>

@endsection
