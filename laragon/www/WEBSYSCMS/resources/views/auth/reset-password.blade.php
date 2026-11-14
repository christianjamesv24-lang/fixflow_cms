@extends('layouts.guest')

@section('content')
<div class="auth-head">
    <p class="eyebrow">Account recovery</p>
    <h1>Choose a new password</h1>
    <p>Use at least 8 characters. Your recovery link can only be used once.</p>
</div>

<form method="post" action="<?= e(url('/reset-password/'.$token)) ?>" class="form">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">

    <div class="field">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" value="<?= e(old('email', $email)) ?>" class="<?= isset($errors['email']) ? 'is-invalid' : '' ?>" autocomplete="email" required>
        <?php if (isset($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?>
    </div>

    <div class="field">
        <label for="password">New password</label>
        <div class="input-wrap">
            <input type="password" id="password" name="password" class="<?= isset($errors['password']) ? 'is-invalid' : '' ?>" autocomplete="new-password" minlength="8" required autofocus>
            <button type="button" class="input-action" data-toggle-password="password" aria-label="Show password"><?= icon('eye') ?></button>
        </div>
        <?php if (isset($errors['password'])): ?><span class="field-error"><?= e($errors['password']) ?></span><?php endif; ?>
    </div>

    <div class="field">
        <label for="password_confirmation">Confirm new password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" class="<?= isset($errors['password_confirmation']) ? 'is-invalid' : '' ?>" autocomplete="new-password" minlength="8" required>
        <?php if (isset($errors['password_confirmation'])): ?><span class="field-error"><?= e($errors['password_confirmation']) ?></span><?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Reset password</button>
</form>

<p class="auth-switch"><a href="<?= e(url('/forgot-password')) ?>">Request a new recovery link</a></p>
@endsection
