<?php
$brandColor = (string) setting('brand_color', '#2563eb');
$accentColor = (string) setting('accent_color', '#0ea5e9');

if (preg_match('/\A#[0-9a-fA-F]{6}\z/', $brandColor) !== 1) {
    $brandColor = '#2563eb';
}

if (preg_match('/\A#[0-9a-fA-F]{6}\z/', $accentColor) !== 1) {
    $accentColor = '#0ea5e9';
}

$fonts = config('branding.fonts');
$font = $fonts[setting('site_font', 'system')] ?? $fonts['system'];
?>
<style>
    :root {
        --brand: <?= $brandColor ?>;
        --brand-dark: color-mix(in srgb, var(--brand) 82%, #000);
        --brand-soft: color-mix(in srgb, var(--brand) 8%, #fff);
        --brand-line: color-mix(in srgb, var(--brand) 28%, #fff);
        --accent: <?= $accentColor ?>;
        --font: <?= $font['stack'] ?>;
    }
</style>
