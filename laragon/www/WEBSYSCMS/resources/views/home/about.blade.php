@extends('layouts.main')

@section('content')
<section class="page-hero">
    <div class="container page-hero-inner">
        <p class="eyebrow">About us</p>
        <h1><?= e($page['title'] ?? 'Service that respects your time') ?></h1>
        <p><?= e($page['excerpt'] ?? setting('about_intro')) ?></p>
    </div>
</section>

<section class="section">
    <div class="container about-grid">
        <div class="prose prose-lg">
            <?php if ($page): ?>
                <?= $page['content'] ?>
            <?php else: ?>
                <h2>A service desk built around the visit</h2>
                <p><?= e(setting('about_intro')) ?></p>
                <p>Every request is logged, assigned, scheduled, and closed out with a report. Customers can track progress, technicians get complete job details, and coordinators can see the entire service desk in one place.</p>
            <?php endif; ?>
        </div>

        <aside class="about-aside">
            <div class="about-card">
                <span class="feature-icon"><?= icon('pin') ?></span>
                <h3>Where we work</h3>
                <p><?= e(setting('contact_address')) ?></p>
            </div>
            <div class="about-card">
                <span class="feature-icon"><?= icon('clock') ?></span>
                <h3>Service hours</h3>
                <p><?= e(setting('business_hours')) ?></p>
                <?php if (setting('support_hours') !== '' && setting('support_hours') !== setting('business_hours')): ?>
                    <small><?= e(setting('support_hours')) ?></small>
                <?php endif; ?>
            </div>
            <div class="about-card">
                <span class="feature-icon"><?= icon('phone') ?></span>
                <h3>Talk to the team</h3>
                <p><?= e(setting('contact_phone')) ?><br><?= e(setting('contact_email')) ?></p>
            </div>
        </aside>
    </div>
</section>

<section class="cta-band">
    <div class="container cta-inner">
        <div>
            <h2>Let us take care of the next repair</h2>
            <p>Create an account or send us a message and we will get you scheduled.</p>
        </div>
        <div class="cta-actions">
            <a class="btn btn-light btn-lg" href="<?= e(url(Auth::check() ? '/customer/requests/new' : '/register')) ?>">Create account</a>
            <a class="btn btn-outline-light btn-lg" href="<?= e(url('/contact')) ?>">Contact us</a>
        </div>
    </div>
</section>

@endsection
