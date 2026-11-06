<section class="error-page">
    <div class="container error-inner">
        <p class="error-code">404</p>
        <h1>Page not found</h1>
        <p><?= e($message ?? 'The page you are looking for does not exist or has moved.') ?></p>
        <div class="error-actions">
            <a class="btn btn-primary" href="<?= e(url('/')) ?>">Back to home</a>
            <a class="btn btn-ghost" href="<?= e(url('/services')) ?>">Browse services</a>
        </div>
    </div>
</section>
