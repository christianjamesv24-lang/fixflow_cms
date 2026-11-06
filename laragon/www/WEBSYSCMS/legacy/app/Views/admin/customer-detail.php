<?php $upcomingDate = $upcoming ? format_date($upcoming['scheduled_date'], 'l, M j').' at '.format_time($upcoming['scheduled_time']) : null; ?>
<div class="page-head">
    <div>
        <nav class="breadcrumb" style="margin-bottom:8px">
            <a href="<?= e(url('/admin/customers')) ?>">Customers</a>
            <?= icon('chevron-right') ?>
            <span><?= e($customer['name']) ?></span>
        </nav>
        <h1><?= e($customer['name']) ?></h1>
        <p>Customer since <?= e(format_date($customer['created_at'], 'F Y')) ?> · <?= e($customer['email']) ?></p>
    </div>
    <div class="page-head-actions">
        <?= status_badge($customer['status']) ?>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/requests?q='.urlencode($customer['email']))) ?>"><?= icon('clipboard') ?> Their requests</a>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-card-top"><span class="stat-icon"><?= icon('clipboard') ?></span></div>
        <div>
            <div class="stat-value"><?= (int) $stats['total'] ?></div>
            <div class="stat-label">Total requests</div>
        </div>
        <div class="stat-foot">All time.</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top"><span class="stat-icon tone-warn"><?= icon('clock') ?></span></div>
        <div>
            <div class="stat-value"><?= (int) $stats['active'] ?></div>
            <div class="stat-label">Active jobs</div>
        </div>
        <div class="stat-foot">Pending, assigned, or in progress.</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top"><span class="stat-icon tone-success"><?= icon('check-circle') ?></span></div>
        <div>
            <div class="stat-value"><?= (int) $stats['completed'] ?></div>
            <div class="stat-label">Completed jobs</div>
        </div>
        <div class="stat-foot">Closed with a service report.</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top"><span class="stat-icon tone-info"><?= icon('credit-card') ?></span></div>
        <div>
            <div class="stat-value"><?= e(money($stats['spent'])) ?></div>
            <div class="stat-label">Lifetime spend</div>
        </div>
        <div class="stat-foot">Across completed jobs.</div>
    </div>
</div>

<div class="detail-layout">
    <div class="dash-stack">
        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Service history</h2>
                    <p class="panel-sub">Every request filed by this customer.</p>
                </div>
            </div>
            <?php if ($requests) { ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Service</th>
                                <th>Technician</th>
                                <th>Created</th>
                                <th>Cost</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request) { ?>
                                <tr>
                                    <td>
                                        <a class="table-ticket" href="<?= e(url('/admin/requests/'.$request['id'])) ?>"><?= e($request['ticket_no']) ?></a>
                                    </td>
                                    <td>
                                        <div class="table-main">
                                            <strong><?= e($request['title']) ?></strong>
                                            <small><?= e($request['category_name']) ?></small>
                                        </div>
                                    </td>
                                    <td><?= e($request['technician_name'] ?: 'Unassigned') ?></td>
                                    <td class="cell-muted"><?= e(format_date($request['created_at'], 'M j, Y')) ?></td>
                                    <td class="cell-money"><?= e(money($request['final_cost'] ?: $request['estimated_cost'])) ?></td>
                                    <td><?= status_badge($request['status']) ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } else { ?>
                <div class="empty-state">
                    <?= icon('clipboard') ?>
                    <h3>No requests yet</h3>
                    <p>This customer has not booked any service.</p>
                </div>
            <?php } ?>
        </section>
    </div>

    <div class="sticky-col">
        <section class="panel">
            <div class="panel-body">
                <div class="profile-card" style="border:none;padding:0">
                    <span class="avatar avatar-lg" style="background: <?= e($customer['avatar_color'] ?: '#2563eb') ?>"><?= e(initials($customer['name'])) ?></span>
                    <h2><?= e($customer['name']) ?></h2>
                    <p class="role-label">Customer</p>
                </div>
                <div class="divider"></div>
                <div class="stack-sm">
                    <span class="text-sm text-muted"><?= icon('mail') ?> <?= e($customer['email']) ?></span>
                    <span class="text-sm text-muted"><?= icon('phone') ?> <?= e($customer['phone'] ?: 'No phone on file') ?></span>
                    <span class="text-sm text-muted"><?= icon('pin') ?> <?= e($customer['address'] ?: 'No address on file') ?></span>
                </div>
                <?php if ($upcoming) { ?>
                    <div class="divider"></div>
                    <span class="info-label">Next appointment</span>
                    <div class="note-box mt-1" style="margin-bottom:0">
                        <?= icon('calendar') ?>
                        <p><strong><?= e($upcomingDate) ?></strong><br><?= e($upcoming['ticket_no']) ?> · <?= e($upcoming['technician_name'] ?: 'Technician unassigned') ?></p>
                    </div>
                <?php } ?>
            </div>
        </section>
    </div>
</div>
