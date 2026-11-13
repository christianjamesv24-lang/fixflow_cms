<?php
$siteName = setting('site_name', 'FixitNow');
$currentUser = auth()->user();
$roleLabels = ['admin' => 'Administrator', 'technician' => 'Technician', 'customer' => 'Customer'];
$role = $currentUser['role'] ?? 'customer';
$roleLabel = $roleLabels[$role] ?? ucfirst($role);

$navGroups = [];
if ($role === 'admin') {
    $navGroups = [
        'Service desk' => [
            ['label' => 'Overview', 'path' => '/admin', 'icon' => 'grid', 'exact' => true],
            ['label' => 'Service requests', 'path' => '/admin/requests', 'icon' => 'clipboard'],
            ['label' => 'Appointments', 'path' => '/admin/appointments', 'icon' => 'calendar'],
            ['label' => 'Reports', 'path' => '/admin/reports', 'icon' => 'chart'],
        ],
        'People' => [
            ['label' => 'Technicians', 'path' => '/admin/technicians', 'icon' => 'users'],
            ['label' => 'Customers', 'path' => '/admin/customers', 'icon' => 'user'],
            ['label' => 'Feedback', 'path' => '/admin/feedback', 'icon' => 'star'],
        ],
        'Content' => [
            ['label' => 'Services', 'path' => '/admin/services', 'icon' => 'wrench'],
            ['label' => 'CMS pages', 'path' => '/admin/pages', 'icon' => 'file'],
            ['label' => 'Inbox', 'path' => '/admin/messages', 'icon' => 'message'],
            ['label' => 'Live chat', 'path' => '/chat', 'icon' => 'message'],
        ],
        'System' => [
            ['label' => 'Subscriptions', 'path' => '/admin/subscriptions', 'icon' => 'credit-card'],
            ['label' => 'Settings', 'path' => '/admin/settings', 'icon' => 'settings'],
            ['label' => 'Activity log', 'path' => '/admin/activity', 'icon' => 'activity'],
        ],
    ];
} elseif ($role === 'technician') {
    $navGroups = [
        'My work' => [
            ['label' => 'Overview', 'path' => '/technician', 'icon' => 'grid', 'exact' => true],
            ['label' => 'Assigned jobs', 'path' => '/technician/jobs', 'icon' => 'clipboard'],
            ['label' => 'Schedule', 'path' => '/technician/schedule', 'icon' => 'calendar'],
            ['label' => 'Live chat', 'path' => '/chat', 'icon' => 'message'],
        ],
        'Account' => [
            ['label' => 'Subscription', 'path' => '/technician/subscription', 'icon' => 'credit-card'],
            ['label' => 'My profile', 'path' => '/profile', 'icon' => 'user'],
        ],
    ];
} else {
    $navGroups = [
        'My service' => [
            ['label' => 'Overview', 'path' => '/customer', 'icon' => 'grid', 'exact' => true],
            ['label' => 'My requests', 'path' => '/customer/requests', 'icon' => 'clipboard', 'exclude' => '/customer/requests/new'],
            ['label' => 'New request', 'path' => '/customer/requests/new', 'icon' => 'plus', 'exact' => true],
        ],
        'Account' => [
            ['label' => 'My profile', 'path' => '/profile', 'icon' => 'user'],
        ],
    ];
}

$unreadMessages = 0;
if ($role === 'admin') {
    $unreadMessages = (int) db_value("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'");
}

$notificationCount = 0;
$lastNotificationId = 0;
$notificationPollingEnabled = in_array($role, ['admin', 'technician'], true);

if ($notificationPollingEnabled) {
    $notificationCount = (int) db_value(
        'SELECT COUNT(*) FROM user_notifications WHERE user_id = ? AND is_read = 0',
        [$currentUser['id']]
    );
    $lastNotificationId = (int) db_value(
        'SELECT COALESCE(MAX(id), 0) FROM user_notifications WHERE user_id = ?',
        [$currentUser['id']]
    );
}

$pageTitle = $title ?? 'Dashboard';

$currentPath = '/' . trim(request()->path(), '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · <?= e($siteName) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('assets/img/favicon.svg')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
    @include('partials.brand-theme')
</head>
<body class="app-body">
<div class="app-shell" data-app-shell>
    <div class="sidebar-backdrop" data-sidebar-close></div>

    <aside class="app-sidebar" data-sidebar>
        <a class="brand brand-light sidebar-brand" href="<?= e(url(dashboard_url())) ?>">
            @include('partials.brand-mark')
            <span class="brand-text"><?= e($siteName) ?><small>Service desk</small></span>
        </a>

        <div class="sidebar-role">
            <span class="role-dot"></span>
            <?= e($roleLabel) ?> portal
        </div>

        <nav class="sidebar-nav">
            <?php foreach ($navGroups as $groupLabel => $items): ?>
                <div class="sidebar-group">
                    <p class="sidebar-group-label"><?= e($groupLabel) ?></p>
                    <?php foreach ($items as $item): ?>
                        <?php
                        $isActive = !empty($item['exact'])
                            ? $currentPath === rtrim($item['path'], '/')
                            : str_starts_with($currentPath, $item['path']);
                        if ($isActive && !empty($item['exclude']) && str_starts_with($currentPath, $item['exclude'])) {
                            $isActive = false;
                        }
                        ?>
                        <a class="sidebar-link <?= $isActive ? 'active' : '' ?>" href="<?= e(url($item['path'])) ?>">
                            <?= icon($item['icon']) ?>
                            <span><?= e($item['label']) ?></span>
                            <?php if ($item['path'] === '/chat' && $notificationPollingEnabled): ?>
                                <span class="nav-count" data-notification-count<?= $notificationCount === 0 ? ' hidden' : '' ?>><?= $notificationCount ?></span>
                            <?php endif; ?>
                            <?php if ($item['path'] === '/admin/messages' && $unreadMessages > 0): ?>
                                <span class="nav-count"><?= $unreadMessages ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-footer">
            <a class="sidebar-link" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">
                <?= icon('external') ?><span>View website</span>
            </a>
            <a class="sidebar-link" href="<?= e(url('/logout')) ?>">
                <?= icon('logout') ?><span>Sign out</span>
            </a>
        </div>
    </aside>

    <div class="app-main">
        <header class="app-topbar">
            <div class="topbar-left">
                <button class="icon-btn sidebar-toggle" type="button" data-sidebar-toggle aria-label="Open menu"><?= icon('menu') ?></button>
                <div class="topbar-meta">
                    <span class="topbar-portal"><?= e($roleLabel) ?> portal</span>
                    <span class="topbar-date"><?= date('l, F j, Y') ?></span>
                </div>
            </div>

            <div class="topbar-right">
                <a class="icon-btn topbar-quick" href="<?= e(url(dashboard_url())) ?>" title="Dashboard"><?= icon('grid') ?></a>
                <?php if ($notificationPollingEnabled): ?>
                    <a class="icon-btn topbar-notifications"
                       href="<?= e(url('/chat')) ?>"
                       aria-label="<?= $notificationCount ?> unread notifications"
                       title="Notifications"
                       data-notification-poll
                       data-notification-url="<?= e(url('/notifications/poll')) ?>"
                       data-notification-target="<?= e(url('/chat')) ?>"
                       data-last-notification-id="<?= $lastNotificationId ?>">
                        <?= icon('info') ?>
                        <span class="notification-count" data-notification-count<?= $notificationCount === 0 ? ' hidden' : '' ?>><?= $notificationCount ?></span>
                    </a>
                    <span class="sr-only" aria-live="polite" data-notification-status></span>
                <?php endif; ?>
                <details class="user-menu">
                    <summary>
                        <span class="avatar avatar-sm" style="background: <?= e($currentUser['avatar_color'] ?? '#2563eb') ?>"><?= e(initials($currentUser['name'] ?? 'U')) ?></span>
                        <span class="user-menu-text">
                            <strong><?= e($currentUser['name'] ?? 'User') ?></strong>
                            <small><?= e($roleLabel) ?></small>
                        </span>
                        <?= icon('chevron-down') ?>
                    </summary>
                    <div class="user-menu-panel">
                        <a href="<?= e(url('/profile')) ?>"><?= icon('user') ?> My profile</a>
                        <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener"><?= icon('external') ?> View website</a>
                        <div class="user-menu-divider"></div>
                        <a href="<?= e(url('/logout')) ?>" class="danger-text"><?= icon('logout') ?> Sign out</a>
                    </div>
                </details>
            </div>
        </header>

        @include('partials.flash')
        <?php if ($notificationPollingEnabled): ?>
            <div class="toast-stack" role="status" aria-live="polite" aria-atomic="false" data-notification-toasts></div>
        <?php endif; ?>

        <main class="app-content">
            @yield('content')
        </main>
    </div>
</div>

<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
