@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>Notifications</h1>
        <p>Live updates about requests, chat, account and subscription changes.</p>
    </div>
    <?php if ($unreadCount > 0): ?>
        <form method="post" action="<?= e(url('/notifications/read-all')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-ghost"><?= icon('check') ?> Mark all as read</button>
        </form>
    <?php endif; ?>
</div>

<?php if ($notifications->isNotEmpty()): ?>
    <section class="notification-inbox" aria-label="Recent notifications">
        <?php foreach ($notifications as $notification): ?>
            <?php $isUnread = ! (bool) $notification->is_read; ?>
            <?php
            $notificationTitle = strtolower($notification->title);
            $notificationIcon = 'activity';
            if (str_contains($notificationTitle, 'message')) {
                $notificationIcon = 'message';
            } elseif (str_contains($notificationTitle, 'subscription') || str_contains($notificationTitle, 'payment')) {
                $notificationIcon = 'credit-card';
            } elseif (str_contains($notificationTitle, 'approved') || str_contains($notificationTitle, 'accepted')) {
                $notificationIcon = 'check';
            } elseif (str_contains($notificationTitle, 'request')) {
                $notificationIcon = 'clipboard';
            }
            ?>
            <a class="notification-row<?= $isUnread ? ' is-unread' : '' ?>"
               href="<?= e(url('/notifications/'.$notification->id.'/open')) ?>">
                <span class="notification-icon"><?= icon($notificationIcon) ?></span>
                <span class="notification-copy">
                    <strong><?= e($notification->title) ?></strong>
                    <span><?= e($notification->body) ?></span>
                </span>
                <span class="notification-meta">
                    <time datetime="<?= e(date('c', strtotime($notification->created_at))) ?>">
                        <?= e(\Illuminate\Support\Carbon::parse($notification->created_at)->diffForHumans()) ?>
                    </time>
                    <?php if ($isUnread): ?><span class="notification-unread-dot"><span class="sr-only">Unread</span></span><?php endif; ?>
                </span>
            </a>
        <?php endforeach; ?>
    </section>
<?php else: ?>
    <div class="panel">
        <div class="empty-state">
            <?= icon('bell') ?>
            <h3>You're all caught up</h3>
            <p>New job, subscription, and message updates will appear here.</p>
        </div>
    </div>
<?php endif; ?>

<style>
    .notification-inbox {
        overflow: hidden;
        border: 1px solid var(--line, #e2e8f0);
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 5px 16px rgba(15, 23, 42, .045);
    }
    .notification-row {
        display: grid;
        grid-template-columns: 52px minmax(0, 1fr) auto;
        align-items: center;
        gap: 14px;
        padding: 16px 20px;
        color: inherit;
        text-decoration: none;
        border-bottom: 1px solid #edf1f4;
    }
    .notification-row:last-child { border-bottom: 0; }
    .notification-row:hover { background: #f8fafc; }
    .notification-row.is-unread { background: #f8fdfc; }
    .notification-icon {
        display: grid;
        width: 50px;
        height: 50px;
        place-items: center;
        border-radius: 14px;
        color: #0b8176;
        background: #effbf8;
    }
    .notification-icon .icon { width: 22px; height: 22px; }
    .notification-copy { display: grid; gap: 4px; min-width: 0; }
    .notification-copy strong { color: #10233f; font-size: 15px; }
    .notification-copy > span { color: #64748b; font-size: 14px; }
    .notification-meta {
        display: flex;
        align-items: center;
        gap: 12px;
        color: #7b8ba3;
        font-size: 13px;
    }
    .notification-unread-dot {
        width: 10px;
        height: 10px;
        flex: 0 0 10px;
        border-radius: 50%;
        background: #0d9b8c;
    }
    @media (max-width: 640px) {
        .notification-row { grid-template-columns: 42px minmax(0, 1fr) auto; gap: 10px; padding: 14px; }
        .notification-icon { width: 42px; height: 42px; }
        .notification-meta time { max-width: 75px; text-align: right; }
    }
</style>
@endsection
