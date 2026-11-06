<?php
$isEdit = $page !== null;
$action = $isEdit ? '/admin/pages/'.$page['id'] : '/admin/pages';
$value = static function (string $key, $default = '') use ($page) {
    return old($key, $page[$key] ?? $default);
};
$showInFooter = $isEdit ? (int) $page['show_in_footer'] : 1;
if (old('show_in_footer') !== null) {
    $showInFooter = 1;
}
?>
<div class="page-head">
    <div>
        <nav class="breadcrumb" style="margin-bottom:8px">
            <a href="<?= e(url('/admin/pages')) ?>">CMS pages</a>
            <?= icon('chevron-right') ?>
            <span><?= $isEdit ? e($page['title']) : 'New page' ?></span>
        </nav>
        <h1><?= e($title) ?></h1>
        <p>Write the page content in plain text or HTML. Published pages are available at /p/{slug}.</p>
    </div>
    <div class="page-head-actions">
        <?php if ($isEdit) { ?>
            <a class="btn btn-ghost" href="<?= e(url('/p/'.$page['slug'])) ?>" target="_blank" rel="noopener"><?= icon('external') ?> View page</a>
        <?php } ?>
        <a class="btn btn-ghost" href="<?= e(url('/admin/pages')) ?>"><?= icon('arrow-left') ?> Back to pages</a>
    </div>
</div>

<div class="form-card">
    <form method="post" action="<?= e(url($action)) ?>" class="form">
        <?= csrf_field() ?>

        <div class="form-row">
            <div class="field">
                <label for="title">Page title *</label>
                <input type="text" id="title" name="title" value="<?= e($value('title')) ?>" class="<?= isset($errors['title']) ? 'is-invalid' : '' ?>" required>
                <?php if (isset($errors['title'])) { ?><span class="field-error"><?= e($errors['title']) ?></span><?php } ?>
            </div>
            <div class="field">
                <label for="slug">URL slug</label>
                <input type="text" id="slug" name="slug" value="<?= e($value('slug')) ?>" class="<?= isset($errors['slug']) ? 'is-invalid' : '' ?>" placeholder="auto-generated from the title">
                <?php if (isset($errors['slug'])) { ?><span class="field-error"><?= e($errors['slug']) ?></span><?php } ?>
            </div>
        </div>

        <div class="field">
            <label for="excerpt">Excerpt</label>
            <input type="text" id="excerpt" name="excerpt" value="<?= e($value('excerpt')) ?>" placeholder="Short summary shown in listings">
        </div>

        <div class="field">
            <label for="content">Content *</label>
            <textarea id="content" name="content" rows="12" class="<?= isset($errors['content']) ? 'is-invalid' : '' ?>" required><?= e($value('content')) ?></textarea>
            <?php if (isset($errors['content'])) { ?><span class="field-error"><?= e($errors['content']) ?></span><?php } ?>
            <span class="field-hint">Basic HTML is allowed (headings, paragraphs, lists, links).</span>
        </div>

        <div class="form-row">
            <div class="field">
                <label for="meta_title">Meta title</label>
                <input type="text" id="meta_title" name="meta_title" value="<?= e($value('meta_title')) ?>" placeholder="Defaults to the page title">
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="published" <?= $value('status', 'draft') === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="draft" <?= $value('status', 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                </select>
            </div>
        </div>

        <div class="field">
            <label for="meta_description">Meta description</label>
            <input type="text" id="meta_description" name="meta_description" value="<?= e($value('meta_description')) ?>" placeholder="Shown in search results">
        </div>

        <label class="checkbox">
            <input type="checkbox" name="show_in_footer" value="1" <?= $showInFooter ? 'checked' : '' ?>>
            <span>Show this page in the website footer</span>
        </label>

        <div class="form-actions">
            <a class="btn btn-ghost" href="<?= e(url('/admin/pages')) ?>">Cancel</a>
            <button class="btn btn-primary" type="submit"><?= icon('check') ?> <?= $isEdit ? 'Save page' : 'Create page' ?></button>
        </div>
    </form>
</div>
