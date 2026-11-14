@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">Technician account</p>
        <h1>Subscription</h1>
        <p><?= $demoMode ? 'Choose a plan to demonstrate the subscription checkout.' : 'Choose a subscription plan and pay securely with GCash.' ?></p>
        <?php if ($demoMode): ?>
            <p class="panel-sub"><strong>Demo mode:</strong> checkout is simulated for your school project. No real GCash payment will happen.</p>
        <?php endif; ?>
    </div>
</div>

<?php if (! $accountApproved): ?>
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2 class="panel-title">Account awaiting admin approval</h2>
                <p class="panel-sub">Pay for a subscription first. Your account will be approved automatically after PayMongo confirms the GCash payment.</p>
            </div>
            <?= status_badge('pending') ?>
        </div>
    </section>
<?php endif; ?>

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
                <p class="panel-sub"><?= e(ucfirst($pendingPayment->plan)) ?> plan · <?= $pendingPayment->payment_provider === 'paymongo' ? 'PayMongo checkout' : ($pendingPayment->payment_provider === 'demo' ? 'Demo reference '.e($pendingPayment->gcash_reference) : 'GCash reference '.e($pendingPayment->gcash_reference)) ?></p>
            </div>
            <?= status_badge('pending') ?>
        </div>
        <div class="panel-body">
            <p><?php if ($pendingPayment->payment_provider === 'paymongo'): ?>PayMongo is confirming your payment. Reload this page in a moment to check for confirmation.<?php elseif ($pendingPayment->payment_provider === 'demo'): ?>This is a simulated payment for the project demonstration; no money was charged.<?php else: ?>The administrator will check the transfer in GCash.<?php endif; ?><?php if (! $accountApproved): ?> Your technician account and subscription will be approved together; work access stays locked until then.<?php else: ?> You can use technician features after the subscription is approved.<?php endif; ?></p>
            <?php if ($pendingPayment->payment_provider === 'paymongo' && $pendingPayment->checkout_url): ?>
                <form method="post" action="<?= e(url('/technician/subscription/checkout')) ?>" class="mt-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="plan" value="<?= e($pendingPayment->plan) ?>">
                    <button class="btn btn-primary" type="submit">Return to PayMongo checkout</button>
                </form>
            <?php elseif ($demoMode && $pendingPayment->payment_provider === 'demo'): ?>
                <form method="post" action="<?= e(url('/technician/subscription/checkout')) ?>" class="mt-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="plan" value="<?= e($pendingPayment->plan) ?>">
                    <button class="btn btn-primary" type="submit">Continue to Demo Checkout</button>
                </form>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<?php if (request()->query('payment') === 'returned'): ?>
    <div class="note-box mt-2"><p>Checkout returned. We are waiting for PayMongo to confirm the payment; account access will be enabled only after confirmation.</p></div>
<?php elseif (request()->query('payment') === 'cancelled'): ?>
    <div class="note-box mt-2"><p>Checkout was cancelled. You can try again when ready.</p></div>
<?php endif; ?>

<?php
$oldPlan = old('plan', $selectedPlan);
if (is_string($oldPlan) && array_key_exists($oldPlan, $prices)) {
    $selectedPlan = $oldPlan;
}
?>

<?php if (! $pendingPayment): ?>
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2 class="panel-title"><?= $subscription ? 'Renew or extend your plan' : 'Choose a plan' ?></h2>
                <p class="panel-sub"><?= $subscription ? 'An approved renewal starts after your current plan ends.' : 'Your selected subscription begins when the payment is approved.' ?></p>
            </div>
        </div>
        <div class="panel-body">
            <?php $checkoutEnabled = $demoMode || $paymongoReady; ?>
            <form
                method="<?= $checkoutEnabled ? 'post' : 'get' ?>"
                action="<?= e(url($checkoutEnabled ? '/technician/subscription/checkout' : '/technician/subscription')) ?>"
                class="plan-selector"
            >
                <?php if ($checkoutEnabled): ?><?= csrf_field() ?><?php endif; ?>
                <div class="plan-options">
                    <label class="plan-option <?= $selectedPlan === 'monthly' ? 'is-selected' : '' ?>">
                        <span class="plan-option-choice">
                            <input type="radio" name="plan" value="monthly" <?= $selectedPlan === 'monthly' ? 'checked' : '' ?>>
                            <span>Monthly</span>
                        </span>
                        <span class="stat-value"><?= e(money($prices['monthly'])) ?></span>
                        <span class="stat-label">One month of technician access</span>
                    </label>
                    <label class="plan-option <?= $selectedPlan === 'yearly' ? 'is-selected' : '' ?>">
                        <span class="plan-option-choice">
                            <input type="radio" name="plan" value="yearly" <?= $selectedPlan === 'yearly' ? 'checked' : '' ?>>
                            <span>Yearly</span>
                        </span>
                        <span class="stat-value"><?= e(money($prices['yearly'])) ?></span>
                        <span class="stat-foot">Save <?= e(money(($prices['monthly'] * 12) - $prices['yearly'])) ?> per year.</span>
                    </label>
                </div>
                <button class="btn btn-primary mt-2" type="submit">
                    <?php if ($demoMode): ?><?= icon('credit-card') ?> Continue to Demo Checkout<?php elseif ($paymongoReady): ?><?= icon('credit-card') ?> Subscribe with GCash<?php else: ?>Continue with selected plan<?php endif; ?>
                </button>
            </form>

            <?php if ($demoMode): ?>
                <div class="note-box mt-2">
                    <?= icon('info') ?>
                    <p>Demo mode is for your school presentation only. The next screen simulates the checkout and does not open GCash, charge an account, or verify a real payment.</p>
                </div>
            <?php elseif ($paymongoReady): ?>
                <div class="note-box mt-2">
                    <?= icon('credit-card') ?>
                    <p>Choose Monthly or Yearly, then click <strong>Subscribe with GCash</strong> to open PayMongo checkout. Your account and subscription activate after PayMongo confirms payment.</p>
                </div>
            <?php elseif (! empty($gcash['account_name']) && ! empty($gcash['account_number'])): ?>
                <div class="note-box mt-2">
                    <?= icon('credit-card') ?>
                    <p>Send the exact <strong><?= e(ucfirst($selectedPlan)) ?> plan amount of <?= e(money($prices[$selectedPlan])) ?></strong> to <strong><?= e($gcash['account_name']) ?></strong> via GCash at <strong><?= e($gcash['account_number']) ?></strong>. Keep the transaction reference from your completed transfer.</p>
                </div>
                <form method="post" action="<?= e(url('/technician/subscription/payment')) ?>" class="form mt-2" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="plan" value="<?= e($selectedPlan) ?>">
                    <div class="form-row">
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
                    <p>You can choose a plan, but PayMongo checkout is not configured in this environment, so no payment page will open. Ask the administrator to configure PayMongo before trying to subscribe.</p>
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
