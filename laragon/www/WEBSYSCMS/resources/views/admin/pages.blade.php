@extends('layouts.app')

@section('content')
<?php
$published = 0;
$drafts = 0;
foreach ($pages as $item) {
    if ($item['status'] === 'published') {
        $published++;
    } else {
        $drafts++;
    }
}
?>
<div class="page-head">
    <div>
        <p class="eyebrow">Content</p>
        <h1>CMS pages</h1>
        <p><?= $published ?> published · <?= $drafts ?> draft · managed at <span class="cell-muted">/p/{slug}</span></p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-primary" href="<?= e(url('/admin/pages/create')) ?>"><?= icon('plus') ?> New page</a>
    </div>
</div>

<?php if ($pages): ?>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Page</th>
                    <th>Slug</th>
                    <th>Footer</th>
                    <th>Updated</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $page): ?>
                    <tr>
                        <td>
                            <div class="table-main">
                                <strong><?= e($page['title']) ?></strong>
                                <small><?= e(truncate($page['excerpt'] ?: strip_tags($page['content']), 70)) ?></small>
                            </div>
                        </td>
                        <td class="cell-muted">/p/<?= e($page['slug']) ?></td>
                        <td><?= $page['show_in_footer'] ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-muted">No</span>' ?></td>
                        <td class="cell-muted"><?= e(format_date($page['updated_at'], 'M j, Y')) ?></td>
                        <td><?= status_badge($page['status']) ?></td>
                        <td class="text-right">
                            <div class="inline-actions">
                                <a class="icon-btn" href="<?= e(url('/p/' . $page['slug'])) ?>" target="_blank" rel="noopener" title="View page"><?= icon('eye') ?></a>
                                <a class="icon-btn" href="<?= e(url('/admin/pages/' . $page['id'] . '/edit')) ?>" title="Edit page"><?= icon('edit') ?></a>
                                <form method="post" action="<?= e(url('/admin/pages/' . $page['id'] . '/delete')) ?>" data-confirm="Delete this page permanently?">
                                    <?= csrf_field() ?>
                                    <button class="icon-btn danger-text" type="submit" title="Delete page"><?= icon('trash') ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="panel">
        <div class="empty-state">
            <?= icon('file') ?>
            <h3>No pages yet</h3>
            <p>Create informational pages such as terms, privacy, or service guides.</p>
            <a class="btn btn-primary btn-sm" href="<?= e(url('/admin/pages/create')) ?>"><?= icon('plus') ?> New page</a>
        </div>
    </div>
<?php endif; ?>

@endsection
