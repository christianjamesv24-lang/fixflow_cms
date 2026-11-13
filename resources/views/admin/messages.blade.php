@extends('layouts.app')

@section('content')
<?php
$statusLabels = ['new' => 'New', 'read' => 'Read', 'replied' => 'Replied'];
$statuses = ['new', 'read', 'replied'];
?>
<div class="page-head">
    <div>
        <p class="eyebrow">Content</p>
        <h1>Inbox</h1>
        <p><?= $counts['new'] ?> unread · <?= array_sum($counts) ?> total contact message<?= array_sum($counts) === 1 ? '' : 's' ?>.</p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-ghost" href="<?= e(url('/contact')) ?>" target="_blank" rel="noopener"><?= icon('external') ?> Contact page</a>
    </div>
</div>

<div class="chips">
    <a class="chip <?= $status === '' ? 'active' : '' ?>" href="<?= e(url('/admin/messages')) ?>">
        All <span class="chip-count"><?= array_sum($counts) ?></span>
    </a>
    <?php foreach ($statuses as $key): ?>
        <a class="chip <?= $status === $key ? 'active' : '' ?>" href="<?= e(query_url(['status' => $key])) ?>">
            <?= e($statusLabels[$key]) ?> <span class="chip-count"><?= $counts[$key] ?></span>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($messages): ?>
    <div class="dash-stack">
        <?php foreach ($messages as $message): ?>
            <section class="panel">
                <div class="panel-head">
                    <div class="table-user">
                        <span class="avatar" style="background: <?= $message['status'] === 'new' ? '#2563eb' : '#64748b' ?>"><?= e(initials($message['name'])) ?></span>
                        <span>
                            <strong><?= e($message['name']) ?></strong>
                            <small><?= e($message['email']) ?><?= $message['phone'] ? ' · ' . e($message['phone']) : '' ?></small>
                        </span>
                    </div>
                    <div class="inline-actions">
                        <?= status_badge($message['status']) ?>
                        <span class="cell-muted"><?= e(format_datetime($message['created_at'])) ?></span>
                    </div>
                </div>
                <div class="panel-body">
                    <h3 style="font-size:1rem;margin-bottom:6px"><?= e($message['subject']) ?></h3>
                    <p class="detail-desc" style="margin-bottom:0"><?= e($message['message']) ?></p>
                </div>
                <div class="panel-body" style="border-top:1px solid var(--line);display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end">
                    <a class="btn btn-ghost btn-sm" href="mailto:<?= e($message['email']) ?>?subject=<?= e(rawurlencode('Re: ' . $message['subject'])) ?>"><?= icon('mail') ?> Reply by email</a>
                    <?php foreach ($statuses as $target): ?>
                        <?php if ($message['status'] !== $target): ?>
                            <form method="post" action="<?= e(url('/admin/messages/' . $message['id'] . '/status')) ?>" class="inline-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="status" value="<?= e($target) ?>">
                                <button class="btn btn-soft btn-sm" type="submit">Mark <?= e($statusLabels[$target]) ?></button>
                            </form>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="panel">
        <div class="empty-state">
            <?= icon('message') ?>
            <h3>No messages</h3>
            <p><?= $status !== '' ? 'No messages match this filter.' : 'Messages from the contact form will arrive here.' ?></p>
            <?php if ($status !== ''): ?>
                <a class="btn btn-soft btn-sm" href="<?= e(url('/admin/messages')) ?>">View all messages</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

@endsection
