<section class="page-hero page-hero-compact">
    <div class="container page-hero-inner">
        <p class="eyebrow">Contact</p>
        <h1>Tell us what needs fixing</h1>
        <p>Send a message for quotes, scheduling questions, commercial inquiries, or anything else.</p>
    </div>
</section>

<section class="section">
    <div class="container contact-grid">
        <div class="contact-info">
            <div class="contact-card">
                <span class="feature-icon"><?= icon('phone') ?></span>
                <div>
                    <h3>Call or text</h3>
                    <p><?= e(setting('contact_phone')) ?></p>
                    <small><?= e(setting('business_hours')) ?></small>
                </div>
            </div>
            <div class="contact-card">
                <span class="feature-icon"><?= icon('mail') ?></span>
                <div>
                    <h3>Email</h3>
                    <p><?= e(setting('contact_email')) ?></p>
                    <small>We reply within one business day.</small>
                </div>
            </div>
            <div class="contact-card">
                <span class="feature-icon"><?= icon('pin') ?></span>
                <div>
                    <h3>Service area</h3>
                    <p><?= e(setting('contact_address')) ?></p>
                    <small>Serving nearby cities across Metro Manila.</small>
                </div>
            </div>

            <div class="contact-note">
                <h3>Already booked?</h3>
                <p>Track your technician and service report from your dashboard.</p>
                <a class="btn btn-soft btn-sm" href="<?= e(url(Auth::check() ? dashboard_url() : '/login')) ?>">Open dashboard</a>
            </div>
        </div>

        <div class="form-card">
            <h2>Send a message</h2>
            <p class="form-intro">Fields marked with an asterisk are required.</p>

            <form method="post" action="<?= e(url('/contact')) ?>" class="form">
                <?= csrf_field() ?>
                <div class="form-row">
                    <div class="field">
                        <label for="name">Full name *</label>
                        <input type="text" id="name" name="name" value="<?= e(old('name')) ?>" class="<?= isset($errors['name']) ? 'is-invalid' : '' ?>" required>
                        <?php if (isset($errors['name'])) { ?><span class="field-error"><?= e($errors['name']) ?></span><?php } ?>
                    </div>
                    <div class="field">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" class="<?= isset($errors['email']) ? 'is-invalid' : '' ?>" required>
                        <?php if (isset($errors['email'])) { ?><span class="field-error"><?= e($errors['email']) ?></span><?php } ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="field">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" value="<?= e(old('phone')) ?>" placeholder="+63 9XX XXX XXXX">
                    </div>
                    <div class="field">
                        <label for="subject">Subject *</label>
                        <input type="text" id="subject" name="subject" value="<?= e(old('subject')) ?>" class="<?= isset($errors['subject']) ? 'is-invalid' : '' ?>" required>
                        <?php if (isset($errors['subject'])) { ?><span class="field-error"><?= e($errors['subject']) ?></span><?php } ?>
                    </div>
                </div>

                <div class="field">
                    <label for="message">Message *</label>
                    <textarea id="message" name="message" rows="6" class="<?= isset($errors['message']) ? 'is-invalid' : '' ?>" required><?= e(old('message')) ?></textarea>
                    <?php if (isset($errors['message'])) { ?><span class="field-error"><?= e($errors['message']) ?></span><?php } ?>
                </div>

                <button type="submit" class="btn btn-primary"><?= icon('send') ?> Send message</button>
            </form>
        </div>
    </div>
</section>
