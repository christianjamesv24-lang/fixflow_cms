<section class="page-hero page-hero-compact">
    <div class="container page-hero-inner">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(url('/')) ?>">Home</a>
            <?= icon('chevron-right') ?>
            <a href="<?= e(url('/services')) ?>">Services</a>
            <?= icon('chevron-right') ?>
            <span><?= e($category['name']) ?></span>
        </nav>
        <div class="service-detail-head">
            <span class="service-icon service-icon-lg"><?= icon($category['icon']) ?></span>
            <div>
                <p class="eyebrow">Service category</p>
                <h1><?= e($category['name']) ?> repair &amp; maintenance</h1>
                <p><?= e($category['short_description']) ?></p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container detail-grid">
        <article class="detail-body">
            <div class="prose">
                <?= $category['description'] ?>
            </div>

            <div class="detail-highlights">
                <div class="highlight">
                    <span class="feature-icon"><?= icon('clipboard') ?></span>
                    <h3>Clear diagnosis</h3>
                    <p>Your technician confirms the fault and walks you through the estimate before work starts.</p>
                </div>
                <div class="highlight">
                    <span class="feature-icon"><?= icon('shield') ?></span>
                    <h3>Safety checked</h3>
                    <p>Every completed repair is tested and documented in a written service report.</p>
                </div>
                <div class="highlight">
                    <span class="feature-icon"><?= icon('clock') ?></span>
                    <h3>Tracked visit</h3>
                    <p>Follow the ticket status from assignment to completion in your dashboard.</p>
                </div>
            </div>

            <div class="process-list">
                <h2>What happens when you book</h2>
                <ol>
                    <li><strong>Submit the request.</strong> Tell us the appliance brand, model, and the symptoms you have noticed.</li>
                    <li><strong>Get scheduled.</strong> A coordinator assigns a technician and publishes your appointment window.</li>
                    <li><strong>Repair visit.</strong> The technician diagnoses the issue, confirms costs, and completes the work.</li>
                    <li><strong>Review the report.</strong> Your dashboard keeps the service report, parts used, and final cost.</li>
                </ol>
            </div>
        </article>

        <aside class="detail-aside">
            <div class="booking-card">
                <span class="badge badge-brand">Now accepting requests</span>
                <h2>Book <?= e($category['name']) ?> service</h2>
                <p>Base visit from <strong><?= e(money($category['base_price'])) ?></strong>. Final cost depends on diagnosis and parts.</p>

                <ul class="booking-points">
                    <li><?= icon('check') ?> <?= number_format($completed) ?> completed in this category</li>
                    <li><?= icon('check') ?> Certified technician assignment</li>
                    <li><?= icon('check') ?> Estimate approved before work</li>
                    <li><?= icon('check') ?> Service report and feedback included</li>
                </ul>

                <a class="btn btn-primary btn-block" href="<?= e(url(Auth::check() ? '/customer/requests/new?category='.$category['slug'] : '/register')) ?>">
                    <?= icon('plus') ?> Start a service request
                </a>
                <a class="btn btn-ghost btn-block" href="<?= e(url('/contact')) ?>">Ask a question</a>

                <p class="booking-note"><?= icon('phone') ?> Prefer to talk? <?= e(setting('contact_phone')) ?></p>
            </div>
        </aside>
    </div>
</section>

<?php if ($related) { ?>
<section class="section section-muted">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Also available</p>
                <h2>Other services</h2>
            </div>
            <a class="link-arrow" href="<?= e(url('/services')) ?>">All services <?= icon('arrow-right') ?></a>
        </div>
        <div class="service-grid">
            <?php foreach ($related as $item) { ?>
                <a class="service-card" href="<?= e(url('/services/'.$item['slug'])) ?>">
                    <span class="service-icon"><?= icon($item['icon']) ?></span>
                    <h3><?= e($item['name']) ?></h3>
                    <p><?= e($item['short_description']) ?></p>
                    <span class="service-card-foot"><span>From <?= e(money($item['base_price'])) ?></span><?= icon('arrow-right') ?></span>
                </a>
            <?php } ?>
        </div>
    </div>
</section>
<?php } ?>
