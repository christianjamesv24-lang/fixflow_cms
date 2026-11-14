@extends('layouts.guest')

@section('content')
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
        <?php if (isset($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?>
    </div>

    <div class="field">
        <label for="password">Password</label>
        <div class="input-wrap">
            <input type="password" id="password" name="password" class="<?= isset($errors['password']) ? 'is-invalid' : '' ?>" autocomplete="current-password" required>
            <button type="button" class="input-action" data-toggle-password="password" aria-label="Show password"><?= icon('eye') ?></button>
        </div>
        <?php if (isset($errors['password'])): ?><span class="field-error"><?= e($errors['password']) ?></span><?php endif; ?>
    </div>

    <p class="auth-switch"><a href="<?= e(route('password.request')) ?>">Forgot password?</a></p>
    <button type="submit" class="btn btn-primary btn-block">Sign in</button>
</form>


<p class="auth-switch">New to <?= e(setting('site_name', 'FixitNow')) ?>? <a href="<?= e(url('/register')) ?>">Create an account</a></p>

@endsection
