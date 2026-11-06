<?php $firstName = explode(' ', $user['name'])[0]; ?>
<div class="page-head">
    <div>
        <p class="eyebrow">Technician portal</p>
        <h1>Good day, <?= e($firstName) ?></h1>
        <p><?= e($user['specialty'] ?: 'Your assigned jobs and schedule at a glance.') ?></p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-ghost" href="<?= e(url('/technician/schedule')) ?>"><?= icon('calendar') ?> My schedule</a>
        <a class="btn btn-primary" href="<?= e(url('/technician/jobs')) ?>"><?= icon('clipboard') ?> Assigned jobs</a>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon"><?= icon('clipboard') ?></span>
            <span class="stat-trend"><?= $counts['assigned'] ?> new</span>
        </div>
        <div>
            <div class="stat-value"><?= number_format($active) ?></div>
            <div class="stat-label">Active jobs</div>
        </div>
        <div class="stat-foot">Assigned, accepted, or in progress.</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon tone-warn"><?= icon('wrench') ?></span>
        </div>
        <div>
            <div class="stat-value"><?= number_format($counts['in_progress']) ?></div>
            <div class="stat-label">In progress</div>
        </div>
        <div class="stat-foot">Jobs currently on site.</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon tone-success"><?= icon('check-circle') ?></span>
        </div>
        <div>
            <div class="stat-value"><?= number_format($completed) ?></div>
            <div class="stat-label">Completed jobs</div>
        </div>
        <div class="stat-foot">All completed with a service report.</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon tone-info"><?= icon('star') ?></span>
            <span class="stat-trend"><?= $reviewCount ?> reviews</span>
        </div>
        <div>
            <div class="stat-value"><?= $rating > 0 ? number_format($rating, 1) : '—' ?></div>
            <div class="stat-label">Average rating</div>
        </div>
        <div class="stat-foot"><?= rating_stars($rating ?: 0) ?></div>
    </div>
</div>

<div class="dash-grid">
    <div class="dash-stack">
        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Today's schedule</h2>
                    <p class="panel-sub"><?= date('l, F j') ?> · <?= count($today) ?> appointment<?= count($today) === 1 ? '' : 's' ?></p>
                </div>
                <a class="panel-link" href="<?= e(url('/technician/schedule')) ?>">Full schedule <?= icon('arrow-right') ?></a>
            </div>

            <?php if ($today) { ?>
                <div class="appointment-list">
                    <?php foreach ($today as $appointment) { ?>
                        <div class="appointment-item">
                            <div class="appointment-date">
                                <strong><?= e(format_date($appointment['scheduled_date'], 'd')) ?></strong>
                                <span><?= e(format_date($appointment['scheduled_date'], 'M')) ?></span>
                            </div>
                            <div class="appointment-main">
                                <strong><?= e($appointment['title']) ?></strong>
                                <small><?= e($appointment['ticket_no']) ?> · <?= e($appointment['customer_name']) ?> · <?= e($appointment['category_name']) ?></small>
                                <small><?= icon('pin') ?> <?= e(truncate($appointment['address'], 60)) ?></small>
                            </div>
                            <div class="appointment-side">
                                <span class="appointment-time"><?= icon('clock') ?> <?= e(format_time($appointment['scheduled_time'])) ?></span>
                                <?= status_badge($appointment['status']) ?>
                                <a class="btn btn-ghost btn-sm" href="<?= e(url('/technician/jobs/'.$appointment['request_id'])) ?>">Open</a>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <div class="empty-state">
                    <?= icon('calendar') ?>
                    <h3>Nothing scheduled today</h3>
                    <p>Check your upcoming schedule or pick up an active job.</p>
                </div>
            <?php } ?>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Priority jobs</h2>
                    <p class="panel-sub">Your active assignments, highest priority first.</p>
                </div>
                <a class="panel-link" href="<?= e(url('/technician/jobs')) ?>">All jobs <?= icon('arrow-right') ?></a>
            </div>

            <?php if ($activeJobs) { ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Job</th>
                                <th>Customer</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activeJobs as $job) { ?>
                                <tr>
                                    <td><a class="table-ticket" href="<?= e(url('/technician/jobs/'.$job['id'])) ?>"><?= e($job['ticket_no']) ?></a></td>
                                    <td>
                                        <div class="table-main">
                                            <strong><?= e($job['title']) ?></strong>
                                            <small><?= e($job['category_name']) ?></small>
                                        </div>
                                    </td>
                                    <td><?= e($job['customer_name']) ?></td>
                                    <td><?= status_badge($job['priority']) ?></td>
                                    <td><?= status_badge($job['status']) ?></td>
                                    <td class="text-right">
                                        <a class="icon-btn" href="<?= e(url('/technician/jobs/'.$job['id'])) ?>" title="Open job"><?= icon('chevron-right') ?></a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } else { ?>
                <div class="empty-state">
                    <?= icon('check-circle') ?>
                    <h3>No active jobs</h3>
                    <p>You are all caught up. New assignments will appear here.</p>
                </div>
            <?php } ?>
        </section>
    </div>

    <div class="dash-stack">
        <section class="panel">
            <div class="panel-head">
                <h2 class="panel-title">Your rating</h2>
            </div>
            <div class="panel-body" style="text-align:center">
                <div style="font-size:2.6rem;font-weight:800;letter-spacing:-.04em;line-height:1"><?= $rating > 0 ? number_format($rating, 1) : '—' ?></div>
                <div class="mt-1"><?= rating_stars($rating ?: 0) ?></div>
                <p class="text-muted text-sm mt-1" style="margin-bottom:0"><?= $reviewCount ?> customer review<?= $reviewCount === 1 ? '' : 's' ?></p>
                <?php if ($reviewCount > 0) { ?>
                    <a class="btn btn-soft btn-sm btn-block mt-2" href="<?= e(url('/technician/jobs')) ?>">Review completed jobs</a>
                <?php } ?>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <h2 class="panel-title">Recently completed</h2>
            </div>
            <?php if ($recent) { ?>
                <div class="mini-list">
                    <?php foreach ($recent as $job) { ?>
                        <a class="mini-item" href="<?= e(url('/technician/jobs/'.$job['id'])) ?>">
                            <span class="mini-icon"><?= icon($job['category_icon']) ?></span>
                            <div class="mini-main">
                                <strong><?= e($job['title']) ?></strong>
                                <small><?= e($job['ticket_no']) ?> · <?= e(format_date($job['completed_at'], 'M j')) ?></small>
                            </div>
                            <span class="mini-meta"><?= e(money($job['final_cost'])) ?></span>
                        </a>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <div class="empty-state" style="padding:24px 20px">
                    <?= icon('check-circle') ?>
                    <h3>No completed jobs yet</h3>
                    <p>Completed visits will be listed here.</p>
                </div>
            <?php } ?>
        </section>

        <div class="note-box">
            <?= icon('shield') ?>
            <p><strong>Reminder:</strong> Confirm the estimate with the customer before replacing any parts, and always submit the service report before leaving.</p>
        </div>
    </div>
</div>
