@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">Revenue</p>
        <h1>Technician subscriptions</h1>
        <p>Verify GCash transfers before enabling technician access.</p>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-value"><?= number_format($pendingCount) ?></div>
        <div class="stat-label">Awaiting review</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= e(money($approvedIncome)) ?></div>
        <div class="stat-label">Approved subscription income</div>
        <div class="stat-foot">Manual GCash payments approved in this system.</div>
    </div>
</div>

<section class="panel mt-2">
    <div class="panel-head">
        <div>
            <h2 class="panel-title">Payment records</h2>
            <p class="panel-sub">The GCash reference is for matching against your GCash transaction history.</p>
        </div>
    </div>
    <?php if ($payments->isEmpty()): ?>
        <div class="empty-state">
            <?= icon('credit-card') ?>
            <h3>No subscription payments yet</h3>
            <p>Submitted payment references will appear here.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Technician</th>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>GCash reference</th>
                        <th>Proof</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Review</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td>
                                <div class="table-main">
                                    <strong><?= e($payment->technician_name) ?></strong>
                                    <small><?= e($payment->technician_email) ?></small>
                                </div>
                            </td>
                            <td><?= e(ucfirst($payment->plan)) ?></td>
                            <td><?= e(money($payment->amount)) ?></td>
                            <td><code><?= e($payment->gcash_reference) ?></code></td>
                            <td><a href="<?= e(url('/admin/subscriptions/'.$payment->id.'/proof')) ?>">View receipt</a></td>
                            <td><?= e(format_datetime($payment->created_at)) ?></td>
                            <td><?= status_badge($payment->status) ?></td>
                            <td>
                                <?php if ($payment->status === 'pending'): ?>
                                    <div class="inline-actions">
                                        <form method="post" action="<?= e(url('/admin/subscriptions/'.$payment->id.'/approve')) ?>" data-confirm="Verify this GCash transfer in your GCash account before approving.">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-success-soft btn-sm" type="submit">Approve</button>
                                        </form>
                                        <form method="post" action="<?= e(url('/admin/subscriptions/'.$payment->id.'/reject')) ?>">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-danger-soft btn-sm" type="submit">Reject</button>
                                        </form>
                                    </div>
                                <?php elseif ($payment->reviewer_name): ?>
                                    <span class="text-muted">By <?= e($payment->reviewer_name) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
@endsection
