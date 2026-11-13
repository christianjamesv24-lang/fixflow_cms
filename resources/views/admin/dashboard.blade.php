@extends('layouts.app')

@section('content')
<?php
$activeStatuses = ['pending', 'assigned', 'accepted', 'in_progress'];
$activeTotal = 0;
foreach ($activeStatuses as $status) {
    $activeTotal += $counts[$status] ?? 0;
}
$completionRate = $total > 0 ? round((($counts['completed'] ?? 0) / $total) * 100) : 0;
$statusLabels = ['pending' => 'Pending', 'assigned' => 'Assigned', 'accepted' => 'Accepted', 'in_progress' => 'In progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
$statusMax = max(1, max($counts));
?>
<div class="page-head">
    <div>
        <p class="eyebrow">Service desk overview</p>
        <h1>Good day, <?= e(explode(' ', Auth::user()['name'])[0]) ?></h1>
        <p>Here is how the service desk is performing today, <?= date('F j, Y') ?>.</p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-ghost" href="<?= e(url('/admin/reports')) ?>"><?= icon('chart') ?> Reports</a>
        <a class="btn btn-primary" href="<?= e(url('/admin/requests')) ?>"><?= icon('clipboard') ?> Manage requests</a>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon"><?= icon('clipboard') ?></span>
            <span class="stat-trend up"><?= $completionRate ?>% completed</span>
        </div>
        <div>
            <div class="stat-value"><?= number_format($total) ?></div>
            <div class="stat-label">Total service requests</div>
        </div>
        <div class="stat-foot"><?= $activeTotal ?> active across the desk.</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon tone-warn"><?= icon('clock') ?></span>
        </div>
        <div>
            <div class="stat-value"><?= number_format($activeTotal) ?></div>
            <div class="stat-label">Open jobs</div>
        </div>
        <div class="stat-foot"><?= $counts['pending'] ?? 0 ?> awaiting assignment.</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon tone-success"><?= icon('credit-card') ?></span>
        </div>
        <div>
            <div class="stat-value"><?= e(money($revenue)) ?></div>
            <div class="stat-label">Service revenue</div>
        </div>
        <div class="stat-foot">From completed jobs.</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon tone-info"><?= icon('star') ?></span>
            <span class="stat-trend"><?= number_format($reviewCount) ?> reviews</span>
        </div>
        <div>
            <div class="stat-value"><?= $rating > 0 ? number_format($rating, 1) : '—' ?></div>
            <div class="stat-label">Customer rating</div>
        </div>
        <div class="stat-foot"><?= rating_stars($rating ?: 0) ?></div>
    </div>
</div>

<div class="stat-strip">
    <div class="stat-strip-item">
        <span><?= icon('users') ?> <?= number_format($technicians) ?> technicians</span>
        <small>Active field staff</small>
    </div>
    <div class="stat-strip-item">
        <span><?= icon('user') ?> <?= number_format($customers) ?> customers</span>
        <small>Registered accounts</small>
    </div>
    <div class="stat-strip-item">
        <span><?= icon('calendar') ?> <?= count($todayAppointments) ?> appointments today</span>
        <small>Scheduled or confirmed</small>
    </div>
    <div class="stat-strip-item">
        <span><?= icon('check-circle') ?> <?= number_format($counts['completed'] ?? 0) ?> completed</span>
        <small><?= $completionRate ?>% completion rate</small>
    </div>
</div>

<div class="dash-grid">
    <div class="dash-stack">
        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Request volume</h2>
                    <p class="panel-sub">New service requests over the last six months.</p>
                </div>
                <a class="panel-link" href="<?= e(url('/admin/reports')) ?>">Full report <?= icon('arrow-right') ?></a>
            </div>
            <div class="panel-body">
                <div class="chart-bars">
                    <?php foreach ($trend as $point): ?>
                        <?php $height = max(4, (int) round(($point['value'] / $trendMax) * 150)); ?>
                        <div class="chart-col">
                            <div class="chart-col-bar" style="height: <?= $height ?>px"><span><?= $point['value'] ?></span></div>
                            <div class="chart-col-label"><?= e($point['label']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Latest requests</h2>
                    <p class="panel-sub">Newest tickets across all customers.</p>
                </div>
                <a class="panel-link" href="<?= e(url('/admin/requests')) ?>">View all <?= icon('arrow-right') ?></a>
            </div>
            <?php if ($recent): ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>Technician</th>
                                <th>Priority</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent as $request): ?>
                                <tr class="<?= $request['priority'] === 'urgent' ? 'row-alert' : '' ?>">
                                    <td>
                                        <a class="table-ticket" href="<?= e(url('/admin/requests/' . $request['id'])) ?>"><?= e($request['ticket_no']) ?></a>
                                        <div class="cell-muted"><?= e(format_date($request['created_at'], 'M j')) ?></div>
                                    </td>
                                    <td><?= e($request['customer_name']) ?></td>
                                    <td>
                                        <div class="table-main">
                                            <strong><?= e($request['title']) ?></strong>
                                            <small><?= e($request['category_name']) ?></small>
                                        </div>
                                    </td>
                                    <td><?= e($request['technician_name'] ?: 'Unassigned') ?></td>
                                    <td><?= status_badge($request['priority']) ?></td>
                                    <td><?= status_badge($request['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <?= icon('clipboard') ?>
                    <h3>No requests yet</h3>
                    <p>New customer requests will appear here.</p>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Today's appointments</h2>
                    <p class="panel-sub"><?= count($todayAppointments) ?> visit<?= count($todayAppointments) === 1 ? '' : 's' ?> on the calendar.</p>
                </div>
                <a class="panel-link" href="<?= e(url('/admin/appointments')) ?>">Schedule <?= icon('arrow-right') ?></a>
            </div>
            <?php if ($todayAppointments): ?>
                <div class="appointment-list">
                    <?php foreach ($todayAppointments as $appointment): ?>
                        <div class="appointment-item">
                            <div class="appointment-date">
                                <strong><?= e(format_date($appointment['scheduled_date'], 'd')) ?></strong>
                                <span><?= e(format_date($appointment['scheduled_date'], 'M')) ?></span>
                            </div>
                            <div class="appointment-main">
                                <strong><?= e($appointment['title']) ?></strong>
                                <small><?= e($appointment['ticket_no']) ?> · <?= e($appointment['customer_name']) ?></small>
                                <small><?= icon('user') ?> <?= e($appointment['technician_name'] ?: 'Technician unassigned') ?></small>
                            </div>
                            <div class="appointment-side">
                                <span class="appointment-time"><?= icon('clock') ?> <?= e(format_time($appointment['scheduled_time'])) ?></span>
                                <?= status_badge($appointment['status']) ?>
                                <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/requests/' . $appointment['request_id'])) ?>">Open</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <?= icon('calendar') ?>
                    <h3>No visits booked today</h3>
                    <p>Schedule a technician from any open request.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <div class="dash-stack">
        <section class="panel">
            <div class="panel-head">
                <h2 class="panel-title">Pipeline</h2>
            </div>
            <div class="panel-body">
                <div class="bar-list">
                    <?php foreach ($counts as $status => $count): ?>
                        <div class="bar-row">
                            <div class="bar-row-label">
                                <span><?= e($statusLabels[$status] ?? ucfirst($status)) ?></span>
                                <span><?= $count ?></span>
                            </div>
                            <div class="bar-track">
                                <span class="bar-fill <?= status_badge($status) !== '' ? 'tone-' . (['pending' => 'warn', 'assigned' => 'info', 'accepted' => 'info', 'in_progress' => 'info', 'completed' => 'success', 'cancelled' => 'danger'][$status] ?? 'muted') : 'tone-muted' ?>" style="width: <?= max(3, (int) round(($count / $statusMax) * 100)) ?>%"></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Technician performance</h2>
                    <p class="panel-sub">Completed jobs and ratings.</p>
                </div>
                <a class="panel-link" href="<?= e(url('/admin/technicians')) ?>">Manage</a>
            </div>
            <div class="mini-list">
                <?php foreach ($technicianPerformance as $tech): ?>
                    <a class="mini-item" href="<?= e(url('/admin/requests?technician=' . $tech['id'])) ?>">
                        <span class="avatar avatar-sm" style="background: <?= e($tech['avatar_color'] ?: '#2563eb') ?>"><?= e(initials($tech['name'])) ?></span>
                        <div class="mini-main">
                            <strong><?= e($tech['name']) ?></strong>
                            <small><?= e($tech['specialty'] ?: 'General technician') ?></small>
                        </div>
                        <span class="mini-meta">
                            <?= (int) $tech['completed'] ?>/<?= (int) $tech['jobs'] ?> jobs<br>
                            <span class="text-muted"><?= $tech['rating'] > 0 ? number_format((float) $tech['rating'], 1) . ' ★' : 'No reviews' ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
                <?php if (!$technicianPerformance): ?>
                    <div class="empty-state" style="padding:24px 20px">
                        <h3>No technicians yet</h3>
                        <p>Add your first technician to start assigning jobs.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Top service categories</h2>
                    <p class="panel-sub">Requests per appliance type.</p>
                </div>
            </div>
            <div class="panel-body">
                <div class="bar-list">
                    <?php foreach ($categories as $category): ?>
                        <div class="bar-row">
                            <div class="bar-row-label">
                                <span><?= e($category['name']) ?></span>
                                <span><?= (int) $category['total'] ?></span>
                            </div>
                            <div class="bar-track">
                                <span class="bar-fill" style="width: <?= max(3, (int) round(($category['total'] / $categoryMax) * 100)) ?>%"></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </div>
</div>

@endsection
