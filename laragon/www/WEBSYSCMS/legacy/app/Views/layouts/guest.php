<?php
$siteName = setting('site_name', 'FixFlow');
$flashMessages = Flash::all();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? $siteName) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('img/favicon.svg')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="auth-body">
<?php require BASE_PATH.'/app/Views/partials/flash.php'; ?>

<div class="auth-shell">
    <aside class="auth-aside">
        <a class="brand brand-light" href="<?= e(url('/')) ?>">
            <span class="brand-mark"><?= icon('wrench') ?></span>
            <span class="brand-text"><?= e($siteName) ?><small>Appliance care</small></span>
        </a>

        <div class="auth-aside-content">
            <p class="eyebrow eyebrow-light">Service desk</p>
            <h1>One organized place for every appliance fix.</h1>
            <p>Report the problem, track your technician, review the service report, and keep your repair history tidy.</p>

            <ul class="auth-points">
                <li><?= icon('check-circle') ?> Live ticket tracking from request to completion</li>
                <li><?= icon('check-circle') ?> Certified technicians matched to each appliance</li>
                <li><?= icon('check-circle') ?> Transparent estimates and written service reports</li>
            </ul>
        </div>

        <div class="auth-aside-footer">
            <span><?= icon('phone') ?> <?= e(setting('contact_phone')) ?></span>
            <span><?= icon('mail') ?> <?= e(setting('contact_email')) ?></span>
        </div>
    </aside>

    <main class="auth-main">
        <a class="auth-back" href="<?= e(url('/')) ?>"><?= icon('arrow-left') ?> Back to website</a>
        <div class="auth-card">
            <?= $content ?>
        </div>
        <p class="auth-footnote">Need help? Call <?= e(setting('contact_phone')) ?> · <?= e(setting('business_hours')) ?></p>
    </main>
</div>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
