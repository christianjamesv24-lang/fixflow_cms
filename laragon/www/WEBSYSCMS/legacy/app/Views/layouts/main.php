<?php
$siteName = setting('site_name', 'FixFlow');
$currentUser = Auth::user();
$footerPages = Database::all("SELECT title, slug FROM cms_pages WHERE status = 'published' AND show_in_footer = 1 ORDER BY title");
$footerServices = Database::all("SELECT name, slug FROM service_categories WHERE status = 'active' ORDER BY name LIMIT 5");
$flashMessages = Flash::all();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e(setting('site_tagline', 'Appliance repair and maintenance, organized in one place.')) ?>">
    <title><?= e($title ?? $siteName) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('img/favicon.svg')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="public-body">
<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header" data-header>
    <div class="container header-inner">
        <a class="brand" href="<?= e(url('/')) ?>">
            <span class="brand-mark"><?= icon('wrench') ?></span>
            <span class="brand-text"><?= e($siteName) ?><small>Appliance care</small></span>
        </a>

        <nav class="site-nav" id="siteNav" data-nav>
            <a class="<?= active_nav('/') ?>" href="<?= e(url('/')) ?>">Home</a>
            <a class="<?= active_nav('/services') ?>" href="<?= e(url('/services')) ?>">Services</a>
            <a class="<?= active_nav('/about') ?>" href="<?= e(url('/about')) ?>">About</a>
            <a class="<?= active_nav('/contact') ?>" href="<?= e(url('/contact')) ?>">Contact</a>
            <div class="nav-mobile-actions">
                <?php if ($currentUser) { ?>
                    <a class="btn btn-soft" href="<?= e(url(dashboard_url())) ?>">Dashboard</a>
                <?php } else { ?>
                    <a class="btn btn-ghost" href="<?= e(url('/login')) ?>">Sign in</a>
                    <a class="btn btn-primary" href="<?= e(url('/register')) ?>">Create account</a>
                <?php } ?>
            </div>
        </nav>

        <div class="header-actions">
            <?php if ($currentUser) { ?>
                <a class="btn btn-soft btn-sm" href="<?= e(url(dashboard_url())) ?>"><?= icon('grid') ?> Dashboard</a>
            <?php } else { ?>
                <a class="btn btn-ghost btn-sm" href="<?= e(url('/login')) ?>">Sign in</a>
                <a class="btn btn-primary btn-sm" href="<?= e(url('/register')) ?>">Book a repair</a>
            <?php } ?>
            <button class="nav-toggle" type="button" data-nav-toggle aria-label="Toggle menu" aria-expanded="false"><?= icon('menu') ?></button>
        </div>
    </div>
</header>

<?php require BASE_PATH.'/app/Views/partials/flash.php'; ?>

<main id="main">
    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a class="brand brand-light" href="<?= e(url('/')) ?>">
                <span class="brand-mark"><?= icon('wrench') ?></span>
                <span class="brand-text"><?= e($siteName) ?><small>Appliance care</small></span>
            </a>
            <p><?= e(setting('footer_note', 'Careful diagnostics, transparent pricing, and technicians who show up.')) ?></p>
            <div class="footer-contact">
                <span><?= icon('phone') ?> <?= e(setting('contact_phone')) ?></span>
                <span><?= icon('mail') ?> <?= e(setting('contact_email')) ?></span>
            </div>
        </div>

        <div class="footer-col">
            <h3>Services</h3>
            <ul>
                <?php foreach ($footerServices as $service) { ?>
                    <li><a href="<?= e(url('/services/'.$service['slug'])) ?>"><?= e($service['name']) ?></a></li>
                <?php } ?>
            </ul>
        </div>

        <div class="footer-col">
            <h3>Company</h3>
            <ul>
                <li><a href="<?= e(url('/about')) ?>">About us</a></li>
                <li><a href="<?= e(url('/contact')) ?>">Contact</a></li>
                <li><a href="<?= e(url('/services')) ?>">All services</a></li>
                <?php foreach ($footerPages as $page) { ?>
                    <li><a href="<?= e(url('/p/'.$page['slug'])) ?>"><?= e($page['title']) ?></a></li>
                <?php } ?>
            </ul>
        </div>

        <div class="footer-col">
            <h3>Service hours</h3>
            <p class="footer-hours"><?= e(setting('business_hours')) ?></p>
            <p class="footer-muted"><?= e(setting('support_hours')) ?></p>
            <p class="footer-address"><?= icon('pin') ?> <?= e(setting('contact_address')) ?></p>
            <?php if (! empty($currentUser) === false) { ?>
                <a class="btn btn-primary btn-sm footer-cta" href="<?= e(url('/register')) ?>">Create an account</a>
            <?php } ?>
        </div>
    </div>

    <div class="container footer-bottom">
        <p>&copy; <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved.</p>
        <p class="footer-legal">Service desk CMS · Built for organized repairs.</p>
    </div>
</footer>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
