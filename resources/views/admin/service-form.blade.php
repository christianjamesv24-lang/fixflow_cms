@extends('layouts.app')

@section('content')
<?php
$isEdit = $category !== null;
$action = $isEdit ? '/admin/services/' . $category['id'] : '/admin/services';
$value = static function (string $key, $default = '') use ($category) {
    return old($key, $category[$key] ?? $default);
};
$iconLabels = [
    'snowflake' => 'Aircon / snowflake',
    'fridge' => 'Refrigerator',
    'washer' => 'Washing machine',
    'oven' => 'Oven / range',
    'microwave' => 'Microwave',
    'droplet' => 'Water dispenser / plumbing',
    'activity' => 'Diagnostics',
    'refresh' => 'Maintenance',
    'wrench' => 'General repair',
    'zap' => 'Electrical',
    'shield' => 'Protection plan',
    'settings' => 'Tune-up',
    'tool' => 'Installation',
];
?>
<div class="page-head">
    <div>
        <nav class="breadcrumb" style="margin-bottom:8px">
            <a href="<?= e(url('/admin/services')) ?>">Service catalog</a>
            <?= icon('chevron-right') ?>
            <span><?= $isEdit ? e($category['name']) : 'New category' ?></span>
        </nav>
        <h1><?= e($title) ?></h1>
        <p>Categories appear on the public services page and in the request form.</p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-ghost" href="<?= e(url('/admin/services')) ?>"><?= icon('arrow-left') ?> Back to catalog</a>
    </div>
</div>

<div class="form-card">
    <form method="post" action="<?= e(url($action)) ?>" class="form">
        <?= csrf_field() ?>

        <div class="form-row">
            <div class="field">
                <label for="name">Category name *</label>
                <input type="text" id="name" name="name" value="<?= e($value('name')) ?>" class="<?= isset($errors['name']) ? 'is-invalid' : '' ?>" required>
                <?php if (isset($errors['name'])): ?><span class="field-error"><?= e($errors['name']) ?></span><?php endif; ?>
            </div>
            <div class="field">
                <label for="slug">URL slug</label>
                <input type="text" id="slug" name="slug" value="<?= e($value('slug')) ?>" class="<?= isset($errors['slug']) ? 'is-invalid' : '' ?>" placeholder="auto-generated from the name">
                <?php if (isset($errors['slug'])): ?><span class="field-error"><?= e($errors['slug']) ?></span><?php endif; ?>
            </div>
        </div>

        <div class="form-row">
            <div class="field">
                <label for="icon">Icon</label>
                <select id="icon" name="icon">
                    <?php foreach ($iconLabels as $iconName => $label): ?>
                        <option value="<?= e($iconName) ?>" <?= $value('icon', 'wrench') === $iconName ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="base_price">Base price (₱) *</label>
                <input type="number" step="0.01" min="0" id="base_price" name="base_price" value="<?= e($value('base_price', '0')) ?>" class="<?= isset($errors['base_price']) ? 'is-invalid' : '' ?>" required>
                <?php if (isset($errors['base_price'])): ?><span class="field-error"><?= e($errors['base_price']) ?></span><?php endif; ?>
            </div>
        </div>

        <div class="field">
            <label for="short_description">Short description</label>
            <input type="text" id="short_description" name="short_description" value="<?= e($value('short_description')) ?>" placeholder="One line shown on service cards">
        </div>

        <div class="field">
            <label for="description">Full description</label>
            <textarea id="description" name="description" rows="5" placeholder="What this service includes, coverage, and typical turnaround"><?= e($value('description')) ?></textarea>
        </div>

        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="active" <?= $value('status', 'active') === 'active' ? 'selected' : '' ?>>Active (visible to customers)</option>
                <option value="inactive" <?= $value('status', 'active') === 'inactive' ? 'selected' : '' ?>>Inactive (hidden)</option>
            </select>
        </div>

        <div class="form-actions">
            <a class="btn btn-ghost" href="<?= e(url('/admin/services')) ?>">Cancel</a>
            <button class="btn btn-primary" type="submit"><?= icon('check') ?> <?= $isEdit ? 'Save category' : 'Create category' ?></button>
        </div>
    </form>
</div>

@endsection
