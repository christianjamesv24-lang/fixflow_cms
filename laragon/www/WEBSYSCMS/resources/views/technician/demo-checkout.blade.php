@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">Project demonstration</p>
        <h1>Simulated checkout</h1>
        <p>This screen demonstrates the payment flow only. It is not GCash and no money will be charged or transferred.</p>
    </div>
</div>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel-title"><?= e(ucfirst($payment->plan)) ?> technician subscription</h2>
            <p class="panel-sub">Demo reference <?= e($payment->gcash_reference) ?></p>
        </div>
        <?= status_badge('demo') ?>
    </div>
    <div class="panel-body">
        <p><strong>Demo amount:</strong> <?= e(money($payment->amount)) ?></p>
        <div class="note-box mt-2">
            <?= icon('info') ?>
            <p><strong>For presentation purposes only.</strong> Use the button below to simulate a successful payment. This will activate the demo subscription in this local project but will not contact GCash or any payment provider.</p>
        </div>
        <div class="form-actions mt-2">
            <form method="post" action="<?= e(url('/technician/subscription/demo/'.$payment->id.'/complete')) ?>">
                <?= csrf_field() ?>
                <button class="btn btn-primary" type="submit">Simulate successful payment</button>
            </form>
            <form method="post" action="<?= e(url('/technician/subscription/demo/'.$payment->id.'/cancel')) ?>">
                <?= csrf_field() ?>
                <button class="btn btn-secondary" type="submit">Cancel demo checkout</button>
            </form>
        </div>
    </div>
</section>
@endsection
