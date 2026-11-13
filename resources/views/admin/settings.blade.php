@extends('layouts.app')

@section('content')
<?php
$siteName = setting('site_name', 'FixitNow');
$siteTagline = setting('site_tagline', 'Appliance repair &amp; maintenance made simple');
$siteLogo = setting('site_logo', '');
?>
<div class="page-head">
    <div>
        <p class="eyebrow">System</p>
        <h1>Settings</h1>
        <p>Update website details, contact info, and branding. Changes appear on public pages.</p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-ghost" href="<?= e(url('/')) ?>" target="_blank" rel="noopener"><?= icon('external') ?> View site</a>
    </div>
</div>

<form method="post" action="<?= e(url('/admin/settings')) ?>" class="form-card" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="panel-head" style="padding:24px 28px 0;border:none">
        <div>
            <h2 class="panel-title">General</h2>
            <p class="panel-sub">Site identity and defaults.</p>
        </div>
    </div>

    <div class="form" style="padding-top:14px">
        <div class="form-row">
            <div class="field">
                <label for="site_name">Site name *</label>
                <input type="text" id="site_name" name="site_name" value="<?= e(setting('site_name', 'FixitNow')) ?>" class="<?= isset($errors['site_name']) ? 'is-invalid' : '' ?>" required>
                <?php if (isset($errors['site_name'])): ?><span class="field-error"><?= e($errors['site_name']) ?></span><?php endif; ?>
            </div>
            <div class="field">
                <label for="site_tagline">Tagline</label>
                <input type="text" id="site_tagline" name="site_tagline" value="<?= e(setting('site_tagline', 'Appliance repair, organized.')) ?>">
            </div>
        </div>

        <div class="panel-head" style="padding:10px 0 0;border:none">
            <div>
                <h2 class="panel-title">Branding and theme</h2>
                <p class="panel-sub">Customize the logo, colors, and font used across the website and portals.</p>
            </div>
        </div>

        <div class="form-row">
            <div class="field">
                <label for="brand_color">Primary color</label>
                <input type="color" id="brand_color" name="brand_color" value="<?= e(setting('brand_color', '#2563eb')) ?>" class="<?= isset($errors['brand_color']) ? 'is-invalid' : '' ?>">
                <?php if (isset($errors['brand_color'])): ?><span class="field-error"><?= e($errors['brand_color']) ?></span><?php endif; ?>
            </div>
            <div class="field">
                <label for="accent_color">Accent color</label>
                <input type="color" id="accent_color" name="accent_color" value="<?= e(setting('accent_color', '#0ea5e9')) ?>" class="<?= isset($errors['accent_color']) ? 'is-invalid' : '' ?>">
                <?php if (isset($errors['accent_color'])): ?><span class="field-error"><?= e($errors['accent_color']) ?></span><?php endif; ?>
            </div>
        </div>

        <div class="field">
            <label for="site_font">Website font</label>
            <select id="site_font" name="site_font" class="<?= isset($errors['site_font']) ? 'is-invalid' : '' ?>">
                <?php foreach (config('branding.fonts') as $fontKey => $font): ?>
                    <option value="<?= e($fontKey) ?>" <?= setting('site_font', 'system') === $fontKey ? 'selected' : '' ?>><?= e($font['label']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['site_font'])): ?><span class="field-error"><?= e($errors['site_font']) ?></span><?php endif; ?>
        </div>

        <div class="field">
            <label for="site_logo">Logo (JPEG, PNG, or WebP; up to 2 MB)</label>
            <input type="file" id="site_logo" name="site_logo" accept="image/jpeg,image/png,image/webp" class="<?= isset($errors['site_logo']) ? 'is-invalid' : '' ?>">
            <?php if (isset($errors['site_logo'])): ?><span class="field-error"><?= e($errors['site_logo']) ?></span><?php endif; ?>
            <?php if ($siteLogo !== ''): ?>
                <div style="margin-top:12px">
                    <img src="<?= e(asset($siteLogo)) ?>" alt="Current site logo" class="brand-mark-image">
                    <label style="display:block;margin-top:8px">
                        <input type="checkbox" name="remove_site_logo" value="1">
                        Remove current logo
                    </label>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <div class="field">
                <label for="currency_symbol">Currency symbol</label>
                <input type="text" id="currency_symbol" name="currency_symbol" value="<?= e(setting('currency_symbol', '₱')) ?>">
            </div>
            <div class="field">
                <label for="business_hours">Business hours</label>
                <input type="text" id="business_hours" name="business_hours" value="<?= e(setting('business_hours', 'Mon - Sat: 8:00 AM - 6:00 PM')) ?>">
            </div>
        </div>

        <div class="panel-head" style="padding:10px 0 0;border:none">
            <div>
                <h2 class="panel-title">Contact</h2>
                <p class="panel-sub">Used in the header, footer, and contact page.</p>
            </div>
        </div>

        <div class="form-row">
            <div class="field">
                <label for="contact_email">Contact email *</label>
                <input type="email" id="contact_email" name="contact_email" value="<?= e(setting('contact_email', 'support@fixflow.test')) ?>" class="<?= isset($errors['contact_email']) ? 'is-invalid' : '' ?>" required>
                <?php if (isset($errors['contact_email'])): ?><span class="field-error"><?= e($errors['contact_email']) ?></span><?php endif; ?>
            </div>
            <div class="field">
                <label for="contact_phone">Phone number</label>
                <input type="text" id="contact_phone" name="contact_phone" value="<?= e(setting('contact_phone', '+63 2 1234 5678')) ?>">
            </div>
        </div>

        <div class="field">
            <label for="contact_address">Address</label>
            <input type="text" id="contact_address" name="contact_address" value="<?= e(setting('contact_address', 'Mindoro State Main Campus, Alcate, Victoria, Oriental Mindoro')) ?>">
        </div>

        <div class="field">
            <label for="support_hours">Support hours</label>
            <input type="text" id="support_hours" name="support_hours" value="<?= e(setting('support_hours', 'Daily: 8:00 AM - 8:00 PM')) ?>">
        </div>

        <div class="panel-head" style="padding:10px 0 0;border:none">
            <div>
                <h2 class="panel-title">Homepage</h2>
                <p class="panel-sub">Hero section content.</p>
            </div>
        </div>

        <div class="field">
            <label for="hero_title">Hero title</label>
            <input type="text" id="hero_title" name="hero_title" value="<?= e(setting('hero_title', 'Appliance trouble? Consider it handled.')) ?>">
        </div>

        <div class="field">
            <label for="hero_subtitle">Hero subtitle</label>
            <textarea id="hero_subtitle" name="hero_subtitle" rows="3"><?= e(setting('hero_subtitle', 'Report the problem, get matched with a certified technician, and track every step of the repair from request to signed-off service report.')) ?></textarea>
        </div>

        <div class="field">
            <label for="about_intro">About intro</label>
            <textarea id="about_intro" name="about_intro" rows="3"><?= e(setting('about_intro', '')) ?></textarea>
        </div>

        <div class="panel-head" style="padding:10px 0 0;border:none">
            <div>
                <h2 class="panel-title">Social</h2>
                <p class="panel-sub">Links for the website footer.</p>
            </div>
        </div>

        <div class="form-row">
            <div class="field">
                <label for="facebook_url">Facebook URL</label>
                <input type="url" id="facebook_url" name="facebook_url" value="<?= e(setting('facebook_url', '')) ?>" placeholder="https://facebook.com/...">
            </div>
            <div class="field">
                <label for="instagram_url">Instagram URL</label>
                <input type="url" id="instagram_url" name="instagram_url" value="<?= e(setting('instagram_url', '')) ?>" placeholder="https://instagram.com/...">
            </div>
        </div>

        <div class="panel-head" style="padding:10px 0 0;border:none">
            <div>
                <h2 class="panel-title">Footer</h2>
                <p class="panel-sub">Notes shown at the bottom of the site.</p>
            </div>
        </div>

        <div class="field">
            <label for="footer_note">Footer note</label>
            <input type="text" id="footer_note" name="footer_note" value="<?= e(setting('footer_note', '© ' . date('Y') . ' FixitNow. All rights reserved.')) ?>">
        </div>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= icon('check') ?> Save settings</button>
        </div>
    </div>
</form>

@endsection
