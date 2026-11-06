@extends('layouts.app')

@section('content')
<?php
$selectedCategory = old('category_id', '');
if ($selectedCategory === '' && $selected !== '') {
    foreach ($categories as $category) {
        if ($category['slug'] === $selected) {
            $selectedCategory = $category['id'];
            break;
        }
    }
}
?>
<div class="page-head">
    <div>
        <p class="eyebrow">New request</p>
        <h1>Book a service visit</h1>
        <p>Tell us about the appliance and the problem. Our coordinator will assign the right technician.</p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-ghost" href="<?= e(url('/customer/requests')) ?>"><?= icon('arrow-left') ?> Back to requests</a>
    </div>
</div>

<form method="post" action="<?= e(url('/customer/requests')) ?>" class="form-card form">
    <?= csrf_field() ?>

    <div class="section-title" style="padding:0 0 14px;border-bottom:1px solid var(--line);margin-bottom:4px">
        <h2>Appliance &amp; service</h2>
        <span>Required details</span>
    </div>

    <div class="form-row">
        <div class="field">
            <label for="category_id">Service category *</label>
            <select id="category_id" name="category_id" class="<?= isset($errors['category_id']) ? 'is-invalid' : '' ?>" required>
                <option value="">Choose an appliance type</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>" <?= (string) $selectedCategory === (string) $category['id'] ? 'selected' : '' ?>>
                        <?= e($category['name']) ?> — from <?= e(money($category['base_price'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['category_id'])): ?><span class="field-error"><?= e($errors['category_id']) ?></span><?php endif; ?>
        </div>

        <div class="field">
            <label for="service_type">Service type *</label>
            <select id="service_type" name="service_type" class="<?= isset($errors['service_type']) ? 'is-invalid' : '' ?>" required>
                <?php foreach (['repair' => 'Repair', 'maintenance' => 'Maintenance', 'installation' => 'Installation', 'inspection' => 'Inspection'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= old('service_type', 'repair') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['service_type'])): ?><span class="field-error"><?= e($errors['service_type']) ?></span><?php endif; ?>
        </div>
    </div>

    <div class="field">
        <label for="title">Request title *</label>
        <input type="text" id="title" name="title" value="<?= e(old('title')) ?>" class="<?= isset($errors['title']) ? 'is-invalid' : '' ?>" placeholder="Example: Refrigerator not cooling" required>
        <?php if (isset($errors['title'])): ?><span class="field-error"><?= e($errors['title']) ?></span><?php endif; ?>
    </div>

    <div class="field">
        <label for="description">Describe the problem *</label>
        <textarea id="description" name="description" rows="5" class="<?= isset($errors['description']) ? 'is-invalid' : '' ?>" placeholder="What is happening? Include error codes, unusual sounds, or when the issue started." required><?= e(old('description')) ?></textarea>
        <?php if (isset($errors['description'])): ?><span class="field-error"><?= e($errors['description']) ?></span><?php endif; ?>
    </div>

    <div class="form-row">
        <div class="field">
            <label for="brand">Brand</label>
            <input type="text" id="brand" name="brand" value="<?= e(old('brand')) ?>" placeholder="e.g. Samsung">
        </div>
        <div class="field">
            <label for="model">Model</label>
            <input type="text" id="model" name="model" value="<?= e(old('model')) ?>" placeholder="e.g. RT29K">
        </div>
    </div>

    <div class="form-row">
        <div class="field">
            <label for="serial_no">Serial number</label>
            <input type="text" id="serial_no" name="serial_no" value="<?= e(old('serial_no')) ?>" placeholder="Optional">
        </div>
        <div class="field">
            <label for="priority">Priority</label>
            <select id="priority" name="priority">
                <?php foreach (['low' => 'Low — can wait', 'medium' => 'Medium — this week', 'high' => 'High — soon', 'urgent' => 'Urgent — safety issue'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= old('priority', 'medium') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="section-title" style="padding:14px 0 14px;border-top:1px solid var(--line);border-bottom:1px solid var(--line);margin:6px 0 4px">
        <h2>Scheduling &amp; location</h2>
        <span>Preferred visit</span>
    </div>

    <div class="form-row">
        <div class="field">
            <label for="preferred_date">Preferred date</label>
            <input type="date" id="preferred_date" name="preferred_date" value="<?= e(old('preferred_date')) ?>" min="<?= date('Y-m-d') ?>" class="<?= isset($errors['preferred_date']) ? 'is-invalid' : '' ?>">
            <?php if (isset($errors['preferred_date'])): ?><span class="field-error"><?= e($errors['preferred_date']) ?></span><?php endif; ?>
        </div>
        <div class="field">
            <label for="preferred_time">Preferred time window</label>
            <select id="preferred_time" name="preferred_time">
                <?php foreach (['Any time', 'Morning (8AM - 12PM)', 'Afternoon (1PM - 5PM)', 'Evening (5PM - 7PM)'] as $slot): ?>
                    <option value="<?= e($slot) ?>" <?= old('preferred_time', 'Morning (8AM - 12PM)') === $slot ? 'selected' : '' ?>><?= e($slot) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="field">
        <label for="address">Service address *</label>
        <input type="text" id="address" name="address" value="<?= e(old('address', $user['address'] ?? '')) ?>" class="<?= isset($errors['address']) ? 'is-invalid' : '' ?>" placeholder="House / unit, street, barangay, city" required>
        <?php if (isset($errors['address'])): ?><span class="field-error"><?= e($errors['address']) ?></span><?php endif; ?>
    </div>

    <div class="note-box">
        <?= icon('info') ?>
        <p><strong>What happens next?</strong> A coordinator reviews your request, assigns a certified technician, and schedules the visit. You can track everything from your dashboard.</p>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= icon('send') ?> Submit request</button>
        <a class="btn btn-ghost" href="<?= e(url('/customer/requests')) ?>">Cancel</a>
    </div>
</form>

@endsection
