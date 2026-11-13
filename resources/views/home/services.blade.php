@extends('layouts.main')

@section('content')
<section class="page-hero">
    <div class="container page-hero-inner">
        <p class="eyebrow">Our services</p>
        <h1>Certified care for the appliances you rely on</h1>
        <p>Choose a category to see what is covered, typical base pricing, and how to book a visit.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="service-grid service-grid-lg">
            <?php foreach ($categories as $category): ?>
                <a class="service-card" href="<?= e(url('/services/' . $category['slug'])) ?>">
                    <span class="service-icon"><?= icon($category['icon']) ?></span>
                    <h3><?= e($category['name']) ?></h3>
                    <p><?= e($category['short_description']) ?></p>
                    <span class="service-card-meta">
                        <span><strong><?= e(money($category['base_price'])) ?></strong> base visit</span>
                        <span><?= (int) $category['request_count'] ?> requests served</span>
                    </span>
                    <span class="service-card-foot">
                        <span>View service details</span>
                        <?= icon('arrow-right') ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="info-banner">
            <div>
                <h2>Not sure which service you need?</h2>
                <p>Describe the problem and our coordinator will route it to the right technician.</p>
            </div>
            <a class="btn btn-primary" href="<?= e(url(Auth::check() ? '/customer/requests/new' : '/register')) ?>">Start a request</a>
        </div>
    </div>
</section>

@endsection
