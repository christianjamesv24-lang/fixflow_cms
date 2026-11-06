<?php
$statusLabels = ['scheduled' => 'Scheduled', 'confirmed' => 'Confirmed', 'rescheduled' => 'Rescheduled', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'no_show' => 'No show'];
$hasFilters = $filters['status'] !== '' || $filters['from'] !== '' || $filters['to'] !== '';
?>
<div class="page-head">
    <div>
        <p class="eyebrow">Dispatch</p>
        <h1>Appointments</h1>
        <p><?= number_format($total) ?> appointment<?= $total === 1 ? '' : 's' ?> in the selected period.</p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-ghost" href="<?= e(url('/admin/requests')) ?>"><?= icon('clipboard') ?> Service requests</a>
    </div>
</div>

<div class="chips">
    <a class="chip <?= $filters['status'] === '' ? 'active' : '' ?>" href="<?= e(query_url(['status' => null, 'page' => null])) ?>">
        All <span class="chip-count"><?= array_sum($counts) ?></span>
    </a>
    <?php foreach ($counts as $key => $count) { ?>
        <a class="chip <?= $filters['status'] === $key ? 'active' : '' ?>" href="<?= e(query_url(['status' => $key, 'page' => null])) ?>">
            <?= e($statusLabels[$key] ?? ucfirst($key)) ?> <span class="chip-count"><?= $count ?></span>
        </a>
    <?php } ?>
</div>

<form class="filter-bar" method="get" action="<?= e(url('/admin/appointments')) ?>">
    <?php if ($filters['status'] !== '') { ?><input type="hidden" name="status" value="<?= e($filters['status']) ?>"><?php } ?>
    <div class="field">
        <label for="from">From</label>
        <input type="date" id="from" name="from" value="<?= e($filters['from']) ?>">
    </div>
    <div class="field">
        <label for="to">To</label>
        <input type="date" id="to" name="to" value="<?= e($filters['to']) ?>">
    </div>
    <button class="btn btn-primary btn-sm" type="submit"><?= icon('filter') ?> Filter dates</button>
    <?php if ($hasFilters) { ?>
        <a class="filter-reset" href="<?= e(url('/admin/appointments')) ?>">Reset</a>
    <?php } ?>
</form>

<?php if ($appointments) { ?>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Schedule</th>
                    <th>Ticket</th>
                    <th>Customer</th>
                    <th>Technician</th>
                    <th>Duration</th>
                    <th>Status</th>
                    <th>Update</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appointment) { ?>
                    <tr>
                        <td>
                            <div class="cell-strong"><?= e(format_date($appointment['scheduled_date'], 'M j, Y')) ?></div>
                            <div class="cell-muted"><?= e(format_time($appointment['scheduled_time'])) ?></div>
                        </td>
                        <td>
                            <a class="table-ticket" href="<?= e(url('/admin/requests/'.$appointment['request_id'])) ?>"><?= e($appointment['ticket_no']) ?></a>
                            <div class="cell-muted"><?= e(truncate($appointment['title'], 32)) ?></div>
                        </td>
                        <td><?= e($appointment['customer_name']) ?></td>
                        <td><?= $appointment['technician_name'] ? e($appointment['technician_name']) : '<span class="text-muted">Unassigned</span>' ?></td>
                        <td class="cell-muted"><?= (int) $appointment['duration_minutes'] ?> min</td>
                        <td><?= status_badge($appointment['status']) ?></td>
                        <td>
                            <form class="inline-form" method="post" action="<?= e(url('/admin/appointments/'.$appointment['id'].'/status')) ?>">
                                <?= csrf_field() ?>
                                <select name="status" aria-label="Appointment status">
                                    <?php foreach ($statusLabels as $value => $label) { ?>
                                        <option value="<?= e($value) ?>" <?= $appointment['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php } ?>
                                </select>
                                <button class="btn btn-soft btn-sm" type="submit">Save</button>
                            </form>
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
            <?= icon('calendar') ?>
            <h3>No appointments found</h3>
            <p><?= $hasFilters ? 'Try a different date range or status.' : 'Scheduled visits will appear here.' ?></p>
            <?php if ($hasFilters) { ?>
                <a class="btn btn-soft btn-sm" href="<?= e(url('/admin/appointments')) ?>">Clear filters</a>
            <?php } ?>
        </div>
    </div>
<?php } ?>
