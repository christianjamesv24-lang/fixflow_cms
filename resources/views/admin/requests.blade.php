@extends('layouts.app')

@section('content')
<?php
$statusLabels = ['pending' => 'Pending', 'assigned' => 'Assigned', 'accepted' => 'Accepted', 'in_progress' => 'In progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
$priorityLabels = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'];
$hasFilters = $filters['status'] !== '' || $filters['priority'] !== '' || $filters['technician'] > 0 || $filters['q'] !== '';
?>
<div class="page-head">
    <div>
        <p class="eyebrow">Service desk</p>
        <h1>Service requests</h1>
        <p><?= number_format($total) ?> request<?= $total === 1 ? '' : 's' ?> match the current view.</p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-ghost" href="<?= e(url('/admin/appointments')) ?>"><?= icon('calendar') ?> Appointments</a>
        <a class="btn btn-ghost" href="<?= e(url('/admin/reports')) ?>"><?= icon('download') ?> Export</a>
    </div>
</div>

<div class="chips">
    <a class="chip <?= $filters['status'] === '' ? 'active' : '' ?>" href="<?= e(query_url(['status' => null, 'page' => null])) ?>">
        All <span class="chip-count"><?= array_sum($counts) ?></span>
    </a>
    <?php foreach ($counts as $key => $count): ?>
        <a class="chip <?= $filters['status'] === $key ? 'active' : '' ?>" href="<?= e(query_url(['status' => $key, 'page' => null])) ?>">
            <?= e($statusLabels[$key] ?? ucfirst($key)) ?> <span class="chip-count"><?= $count ?></span>
        </a>
    <?php endforeach; ?>
</div>

<form class="filter-bar" method="get" action="<?= e(url('/admin/requests')) ?>">
    <div class="field field-grow">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="Ticket, customer, appliance, or title">
    </div>
    <div class="field">
        <label for="priority">Priority</label>
        <select id="priority" name="priority">
            <option value="">Any priority</option>
            <?php foreach ($priorityLabels as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $filters['priority'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="technician">Technician</label>
        <select id="technician" name="technician">
            <option value="">Any technician</option>
            <?php foreach ($technicians as $technician): ?>
                <option value="<?= (int) $technician['id'] ?>" <?= $filters['technician'] === (int) $technician['id'] ? 'selected' : '' ?>><?= e($technician['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if ($filters['status'] !== ''): ?><input type="hidden" name="status" value="<?= e($filters['status']) ?>"><?php endif; ?>
    <button class="btn btn-primary btn-sm" type="submit"><?= icon('filter') ?> Apply</button>
    <?php if ($hasFilters): ?>
        <a class="filter-reset" href="<?= e(url('/admin/requests')) ?>">Reset</a>
    <?php endif; ?>
</form>

<?php if ($requests): ?>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Customer</th>
                    <th>Service</th>
                    <th>Technician</th>
                    <th>Preferred</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr class="<?= $request['priority'] === 'urgent' && !in_array($request['status'], ['completed', 'cancelled'], true) ? 'row-alert' : '' ?>">
                        <td>
                            <a class="table-ticket" href="<?= e(url('/admin/requests/' . $request['id'])) ?>"><?= e($request['ticket_no']) ?></a>
                            <div class="cell-muted"><?= e(format_date($request['created_at'], 'M j, Y')) ?></div>
                        </td>
                        <td>
                            <div class="table-user">
                                <span class="avatar avatar-xs"><?= e(initials($request['customer_name'])) ?></span>
                                <span><strong><?= e($request['customer_name']) ?></strong><small><?= e($request['customer_phone'] ?: '') ?></small></span>
                            </div>
                        </td>
                        <td>
                            <div class="table-main">
                                <strong><?= e($request['title']) ?></strong>
                                <small><?= e($request['category_name']) ?> · <?= e(ucfirst($request['service_type'])) ?></small>
                            </div>
                        </td>
                        <td><?= $request['technician_name'] ? e($request['technician_name']) : '<span class="text-muted">Unassigned</span>' ?></td>
                        <td class="cell-muted"><?= e($request['preferred_date'] ? format_date($request['preferred_date'], 'M j') : 'Flexible') ?></td>
                        <td><?= status_badge($request['priority']) ?></td>
                        <td><?= status_badge($request['status']) ?></td>
                        <td class="text-right">
                            <a class="icon-btn" href="<?= e(url('/admin/requests/' . $request['id'])) ?>" title="Open request"><?= icon('chevron-right') ?></a>
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
            <h3>No requests found</h3>
            <p><?= $hasFilters ? 'Try adjusting the filters or search term.' : 'Customer requests will appear here.' ?></p>
            <?php if ($hasFilters): ?>
                <a class="btn btn-soft btn-sm" href="<?= e(url('/admin/requests')) ?>">Clear filters</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

@endsection
