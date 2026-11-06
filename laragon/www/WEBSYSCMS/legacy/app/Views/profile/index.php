<?php
$roleLabels = ['admin' => 'Administrator', 'technician' => 'Technician', 'customer' => 'Customer'];
$role = $user['role'];
$roleLabel = $roleLabels[$role] ?? ucfirst($role);
?>
<div class="page-head">
    <div>
        <p class="eyebrow">Account</p>
        <h1>My profile</h1>
        <p>Manage your personal details and account password.</p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-ghost" href="<?= e(url(dashboard_url())) ?>"><?= icon('grid') ?> Back to dashboard</a>
    </div>
</div>

<div class="profile-grid">
    <div class="dash-stack">
        <div class="profile-card">
            <span class="avatar avatar-lg" style="background: <?= e($user['avatar_color'] ?: '#2563eb') ?>"><?= e(initials($user['name'])) ?></span>
            <h2><?= e($user['name']) ?></h2>
            <p class="role-label"><?= e($roleLabel) ?></p>

            <ul class="profile-meta">
                <li><?= icon('mail') ?> <?= e($user['email']) ?></li>
                <li><?= icon('phone') ?> <?= e($user['phone'] ?: 'No phone saved') ?></li>
                <li><?= icon('pin') ?> <?= e($user['address'] ?: 'No address saved') ?></li>
                <li><?= icon('calendar') ?> Joined <?= e(format_date($user['created_at'], 'F Y')) ?></li>
            </ul>
        </div>

        <?php if ($role === 'customer' && $stats) { ?>
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Service summary</h2>
                </div>
                <div class="panel-body stack-sm">
                    <div class="flex-between text-sm">
                        <span class="text-muted">Total requests</span>
                        <strong><?= (int) $stats['total'] ?></strong>
                    </div>
                    <div class="flex-between text-sm">
                        <span class="text-muted">Active jobs</span>
                        <strong><?= (int) $stats['active'] ?></strong>
                    </div>
                    <div class="flex-between text-sm">
                        <span class="text-muted">Completed jobs</span>
                        <strong><?= (int) $stats['completed'] ?></strong>
                    </div>
                </div>
            </section>
        <?php } elseif ($role === 'technician' && $stats) { ?>
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Work summary</h2>
                </div>
                <div class="panel-body stack-sm">
                    <div class="flex-between text-sm">
                        <span class="text-muted">Active jobs</span>
                        <strong><?= (int) $stats['active'] ?></strong>
                    </div>
                    <div class="flex-between text-sm">
                        <span class="text-muted">Completed jobs</span>
                        <strong><?= (int) $stats['completed'] ?></strong>
                    </div>
                    <div class="flex-between text-sm">
                        <span class="text-muted">Average rating</span>
                        <strong><?= $stats['rating'] > 0 ? number_format($stats['rating'], 1).' ★' : '—' ?></strong>
                    </div>
                </div>
            </section>
        <?php } ?>

        <?php if ($role === 'technician' && $user['specialty']) { ?>
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Specialty</h2>
                </div>
                <div class="panel-body">
                    <p class="text-sm" style="margin:0"><?= e($user['specialty']) ?></p>
                    <?php if ($user['bio']) { ?>
                        <p class="text-sm text-muted mt-1" style="margin-bottom:0"><?= e($user['bio']) ?></p>
                    <?php } ?>
                </div>
            </section>
        <?php } ?>
    </div>

    <div class="dash-stack">
        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Personal details</h2>
                    <p class="panel-sub">Keep your contact information up to date.</p>
                </div>
            </div>
            <form method="post" action="<?= e(url('/profile')) ?>" class="form">
                <?= csrf_field() ?>
                <div class="form-row">
                    <div class="field">
                        <label for="name">Full name *</label>
                        <input type="text" id="name" name="name" value="<?= e(old('name', $user['name'])) ?>" class="<?= isset($errors['name']) ? 'is-invalid' : '' ?>" required>
                        <?php if (isset($errors['name'])) { ?><span class="field-error"><?= e($errors['name']) ?></span><?php } ?>
                    </div>
                    <div class="field">
                        <label for="email">Email address</label>
                        <input type="email" id="email" value="<?= e($user['email']) ?>" disabled>
                        <span class="field-hint">Contact support to change your sign-in email.</span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="field">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" value="<?= e(old('phone', $user['phone'])) ?>" placeholder="+63 917 000 0000">
                    </div>
                    <?php if ($role === 'technician') { ?>
                        <div class="field">
                            <label for="specialty">Specialty</label>
                            <input type="text" id="specialty" name="specialty" value="<?= e(old('specialty', $user['specialty'])) ?>" placeholder="e.g. Air conditioning">
                        </div>
                    <?php } ?>
                </div>

                <div class="field">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" value="<?= e(old('address', $user['address'])) ?>" placeholder="Street, city, province">
                </div>

                <?php if ($role === 'technician') { ?>
                    <div class="field">
                        <label for="bio">Short bio</label>
                        <textarea id="bio" name="bio" rows="3" placeholder="Experience, certifications, or languages spoken"><?= e(old('bio', $user['bio'])) ?></textarea>
                    </div>
                <?php } ?>

                <div class="form-actions">
                    <button class="btn btn-primary" type="submit"><?= icon('check') ?> Save changes</button>
                </div>
            </form>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Change password</h2>
                    <p class="panel-sub">Use at least 8 characters.</p>
                </div>
            </div>
            <form method="post" action="<?= e(url('/profile/password')) ?>" class="form">
                <?= csrf_field() ?>
                <div class="field">
                    <label for="current_password">Current password *</label>
                    <div class="input-wrap">
                        <input type="password" id="current_password" name="current_password" class="<?= isset($errors['current_password']) ? 'is-invalid' : '' ?>" required autocomplete="current-password">
                        <button class="input-action" type="button" data-password-toggle aria-label="Show password"><?= icon('eye') ?></button>
                    </div>
                    <?php if (isset($errors['current_password'])) { ?><span class="field-error"><?= e($errors['current_password']) ?></span><?php } ?>
                </div>

                <div class="form-row">
                    <div class="field">
                        <label for="new_password">New password *</label>
                        <div class="input-wrap">
                            <input type="password" id="new_password" name="new_password" class="<?= isset($errors['new_password']) ? 'is-invalid' : '' ?>" required autocomplete="new-password">
                            <button class="input-action" type="button" data-password-toggle aria-label="Show password"><?= icon('eye') ?></button>
                        </div>
                        <?php if (isset($errors['new_password'])) { ?><span class="field-error"><?= e($errors['new_password']) ?></span><?php } ?>
                    </div>
                    <div class="field">
                        <label for="new_password_confirmation">Confirm new password *</label>
                        <div class="input-wrap">
                            <input type="password" id="new_password_confirmation" name="new_password_confirmation" class="<?= isset($errors['new_password_confirmation']) ? 'is-invalid' : '' ?>" required autocomplete="new-password">
                            <button class="input-action" type="button" data-password-toggle aria-label="Show password"><?= icon('eye') ?></button>
                        </div>
                        <?php if (isset($errors['new_password_confirmation'])) { ?><span class="field-error"><?= e($errors['new_password_confirmation']) ?></span><?php } ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn btn-soft" type="submit"><?= icon('shield') ?> Update password</button>
                </div>
            </form>
        </section>
    </div>
</div>
