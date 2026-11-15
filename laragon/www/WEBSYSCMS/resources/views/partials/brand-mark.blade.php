<?php
$siteLogo = (string) setting('site_logo', '');

if (preg_match('/\Auploads\/branding\/[a-f0-9]{32}\.(?:jpe?g|png|webp)\z/i', $siteLogo) !== 1
    || ! is_file(public_path($siteLogo))) {
    $siteLogo = '';
}
?>
<?php if ($siteLogo !== ''): ?>
    <img class="brand-mark-image" src="<?= e(asset($siteLogo)) ?>" alt="">
<?php else: ?>
    <span class="brand-mark"><?= icon('wrench') ?></span>
<?php endif; ?>
