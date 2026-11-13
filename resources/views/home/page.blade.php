@extends('layouts.main')

@section('content')
<section class="page-hero page-hero-compact">
    <div class="container page-hero-inner">
        <p class="eyebrow"><?= e(setting('site_name', 'FixitNow')) ?></p>
        <h1><?= e($page['title']) ?></h1>
        <?php if ($page['excerpt']): ?>
            <p><?= e($page['excerpt']) ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container container-narrow">
        <div class="prose prose-lg">
            <?= $page['content'] ?>
        </div>
        <p class="page-updated">Last updated <?= e(format_date($page['updated_at'])) ?></p>
    </div>
</section>

@endsection
