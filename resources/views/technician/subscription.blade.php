@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">Technician account</p>
        <h1>Subscription</h1>
        <p>Pay by GCash and submit the transaction reference. The administrator verifies each payment manually.</p>
    </div>
</div>

<?php if ($subscription): ?>
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2 class="panel-title">Your plan is active</h2>
                <p class="panel-sub"><?= e(ucfirst($subscription->plan)) ?> plan · access through <?= e(format_datetime($subscription->current_period_end)) ?></p>
            </div>
            <?= status_badge('active') ?>
        </div>
        <div class="panel-body">
            <p>Your technician work pages are available while this subscription is active.</p>
        </div>
    </section>
<?php endif; ?>

<?php if ($pendingPayment): ?>
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2 class="panel-title">Payment awaiting review</h2>
                <p class="panel-sub"><?= e(ucfirst($pendingPayment->plan)) ?> plan · GCash reference <?= e($pendingPayment->gcash_reference) ?></p>
            </div>
            <?= status_badge('pending') ?>
        </div>
        <div class="panel-body">
            <p>The administrator will check the transfer in GCash. You can continue using technician features after approval.</p>
        </div>
    </section>
<?php endif; ?>

<?php if (! $pendingPayment): ?>
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2 class="panel-title"><?= $subscription ? 'Renew or extend your plan' : 'Choose a plan' ?></h2>
                <p class="panel-sub"><?= $subscription ? 'An approved renewal starts after your current plan ends.' : 'Your selected subscription begins when the payment is approved.' ?></p>
            </div>
        </div>
        <div class="panel-body">
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-value"><?= e(money($prices['monthly'])) ?></div>
                    <div class="stat-label">Monthly</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= e(money($prices['yearly'])) ?></div>
                    <div class="stat-label">Yearly</div>
                    <div class="stat-foot">Save <?= e(money(($prices['monthly'] * 12) - $prices['yearly'])) ?> per year.</div>
                </div>
            </div>

            <?php if (! empty($gcash['account_name']) && ! empty($gcash['account_number'])): ?>
                <div class="note-box mt-2">
                    <?= icon('credit-card') ?>
                    <p>Send the exact plan amount to <strong><?= e($gcash['account_name']) ?></strong> via GCash at <strong><?= e($gcash['account_number']) ?></strong>. Keep the transaction reference from your completed transfer.</p>
                </div>
                <form method="post" action="<?= e(url('/technician/subscription/payment')) ?>" class="form mt-2" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="form-row">
                        <div class="field">
                            <label for="plan">Plan *</label>
                            <select id="plan" name="plan" class="<?= isset($errors['plan']) ? 'is-invalid' : '' ?>" required>
                                <option value="monthly" <?= old('plan', 'monthly') === 'monthly' ? 'selected' : '' ?>>Monthly — <?= e(money($prices['monthly'])) ?></option>
                                <option value="yearly" <?= old('plan') === 'yearly' ? 'selected' : '' ?>>Yearly — <?= e(money($prices['yearly'])) ?></option>
                            </select>
                            <?php if (isset($errors['plan'])): ?><span class="field-error"><?= e($errors['plan']) ?></span><?php endif; ?>
                        </div>
                        <div class="field">
                            <label for="gcash_reference">GCash transaction reference *</label>
                            <input type="text" id="gcash_reference" name="gcash_reference" value="<?= e(old('gcash_reference')) ?>" maxlength="40" autocomplete="off" class="<?= isset($errors['gcash_reference']) ? 'is-invalid' : '' ?>" required>
                            <?php if (isset($errors['gcash_reference'])): ?><span class="field-error"><?= e($errors['gcash_reference']) ?></span><?php endif; ?>
                        </div>
                    </div>
                    <div class="field">
                        <label for="proof">GCash payment receipt image *</label>
                        <input type="file" id="proof" name="proof" accept="image/jpeg,image/png,image/webp" class="<?= isset($errors['proof']) ? 'is-invalid' : '' ?>" required>
                        <?php if (isset($errors['proof'])): ?><span class="field-error"><?= e($errors['proof']) ?></span><?php endif; ?>
                        <span class="field-hint">JPG, PNG, or WebP; maximum 5 MB. Only admins can access this image.</span>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit"><?= icon('check') ?> Submit payment for review</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="note-box mt-2">
                    <?= icon('info') ?>
                    <p>GCash payment details are not set up yet. Please contact the administrator before sending money.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($latestPayment && $latestPayment->status === 'rejected'): ?>
    <section class="panel mt-2">
        <div class="panel-head">
            <h2 class="panel-title">Most recent payment was not approved</h2>
            <?= status_badge('rejected') ?>
        </div>
        <div class="panel-body">
            <p>Reference <?= e($latestPayment->gcash_reference) ?>. Check the reference and contact the administrator if you believe this is incorrect.</p>
        </div>
    </section>
<?php endif; ?>
@endsection
