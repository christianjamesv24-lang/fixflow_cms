@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">Work queue</p>
        <h1>Available jobs</h1>
        <p><?= number_format($total) ?> open request<?= $total === 1 ? '' : 's' ?> match your search.</p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-ghost" href="<?= e(url('/technician/jobs')) ?>"><?= icon('clipboard') ?> Assigned jobs</a>
        <a class="btn btn-ghost" href="<?= e(url('/technician/schedule')) ?>"><?= icon('calendar') ?> Schedule</a>
    </div>
</div>

<form class="filter-bar" method="get" action="<?= e(url('/technician/jobs/available')) ?>">
    <div class="field field-grow">
        <label for="q">Search available requests</label>
        <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Ticket, appliance, or issue">
    </div>
    <button class="btn btn-primary btn-sm" type="submit"><?= icon('search') ?> Search</button>
    <?php if ($search !== ''): ?>
        <a class="filter-reset" href="<?= e(url('/technician/jobs/available')) ?>">Reset</a>
    <?php endif; ?>
</form>

<div class="note-box mt-2">
    <?= icon('info') ?>
    <p>These are customer requests awaiting assignment. Customer contact details and exact street addresses stay private until you claim a job.</p>
</div>

<?php if ($jobs): ?>
    <div class="table-wrap mt-2">
        <table class="table">
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Request</th>
                    <th>General area</th>
                    <th>Preferred visit</th>
                    <th>Priority</th>
                    <th>Posted</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jobs as $job): ?>
                    <tr class="<?= $job['priority'] === 'urgent' ? 'row-alert' : '' ?>">
                        <td>
                            <strong><?= e($job['ticket_no']) ?></strong>
                        </td>
                        <td>
                            <div class="table-main">
                                <strong><?= e($job['title']) ?></strong>
                                <small><?= e($job['category_name']) ?> · <?= e(ucfirst($job['service_type'])) ?></small>
                                <span class="cell-muted"><?= e($job['description']) ?></span>
                                <?php if ($job['brand'] || $job['model']): ?>
                                    <small><?= e(trim(($job['brand'] ?? '').' '.($job['model'] ?? ''))) ?></small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="cell-muted"><?= e($job['general_area']) ?></td>
                        <td class="cell-muted">
                            <?= e($job['preferred_date'] ? format_date($job['preferred_date'], 'M j, Y') : 'Flexible') ?>
                            <?php if ($job['preferred_time']): ?><div><?= e($job['preferred_time']) ?></div><?php endif; ?>
                        </td>
                        <td><?= status_badge($job['priority']) ?></td>
                        <td class="cell-muted"><?= e(format_date($job['created_at'], 'M j, Y')) ?></td>
                        <td>
                            <form method="post" action="<?= e(url('/technician/jobs/'.$job['id'].'/claim')) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-primary btn-sm" type="submit">Claim job</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    @include('partials.pagination')
<?php else: ?>
    <div class="panel mt-2">
        <div class="empty-state">
            <?= icon('clipboard') ?>
            <h3>No available requests</h3>
            <p><?= $search !== '' ? 'Try another search term.' : 'New customer requests will appear here while they are waiting for assignment.' ?></p>
        </div>
    </div>
<?php endif; ?>
@endsection
