@extends('layouts.app')

@section('content')
<?php $firstName = explode(' ', $user['name'])[0]; ?>
<div class="page-head">
    <div>
        <p class="eyebrow">Customer portal</p>
        <h1>Welcome back, <?= e($firstName) ?></h1>
        <p>Here is what is happening with your appliance service.</p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-primary" href="<?= e(url('/customer/requests/new')) ?>"><?= icon('plus') ?> New request</a>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon"><?= icon('clipboard') ?></span>
            <span class="stat-trend">All time</span>
        </div>
        <div>
            <div class="stat-value"><?= number_format($total) ?></div>
            <div class="stat-label">Service requests</div>
        </div>
        <div class="stat-foot">Every request keeps a full status history.</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon tone-warn"><?= icon('clock') ?></span>
            <span class="stat-trend"><?= $counts['pending'] ?> awaiting assignment</span>
        </div>
        <div>
            <div class="stat-value"><?= number_format($active) ?></div>
            <div class="stat-label">Active requests</div>
        </div>
        <div class="stat-foot">Pending, assigned, accepted, or in progress.</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon tone-success"><?= icon('check-circle') ?></span>
        </div>
        <div>
            <div class="stat-value"><?= number_format($counts['completed']) ?></div>
            <div class="stat-label">Completed jobs</div>
        </div>
        <div class="stat-foot">Service reports are stored in each ticket.</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon tone-info"><?= icon('credit-card') ?></span>
        </div>
        <div>
            <div class="stat-value"><?= e(money($spent)) ?></div>
            <div class="stat-label">Total service spend</div>
        </div>
        <div class="stat-foot">Sum of final costs on completed jobs.</div>
    </div>
</div>

<div class="dash-grid">
    <div class="dash-stack">
        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Recent requests</h2>
                    <p class="panel-sub">Your latest tickets and their current status.</p>
                </div>
                <a class="panel-link" href="<?= e(url('/customer/requests')) ?>">View all <?= icon('arrow-right') ?></a>
            </div>

            <?php if ($recent): ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Service</th>
                                <th>Technician</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent as $item): ?>
                                <tr>
                                    <td>
                                        <a class="table-ticket" href="<?= e(url('/customer/requests/' . $item['id'])) ?>"><?= e($item['ticket_no']) ?></a>
                                        <div class="cell-muted"><?= e(format_date($item['created_at'], 'M j')) ?></div>
                                    </td>
                                    <td>
                                        <div class="table-main">
                                            <strong><?= e($item['title']) ?></strong>
                                            <small><?= e($item['category_name']) ?></small>
                                        </div>
                                    </td>
                                    <td><?= e($item['technician_name'] ?: 'Not assigned') ?></td>
                                    <td><?= status_badge($item['status']) ?></td>
                                    <td class="text-right">
                                        <a class="icon-btn" href="<?= e(url('/customer/requests/' . $item['id'])) ?>" title="View ticket"><?= icon('chevron-right') ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <?= icon('clipboard') ?>
                    <h3>No requests yet</h3>
                    <p>Submit your first service request and we will assign a technician.</p>
                    <a class="btn btn-primary btn-sm" href="<?= e(url('/customer/requests/new')) ?>">Create a request</a>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel">
            <div class="panel-head">
                <h2 class="panel-title">Need a hand?</h2>
            </div>
            <div class="panel-body">
                <div class="feature-list" style="margin-top:0">
                    <div class="feature-item">
                        <span class="feature-icon"><?= icon('phone') ?></span>
                        <div>
                            <h3>Call the service desk</h3>
                            <p><?= e(setting('contact_phone')) ?> · <?= e(setting('business_hours')) ?></p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon"><?= icon('mail') ?></span>
                        <div>
                            <h3>Email support</h3>
                            <p><?= e(setting('contact_email')) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="dash-stack">
        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Next appointment</h2>
                    <p class="panel-sub">Your upcoming technician visit.</p>
                </div>
            </div>
            <?php if ($upcoming): ?>
                <div class="panel-body">
                    <div class="appointment-item" style="padding:0;border:none">
                        <div class="appointment-date">
                            <strong><?= e(format_date($upcoming['scheduled_date'], 'd')) ?></strong>
                            <span><?= e(format_date($upcoming['scheduled_date'], 'M')) ?></span>
                        </div>
                        <div class="appointment-main">
                            <strong><?= e($upcoming['title']) ?></strong>
                            <small><?= e($upcoming['ticket_no']) ?> · <?= e(date('g:i A', strtotime($upcoming['scheduled_time']))) ?> · <?= e($upcoming['duration_minutes']) ?> mins</small>
                            <small><?= icon('user') ?> <?= e($upcoming['technician_name'] ?: 'Technician to be confirmed') ?></small>
                        </div>
                    </div>
                    <div class="inline-actions mt-2">
                        <?= status_badge($upcoming['status']) ?>
                        <a class="btn btn-soft btn-sm" href="<?= e(url('/customer/requests/' . $upcoming['request_id'])) ?>">View ticket</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state" style="padding:28px 20px">
                    <?= icon('calendar') ?>
                    <h3>No scheduled visits</h3>
                    <p>When an appointment is booked it will show up here.</p>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel">
            <div class="panel-head">
                <h2 class="panel-title">Request status</h2>
            </div>
            <div class="panel-body">
                <div class="bar-list">
                    <?php
                    $statusTones = ['pending' => 'warn', 'assigned' => 'info', 'accepted' => 'info', 'in_progress' => 'brand', 'completed' => 'success', 'cancelled' => 'muted'];
                    $maxCount = max(1, $total);
                    foreach ($counts as $key => $value):
                    ?>
                        <div class="bar-row">
                            <span class="bar-row-label"><?= e(ucwords(str_replace('_', ' ', $key))) ?></span>
                            <span class="bar-track"><span class="bar-fill tone-<?= $statusTones[$key] ?? 'muted' ?>" style="width: <?= $value > 0 ? max(4, round($value / $maxCount * 100)) : 0 ?>%; display:block"></span></span>
                            <span class="bar-row-value"><?= $value ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-body stack-sm">
                <a class="btn btn-primary btn-block" href="<?= e(url('/customer/requests/new')) ?>"><?= icon('plus') ?> Book a repair</a>
                <a class="btn btn-ghost btn-block" href="<?= e(url('/services')) ?>">Browse services</a>
            </div>
        </section>
    </div>
</div>

@endsection
