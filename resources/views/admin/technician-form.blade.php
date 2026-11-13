@extends('layouts.app')

@section('content')
<?php
$isEdit = $technician !== null;
$action = $isEdit ? '/admin/technicians/' . $technician['id'] : '/admin/technicians';
$value = static function (string $key, $default = '') use ($technician) {
    return old($key, $technician[$key] ?? $default);
};
$avatarColor = $value('avatar_color', '#2563eb');
?>
<div class="page-head">
    <div>
        <nav class="breadcrumb" style="margin-bottom:8px">
            <a href="<?= e(url('/admin/technicians')) ?>">Technicians</a>
            <?= icon('chevron-right') ?>
            <span><?= $isEdit ? 'Edit' : 'Add new' ?></span>
        </nav>
        <h1><?= e($title) ?></h1>
        <p><?= $isEdit ? 'Update the technician profile, availability, and login.' : 'Create a field account and set their specialty.' ?></p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-ghost" href="<?= e(url('/admin/technicians')) ?>"><?= icon('arrow-left') ?> Back to list</a>
    </div>
</div>

<div class="form-card">
    <form method="post" action="<?= e(url($action)) ?>" class="form">
        <?= csrf_field() ?>

        <div class="form-row">
            <div class="field">
                <label for="name">Full name *</label>
                <input type="text" id="name" name="name" value="<?= e($value('name')) ?>" class="<?= isset($errors['name']) ? 'is-invalid' : '' ?>" required>
                <?php if (isset($errors['name'])): ?><span class="field-error"><?= e($errors['name']) ?></span><?php endif; ?>
            </div>
            <div class="field">
                <label for="email">Email address *</label>
                <input type="email" id="email" name="email" value="<?= e($value('email')) ?>" class="<?= isset($errors['email']) ? 'is-invalid' : '' ?>" required>
                <?php if (isset($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?>
            </div>
        </div>

        <div class="form-row">
            <div class="field">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" value="<?= e($value('phone')) ?>" placeholder="+63 917 000 0000">
            </div>
            <div class="field">
                <label for="specialty">Specialty</label>
                <input type="text" id="specialty" name="specialty" value="<?= e($value('specialty')) ?>" placeholder="e.g. Refrigeration and air conditioning">
            </div>
        </div>

        <div class="field">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" value="<?= e($value('address')) ?>" placeholder="Service base or home address">
        </div>

        <div class="field">
            <label for="bio">Short bio</label>
            <textarea id="bio" name="bio" rows="3" placeholder="Experience, certifications, or languages spoken"><?= e($value('bio')) ?></textarea>
        </div>

        <div class="form-row">
            <div class="field">
                <label for="password"><?= $isEdit ? 'New password (leave blank to keep current)' : 'Password *' ?></label>
                <div class="input-wrap">
                    <input type="password" id="password" name="password" class="<?= isset($errors['password']) ? 'is-invalid' : '' ?>" <?= $isEdit ? '' : 'required' ?> data-password-input autocomplete="new-password">
                    <button class="input-action" type="button" data-password-toggle aria-label="Show password"><?= icon('eye') ?></button>
                </div>
                <?php if (isset($errors['password'])): ?><span class="field-error"><?= e($errors['password']) ?></span><?php endif; ?>
                <span class="field-hint">Minimum 8 characters.</span>
            </div>
            <div class="field">
                <label for="status">Account status</label>
                <select id="status" name="status">
                    <option value="active" <?= $value('status', 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $value('status', 'active') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>

        <div class="field">
            <label for="avatar_color">Avatar color</label>
            <div class="inline-actions">
                <input type="color" id="avatar_color" name="avatar_color" value="<?= e($avatarColor) ?>" style="width:64px;height:42px;padding:4px;border-radius:10px;border:1px solid var(--line);background:#fff">
                <span class="field-hint" style="margin:0">Used for the technician's initials in the dashboard.</span>
            </div>
        </div>

        <div class="form-actions">
            <a class="btn btn-ghost" href="<?= e(url('/admin/technicians')) ?>">Cancel</a>
            <button class="btn btn-primary" type="submit"><?= icon('check') ?> <?= $isEdit ? 'Save changes' : 'Create technician' ?></button>
        </div>
    </form>
</div>

@endsection
