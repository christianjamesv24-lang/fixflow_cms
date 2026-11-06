@extends('layouts.app')

@section('content')
<?php $avgPrice = $categories ? array_sum(array_column($categories, 'base_price')) / count($categories) : 0; ?>
<div class="page-head">
    <div>
        <p class="eyebrow">Content</p>
        <h1>Service catalog</h1>
        <p><?= count($categories) ?> appliance categories · average base price <?= e(money($avgPrice)) ?>.</p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-ghost" href="<?= e(url('/services')) ?>" target="_blank" rel="noopener"><?= icon('external') ?> View public page</a>
        <a class="btn btn-primary" href="<?= e(url('/admin/services/create')) ?>"><?= icon('plus') ?> Add category</a>
    </div>
</div>

<?php if ($categories): ?>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Slug</th>
                    <th>Base price</th>
                    <th>Requests</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td>
                            <div class="table-user">
                                <span class="service-icon" style="width:38px;height:38px;border-radius:11px"><?= icon($category['icon']) ?></span>
                                <span>
                                    <strong><?= e($category['name']) ?></strong>
                                    <small><?= e(truncate($category['short_description'] ?: $category['description'] ?: 'No description', 60)) ?></small>
                                </span>
                            </div>
                        </td>
                        <td class="cell-muted">/services/<?= e($category['slug']) ?></td>
                        <td class="cell-money"><?= e(money($category['base_price'])) ?></td>
                        <td><?= (int) $category['request_count'] ?></td>
                        <td><?= status_badge($category['status']) ?></td>
                        <td class="text-right">
                            <div class="inline-actions">
                                <a class="icon-btn" href="<?= e(url('/services/' . $category['slug'])) ?>" target="_blank" rel="noopener" title="View service page"><?= icon('eye') ?></a>
                                <a class="icon-btn" href="<?= e(url('/admin/services/' . $category['id'] . '/edit')) ?>" title="Edit category"><?= icon('edit') ?></a>
                                <form method="post" action="<?= e(url('/admin/services/' . $category['id'] . '/delete')) ?>" data-confirm="Delete this service category? Categories with requests will be deactivated instead.">
                                    <?= csrf_field() ?>
                                    <button class="icon-btn danger-text" type="submit" title="Delete category"><?= icon('trash') ?></button>
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
            <?= icon('wrench') ?>
            <h3>No service categories</h3>
            <p>Add appliance categories so customers can submit requests.</p>
            <a class="btn btn-primary btn-sm" href="<?= e(url('/admin/services/create')) ?>"><?= icon('plus') ?> Add category</a>
        </div>
    </div>
<?php endif; ?>

@endsection
