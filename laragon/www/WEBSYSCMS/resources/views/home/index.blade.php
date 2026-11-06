@extends('layouts.main')

@section('content')
<section class="hero">
    <div class="container hero-grid">
        <div class="hero-copy">
            <p class="eyebrow">Appliance repair &amp; maintenance</p>
            <h1><?= e(setting('hero_title', 'Appliance trouble? Consider it handled.')) ?></h1>
            <p class="hero-sub"><?= e(setting('hero_subtitle', 'Report the problem, get matched with a certified technician, and track every step of the repair.')) ?></p>

            <div class="hero-actions">
                <a class="btn btn-primary btn-lg" href="<?= e(url(Auth::check() ? '/customer/requests/new' : '/register')) ?>">
                    <?= icon('plus') ?> Book a repair
                </a>
                <?php if (Auth::check()): ?>
                    <a class="btn btn-ghost btn-lg" href="<?= e(url(dashboard_url())) ?>">Track my requests <?= icon('arrow-right') ?></a>
                <?php else: ?>
                    <a class="btn btn-ghost btn-lg" href="<?= e(url('/login')) ?>">Track a request <?= icon('arrow-right') ?></a>
                <?php endif; ?>
            </div>

            <ul class="hero-points">
                <li><?= icon('check-circle') ?> Certified, background-checked technicians</li>
                <li><?= icon('check-circle') ?> Written service report for every visit</li>
                <li><?= icon('check-circle') ?> Estimates approved before work starts</li>
            </ul>
        </div>

        <div class="hero-visual">
            <div class="hero-card">
                <div class="hero-card-top">
                    <div>
                        <span class="hero-card-label">Service ticket</span>
                        <strong>#SR-2026-0184</strong>
                    </div>
                    <span class="badge badge-brand">In progress</span>
                </div>

                <div class="hero-appliance">
                    <span class="hero-appliance-icon"><?= icon('fridge') ?></span>
                    <div>
                        <strong>Refrigerator not cooling</strong>
                        <small>Inverter unit · Diagnosing compressor</small>
                    </div>
                </div>

                <div class="track-steps">
                    <div class="track-step done">
                        <span class="track-dot"><?= icon('check') ?></span>
                        <div><strong>Request received</strong><small>Ticket created instantly</small></div>
                    </div>
                    <div class="track-step done">
                        <span class="track-dot"><?= icon('check') ?></span>
                        <div><strong>Technician assigned</strong><small>Grace V. · Refrigeration</small></div>
                    </div>
                    <div class="track-step current">
                        <span class="track-dot"><?= icon('wrench') ?></span>
                        <div><strong>Repair in progress</strong><small>On-site diagnosis started</small></div>
                    </div>
                    <div class="track-step">
                        <span class="track-dot"><?= icon('file') ?></span>
                        <div><strong>Service report</strong><small>Sign-off and feedback</small></div>
                    </div>
                </div>

                <div class="hero-card-foot">
                    <span><?= icon('clock') ?> Estimated 2 hours</span>
                    <span class="hero-card-tech">
                        <span class="avatar avatar-xs">GV</span> Grace Villanueva
                    </span>
                </div>
            </div>

            <div class="hero-float">
                <span class="hero-float-icon"><?= icon('star') ?></span>
                <div>
                    <strong><?= $stats['rating'] > 0 ? number_format($stats['rating'], 1) . ' / 5' : 'Rated 5.0' ?></strong>
                    <small>Average customer rating</small>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="stat-strip">
            <div class="stat-strip-item">
                <strong><?= number_format($stats['completed']) ?>+</strong>
                <span>Repairs completed</span>
            </div>
            <div class="stat-strip-item">
                <strong><?= number_format($stats['technicians']) ?></strong>
                <span>Certified technicians</span>
            </div>
            <div class="stat-strip-item">
                <strong><?= number_format($stats['customers']) ?>+</strong>
                <span>Households served</span>
            </div>
            <div class="stat-strip-item">
                <strong><?= $stats['rating'] > 0 ? number_format($stats['rating'], 1) : '5.0' ?></strong>
                <span>Average rating</span>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">What we fix</p>
                <h2>One service desk for every major appliance</h2>
            </div>
            <a class="link-arrow" href="<?= e(url('/services')) ?>">View all services <?= icon('arrow-right') ?></a>
        </div>

        <div class="service-grid">
            <?php foreach ($categories as $category): ?>
                <a class="service-card" href="<?= e(url('/services/' . $category['slug'])) ?>">
                    <span class="service-icon"><?= icon($category['icon']) ?></span>
                    <h3><?= e($category['name']) ?></h3>
                    <p><?= e($category['short_description']) ?></p>
                    <span class="service-card-foot">
                        <span>From <?= e(money($category['base_price'])) ?></span>
                        <?= icon('arrow-right') ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-muted">
    <div class="container">
        <div class="section-head section-head-center">
            <div>
                <p class="eyebrow">How it works</p>
                <h2>From problem to signed-off repair in four steps</h2>
            </div>
        </div>

        <div class="steps-grid">
            <div class="step-card">
                <span class="step-number">01</span>
                <h3>Describe the issue</h3>
                <p>Pick the appliance, tell us what is happening, and choose a preferred visit window.</p>
            </div>
            <div class="step-card">
                <span class="step-number">02</span>
                <h3>Get matched</h3>
                <p>Our coordinator assigns a certified technician for your appliance type and location.</p>
            </div>
            <div class="step-card">
                <span class="step-number">03</span>
                <h3>Track the visit</h3>
                <p>Follow the ticket in your dashboard from assignment to on-site diagnosis.</p>
            </div>
            <div class="step-card">
                <span class="step-number">04</span>
                <h3>Review the report</h3>
                <p>See the diagnosis, parts used, and final cost — then rate the service.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container feature-grid">
        <div class="feature-copy">
            <p class="eyebrow">Why FixitNow</p>
            <h2>Repair service that stays organized</h2>
            <p>No more lost receipts or uncertain arrival windows. Every request has a ticket number, a status history, and a written report your household can refer back to.</p>

            <div class="feature-list">
                <div class="feature-item">
                    <span class="feature-icon"><?= icon('clipboard') ?></span>
                    <div>
                        <h3>Trackable tickets</h3>
                        <p>Every request gets a ticket number and a live status timeline.</p>
                    </div>
                </div>
                <div class="feature-item">
                    <span class="feature-icon"><?= icon('users') ?></span>
                    <div>
                        <h3>Matched technicians</h3>
                        <p>Assignments are based on appliance specialty, not whoever is free.</p>
                    </div>
                </div>
                <div class="feature-item">
                    <span class="feature-icon"><?= icon('shield') ?></span>
                    <div>
                        <h3>Transparent pricing</h3>
                        <p>Estimates are recorded before work starts and final costs are documented.</p>
                    </div>
                </div>
                <div class="feature-item">
                    <span class="feature-icon"><?= icon('file') ?></span>
                    <div>
                        <h3>Written service reports</h3>
                        <p>Diagnosis, work performed, parts used, and recommendations — saved for you.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="feature-panel">
            <div class="panel-head">
                <span class="panel-kicker">Service desk snapshot</span>
                <strong>Built for both sides of the visit</strong>
            </div>
            <div class="panel-row">
                <span class="panel-icon"><?= icon('user') ?></span>
                <div><strong>Customers</strong><small>Book, track, cancel, and review</small></div>
            </div>
            <div class="panel-row">
                <span class="panel-icon"><?= icon('wrench') ?></span>
                <div><strong>Technicians</strong><small>Accept jobs, update status, file reports</small></div>
            </div>
            <div class="panel-row">
                <span class="panel-icon"><?= icon('chart') ?></span>
                <div><strong>Administrators</strong><small>Assign, schedule, report, and manage content</small></div>
            </div>
            <div class="panel-quote">
                <?= icon('star') ?>
                <p>"The ticket history alone saves us so many phone calls."</p>
                <span>Operations team, FixitNow</span>
            </div>
        </div>
    </div>
</section>

<?php if ($testimonials): ?>
<section class="section section-muted">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Customer feedback</p>
                <h2>Visits worth writing home about</h2>
            </div>
            <span class="rating-summary">
                <?= rating_stars($stats['rating'] ?: 5) ?>
                <small><?= $stats['rating'] > 0 ? number_format($stats['rating'], 1) . ' average' : 'New service desk' ?></small>
            </span>
        </div>

        <div class="testimonial-grid">
            <?php foreach ($testimonials as $testimonial): ?>
                <figure class="testimonial">
                    <?= rating_stars($testimonial['rating']) ?>
                    <blockquote><?= e($testimonial['comment']) ?></blockquote>
                    <figcaption>
                        <span class="avatar avatar-xs"><?= e(initials($testimonial['customer_name'])) ?></span>
                        <span>
                            <strong><?= e($testimonial['customer_name']) ?></strong>
                            <small>Ticket <?= e($testimonial['ticket_no']) ?></small>
                        </span>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="cta-band">
    <div class="container cta-inner">
        <div>
            <h2>Ready to get that appliance fixed?</h2>
            <p>Create an account, submit a request, and we will take it from there.</p>
        </div>
        <div class="cta-actions">
            <a class="btn btn-light btn-lg" href="<?= e(url(Auth::check() ? '/customer/requests/new' : '/register')) ?>">Book a repair</a>
            <a class="btn btn-outline-light btn-lg" href="<?= e(url('/contact')) ?>">Talk to us</a>
        </div>
    </div>
</section>

@endsection
