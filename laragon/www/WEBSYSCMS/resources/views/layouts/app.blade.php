<?php
$siteName = setting('site_name', 'FixitNow');
$currentUser = auth()->user();
$roleLabels = ['admin' => 'Administrator', 'technician' => 'Technician', 'customer' => 'Customer'];
$role = $currentUser['role'] ?? 'customer';
$roleLabel = $roleLabels[$role] ?? ucfirst($role);

$primaryNav = [];
$moreNav = [];
if ($role === 'admin') {
    $primaryNav = [
        ['label' => 'Dashboard', 'path' => '/admin', 'exact' => true],
        ['label' => 'Requests', 'path' => '/admin/requests'],
        ['label' => 'Technicians', 'path' => '/admin/technicians'],
        ['label' => 'Customers', 'path' => '/admin/customers'],
        ['label' => 'Messages', 'path' => '/admin/messages'],
    ];
    $moreNav = [
        ['label' => 'Notifications', 'path' => '/notifications'],
        ['label' => 'Appointments', 'path' => '/admin/appointments'],
        ['label' => 'Reports', 'path' => '/admin/reports'],
        ['label' => 'Feedback', 'path' => '/admin/feedback'],
        ['label' => 'Services', 'path' => '/admin/services'],
        ['label' => 'CMS pages', 'path' => '/admin/pages'],
        ['label' => 'Live chat', 'path' => '/chat'],
        ['label' => 'Subscriptions', 'path' => '/admin/subscriptions'],
        ['label' => 'Settings', 'path' => '/admin/settings'],
        ['label' => 'Activity log', 'path' => '/admin/activity'],
    ];
} elseif ($role === 'technician') {
    $primaryNav = [
        ['label' => 'Dashboard', 'path' => '/technician', 'exact' => true],
        ['label' => 'Assigned jobs', 'path' => '/technician/jobs'],
        ['label' => 'Schedule', 'path' => '/technician/schedule'],
        ['label' => 'Messages', 'path' => '/chat'],
        ['label' => 'Subscription', 'path' => '/technician/subscription'],
    ];
    $moreNav = [
        ['label' => 'Notifications', 'path' => '/notifications'],
    ];
} else {
    $primaryNav = [
        ['label' => 'Dashboard', 'path' => '/customer', 'exact' => true],
        ['label' => 'My requests', 'path' => '/customer/requests', 'exclude' => '/customer/requests/new'],
        ['label' => 'Services', 'path' => '/services'],
    ];
}

$unreadMessages = 0;
if ($role === 'admin') {
    $unreadMessages = (int) db_value("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'");
}

$notificationCount = 0;
$lastNotificationId = 0;
$notificationPollingEnabled = in_array($role, ['admin', 'technician', 'customer'], true);

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
$isActiveNavItem = static function (array $item) use ($currentPath): bool {
    $isActive = !empty($item['exact'])
        ? $currentPath === rtrim($item['path'], '/')
        : str_starts_with($currentPath, $item['path']);

    if ($isActive && !empty($item['exclude']) && str_starts_with($currentPath, $item['exclude'])) {
        return false;
    }

    return $isActive;
};
$moreNavIsActive = false;
foreach ($moreNav as $item) {
    if ($isActiveNavItem($item)) {
        $moreNavIsActive = true;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($pageTitle) ?> · <?= e($siteName) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('assets/img/favicon.svg')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
    @include('partials.brand-theme')
</head>
<body class="app-body">
<div class="app-shell dashboard-shell" data-app-shell>
    <header class="dashboard-topbar">
        <a class="dashboard-brand" href="<?= e(url(dashboard_url())) ?>">
            @include('partials.brand-mark')
            <span class="brand-text"><?= e($siteName) ?><small>Appliance care</small></span>
        </a>

        <nav class="dashboard-nav" aria-label="Main navigation">
            <?php foreach ($primaryNav as $item): ?>
                <a class="dashboard-nav-link <?= $isActiveNavItem($item) ? 'active' : '' ?>"
                   href="<?= e(url($item['path'])) ?>"
                   <?= $isActiveNavItem($item) ? 'aria-current="page"' : '' ?>>
                    <?= e($item['label']) ?>
                    <?php if ($item['path'] === '/notifications' && $notificationPollingEnabled): ?>
                        <span class="nav-count" data-notification-count<?= $notificationCount === 0 ? ' hidden' : '' ?>><?= $notificationCount ?></span>
                    <?php endif; ?>
                    <?php if ($item['path'] === '/admin/messages' && $unreadMessages > 0): ?>
                        <span class="nav-count"><?= $unreadMessages ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
            <?php if ($moreNav): ?>
                <details class="dashboard-more">
                    <summary class="dashboard-nav-link <?= $moreNavIsActive ? 'active' : '' ?>">
                        More <?= icon('chevron-down') ?>
                    </summary>
                    <div class="dashboard-more-menu">
                        <?php foreach ($moreNav as $item): ?>
                            <a class="<?= $isActiveNavItem($item) ? 'active' : '' ?>"
                               href="<?= e(url($item['path'])) ?>"
                               <?= $isActiveNavItem($item) ? 'aria-current="page"' : '' ?>>
                                <?= e($item['label']) ?>
                                <?php if ($item['path'] === '/notifications' && $notificationPollingEnabled): ?>
                                    <span class="nav-count" data-notification-count<?= $notificationCount === 0 ? ' hidden' : '' ?>><?= $notificationCount ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endif; ?>
        </nav>

        <div class="dashboard-actions">
            <?php if ($role === 'customer'): ?>
                <a class="btn btn-primary dashboard-new-request" href="<?= e(url('/customer/requests/new')) ?>">
                    <?= icon('plus') ?><span>New request</span>
                </a>
            <?php endif; ?>
            <?php if ($notificationPollingEnabled): ?>
                <a class="icon-btn topbar-notifications"
                   href="<?= e(url('/notifications')) ?>"
                   aria-label="<?= $notificationCount ?> unread notifications"
                   title="Notifications"
                   data-notification-poll
                   data-notification-url="<?= e(url('/notifications/poll')) ?>"
                   data-notification-target="<?= e(url('/notifications')) ?>"
                   data-last-notification-id="<?= $lastNotificationId ?>">
                    <?= icon('bell') ?>
                    <span class="notification-count" data-notification-count<?= $notificationCount === 0 ? ' hidden' : '' ?>><?= $notificationCount ?></span>
                </a>
                <span class="sr-only" aria-live="polite" data-notification-status></span>
            <?php endif; ?>
            <details class="user-menu dashboard-user-menu">
                <summary aria-label="Account menu for <?= e($currentUser['name'] ?? 'User') ?>">
                    <span class="avatar avatar-sm" style="background: <?= e($currentUser['avatar_color'] ?? '#2563eb') ?>"><?= e(initials($currentUser['name'] ?? 'U')) ?></span>
                    <span class="user-menu-text">
                        <strong><?= e($currentUser['name'] ?? 'User') ?></strong>
                        <small><?= e($roleLabel) ?></small>
                    </span>
                    <?= icon('chevron-down') ?>
                </summary>
                <div class="user-menu-panel">
                    <a href="<?= e(url('/profile')) ?>"><?= icon('user') ?> My profile</a>
                    <?php if ($role === 'customer'): ?>
                        <a href="<?= e(url('/profile#saved-addresses')) ?>"><?= icon('pin') ?> Saved addresses</a>
                    <?php endif; ?>
                    <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener"><?= icon('external') ?> View website</a>
                    <div class="user-menu-divider"></div>
                    <a href="<?= e(url('/logout')) ?>" class="danger-text"><?= icon('logout') ?> Sign out</a>
                </div>
            </details>
        </div>
    </header>

    <div class="app-main dashboard-main">
        @include('partials.flash')
        <?php if ($notificationPollingEnabled): ?>
            <div class="toast-stack" role="status" aria-live="polite" aria-atomic="false" data-notification-toasts></div>
        <?php endif; ?>

        <main class="app-content dashboard-content">
            @yield('content')
        </main>
    </div>
</div>

<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
