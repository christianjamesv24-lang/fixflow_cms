<div class="auth-head">
    <p class="eyebrow">Welcome back</p>
    <h1>Sign in to your account</h1>
    <p>Track requests, manage jobs, or run the service desk.</p>
</div>

<form method="post" action="<?= e(url('/login')) ?>" class="form">
    <?= csrf_field() ?>
    <div class="field">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" class="<?= isset($errors['email']) ? 'is-invalid' : '' ?>" autocomplete="email" required autofocus>
        <?php if (isset($errors['email'])) { ?><span class="field-error"><?= e($errors['email']) ?></span><?php } ?>
    </div>

    <div class="field">
        <label for="password">Password</label>
        <div class="input-wrap">
            <input type="password" id="password" name="password" class="<?= isset($errors['password']) ? 'is-invalid' : '' ?>" autocomplete="current-password" required>
            <button type="button" class="input-action" data-toggle-password="password" aria-label="Show password"><?= icon('eye') ?></button>
        </div>
        <?php if (isset($errors['password'])) { ?><span class="field-error"><?= e($errors['password']) ?></span><?php } ?>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Sign in</button>
</form>

<div class="demo-box">
    <p class="demo-title">Demo accounts (password shown)</p>
    <button type="button" class="demo-account" data-demo-email="admin@fixflow.test" data-demo-password="Admin@123">
        <span><strong>Administrator</strong><small>admin@fixflow.test</small></span>
        <span class="demo-fill">Use Admin@123</span>
    </button>
    <button type="button" class="demo-account" data-demo-email="john@fixflow.test" data-demo-password="Tech@123">
        <span><strong>Technician</strong><small>john@fixflow.test</small></span>
        <span class="demo-fill">Use Tech@123</span>
    </button>
    <button type="button" class="demo-account" data-demo-email="ana@fixflow.test" data-demo-password="Customer@123">
        <span><strong>Customer</strong><small>ana@fixflow.test</small></span>
        <span class="demo-fill">Use Customer@123</span>
    </button>
</div>

<p class="auth-switch">New to <?= e(setting('site_name', 'FixFlow')) ?>? <a href="<?= e(url('/register')) ?>">Create an account</a></p>
