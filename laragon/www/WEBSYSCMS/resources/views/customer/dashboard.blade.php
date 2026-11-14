@extends('layouts.app')

@section('content')
<?php
$firstName = explode(' ', $user['name'])[0];
$chatTarget = null;
foreach ($recent as $requestItem) {
    if (!empty($requestItem['technician_name']) && in_array($requestItem['status'], ['assigned', 'accepted', 'in_progress'], true)) {
        $chatTarget = $requestItem;
        break;
    }
}
$messagesUrl = $chatTarget
    ? url('/service-requests/' . $chatTarget['id'] . '/chat')
    : url('/customer/requests');
?>

<section class="dashboard-hero customer-hero">
    <div>
        <p class="dashboard-hero-kicker">Your FixitNow dashboard</p>
        <h1>Welcome back, <?= e($firstName) ?>.</h1>
        <p>Track your repairs, chat with your technician, and book your next visit in seconds.</p>
    </div>
    <a class="btn btn-primary dashboard-hero-cta" href="<?= e(url('/customer/requests/new')) ?>">
        <?= icon('plus') ?> New service request
    </a>
</section>

<section class="quick-action-grid" aria-label="Quick actions">
    <a class="quick-action-card quick-action-primary" href="<?= e(url('/customer/requests/new')) ?>">
        <span class="quick-action-icon"><?= icon('plus') ?></span>
        <span class="quick-action-copy"><strong>Book a repair</strong><small>Tell us what needs fixing.</small></span>
        <?= icon('chevron-right', 'quick-action-arrow') ?>
    </a>
    <a class="quick-action-card" href="<?= e(url('/services')) ?>">
        <span class="quick-action-icon"><?= icon('wrench') ?></span>
        <span class="quick-action-copy"><strong>Browse services</strong><small>Explore appliance repair options.</small></span>
        <?= icon('chevron-right', 'quick-action-arrow') ?>
    </a>
    <a class="quick-action-card" href="<?= e($messagesUrl) ?>">
        <span class="quick-action-icon"><?= icon('message') ?></span>
        <span class="quick-action-copy"><strong>Open messages</strong><small><?= $chatTarget ? 'Continue your request conversation.' : 'Chat appears when a technician is assigned.' ?></small></span>
        <?= icon('chevron-right', 'quick-action-arrow') ?>
    </a>
    <a class="quick-action-card" href="<?= e(url('/profile#saved-addresses')) ?>">
        <span class="quick-action-icon"><?= icon('pin') ?></span>
        <span class="quick-action-copy"><strong>Saved addresses</strong><small>Keep your service locations ready.</small></span>
        <?= icon('chevron-right', 'quick-action-arrow') ?>
    </a>
</section>

<section class="dashboard-stat-grid" aria-label="Repair summary">
    <article class="dashboard-stat-card">
        <span class="dashboard-stat-icon"><?= icon('clipboard') ?></span>
        <div><strong><?= number_format($total) ?></strong><span>Service requests</span></div>
    </article>
    <article class="dashboard-stat-card">
        <span class="dashboard-stat-icon tone-info"><?= icon('activity') ?></span>
        <div><strong><?= number_format($active) ?></strong><span>Active repairs</span></div>
    </article>
    <article class="dashboard-stat-card">
        <span class="dashboard-stat-icon tone-success"><?= icon('check-circle') ?></span>
        <div><strong><?= number_format($counts['completed']) ?></strong><span>Completed jobs</span></div>
    </article>
    <article class="dashboard-stat-card">
        <span class="dashboard-stat-icon tone-warn"><?= icon('credit-card') ?></span>
        <div><strong><?= e(money($spent)) ?></strong><span>Total service spend</span></div>
    </article>
</section>

<div class="dashboard-content-grid">
    <section class="dashboard-section">
        <div class="dashboard-section-heading">
            <div>
                <h2>Your repair activity</h2>
                <p>Active and recent service requests.</p>
            </div>
            <a class="panel-link" href="<?= e(url('/customer/requests')) ?>">View all requests <?= icon('arrow-right') ?></a>
        </div>

        <?php if ($recent): ?>
            <div class="activity-list">
                <?php foreach ($recent as $item): ?>
                    <a class="activity-card" href="<?= e(url('/customer/requests/' . $item['id'])) ?>">
                        <span class="activity-icon"><?= icon('wrench') ?></span>
                        <span class="activity-copy">
                            <span class="activity-title">
                                <strong><?= e($item['title']) ?></strong>
                                <?= status_badge($item['status']) ?>
                            </span>
                            <small><?= e($item['category_name']) ?> · <?= e($item['ticket_no']) ?> · <?= e(format_date($item['created_at'], 'M j, Y')) ?></small>
                            <small><?= e($item['technician_name'] ? 'Technician: ' . $item['technician_name'] : 'Waiting for technician assignment') ?></small>
                        </span>
                        <?= icon('chevron-right', 'activity-arrow') ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="dashboard-empty-state">
                <span class="activity-icon"><?= icon('clipboard') ?></span>
                <h3>No repair requests yet</h3>
                <p>Submit your first service request and we will take it from there.</p>
                <a class="btn btn-primary btn-sm" href="<?= e(url('/customer/requests/new')) ?>">Book a repair</a>
            </div>
        <?php endif; ?>
    </section>

    <aside class="dashboard-side-stack">
        <section class="dashboard-side-card">
            <div class="dashboard-section-heading">
                <div>
                    <h2>Next appointment</h2>
                    <p>Your upcoming technician visit.</p>
                </div>
                <?= icon('calendar', 'dashboard-heading-icon') ?>
            </div>
            <?php if ($upcoming): ?>
                <a class="appointment-highlight" href="<?= e(url('/customer/requests/' . $upcoming['request_id'])) ?>">
                    <span class="appointment-date">
                        <strong><?= e(format_date($upcoming['scheduled_date'], 'd')) ?></strong>
                        <span><?= e(format_date($upcoming['scheduled_date'], 'M')) ?></span>
                    </span>
                    <span class="appointment-main">
                        <strong><?= e($upcoming['title']) ?></strong>
                        <small><?= e($upcoming['ticket_no']) ?> · <?= e(date('g:i A', strtotime($upcoming['scheduled_time']))) ?></small>
                        <small><?= icon('user') ?> <?= e($upcoming['technician_name'] ?: 'Technician to be confirmed') ?></small>
                    </span>
                </a>
                <div class="appointment-highlight-footer">
                    <?= status_badge($upcoming['status']) ?>
                    <a class="panel-link" href="<?= e(url('/customer/requests/' . $upcoming['request_id'])) ?>">View ticket <?= icon('arrow-right') ?></a>
                </div>
            <?php else: ?>
                <div class="dashboard-empty-compact">
                    <?= icon('calendar') ?>
                    <strong>No scheduled visits</strong>
                    <span>Your appointment details will appear here.</span>
                </div>
            <?php endif; ?>
        </section>

        <section class="dashboard-side-card support-card">
            <span class="dashboard-stat-icon"><?= icon('phone') ?></span>
            <div>
                <h2>Need a hand?</h2>
                <p>Contact the service desk for help with a request.</p>
                <a class="panel-link" href="<?= e(url('/contact')) ?>">Contact support <?= icon('arrow-right') ?></a>
            </div>
        </section>
    </aside>
</div>
@endsection
