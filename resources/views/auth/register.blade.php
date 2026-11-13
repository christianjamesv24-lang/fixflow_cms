@extends('layouts.guest')

@section('content')
<div class="auth-head">
    <p class="eyebrow">Create your account</p>
    <h1>Book and track appliance service</h1>
    <p>One account keeps your requests, appointments, and service reports together.</p>
</div>

<form method="post" action="<?= e(url('/register')) ?>" class="form">
    <?= csrf_field() ?>

    <div class="field">
        <label for="name">Full name</label>
        <input type="text" id="name" name="name" value="<?= e(old('name')) ?>" class="<?= isset($errors['name']) ? 'is-invalid' : '' ?>" autocomplete="name" required autofocus>
        <?php if (isset($errors['name'])): ?><span class="field-error"><?= e($errors['name']) ?></span><?php endif; ?>
    </div>

    <div class="form-row">
        <div class="field">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" class="<?= isset($errors['email']) ? 'is-invalid' : '' ?>" autocomplete="email" required>
            <?php if (isset($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?>
        </div>
        <div class="field">
            <label for="phone">Phone number</label>
            <input type="text" id="phone" name="phone" value="<?= e(old('phone')) ?>" autocomplete="tel" placeholder="+63 9XX XXX XXXX">
        </div>
    </div>

    <div class="field">
        <label for="address">Service address</label>
        <input type="text" id="address" name="address" value="<?= e(old('address')) ?>" autocomplete="street-address" placeholder="House / unit, street, barangay, city">
        <span class="field-hint">You can change this per request later.</span>
    </div>

    <div class="form-row">
        <div class="field">
            <label for="password">Password</label>
            <div class="input-wrap">
                <input type="password" id="password" name="password" class="<?= isset($errors['password']) ? 'is-invalid' : '' ?>" autocomplete="new-password" required>
                <button type="button" class="input-action" data-toggle-password="password" aria-label="Show password"><?= icon('eye') ?></button>
            </div>
            <span class="field-hint">At least 8 characters.</span>
            <?php if (isset($errors['password'])): ?><span class="field-error"><?= e($errors['password']) ?></span><?php endif; ?>
        </div>
        <div class="field">
            <label for="password_confirmation">Confirm password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="<?= isset($errors['password_confirmation']) ? 'is-invalid' : '' ?>" autocomplete="new-password" required>
            <?php if (isset($errors['password_confirmation'])): ?><span class="field-error"><?= e($errors['password_confirmation']) ?></span><?php endif; ?>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Create account</button>
</form>

<p class="auth-switch">Already have an account? <a href="<?= e(url('/login')) ?>">Sign in</a></p>
<p class="auth-terms">By creating an account you agree to our <a href="<?= e(url('/p/terms')) ?>">terms</a> and <a href="<?= e(url('/p/privacy')) ?>">privacy policy</a>.</p>

@endsection
