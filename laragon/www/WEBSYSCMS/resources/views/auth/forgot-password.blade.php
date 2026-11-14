@extends('layouts.guest')

@section('content')
<div class="auth-head">
    <p class="eyebrow">Account recovery</p>
    <h1>Forgot your password?</h1>
    <p>Enter your account email and we’ll send a recovery link if it matches an account.</p>
</div>

<form method="post" action="<?= e(url('/forgot-password')) ?>" class="form">
    <?= csrf_field() ?>
    <div class="field">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" class="<?= isset($errors['email']) ? 'is-invalid' : '' ?>" autocomplete="email" required autofocus>
        <?php if (isset($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Send recovery email</button>
</form>

<p class="auth-switch">Remembered your password? <a href="<?= e(url('/login')) ?>">Back to sign in</a></p>
@endsection
