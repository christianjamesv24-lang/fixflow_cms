<?php $hasFilters = $filters['q'] !== '' || $filters['status'] !== ''; ?>
<div class="page-head">
    <div>
        <p class="eyebrow">People</p>
        <h1>Technicians</h1>
        <p><?= count($technicians) ?> technician<?= count($technicians) === 1 ? '' : 's' ?> in this view.</p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-primary" href="<?= e(url('/admin/technicians/create')) ?>"><?= icon('plus') ?> Add technician</a>
    </div>
</div>

<form class="filter-bar" method="get" action="<?= e(url('/admin/technicians')) ?>">
    <div class="field field-grow">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="Name, email, or specialty">
    </div>
    <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">All statuses</option>
            <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
    </div>
    <button class="btn btn-primary btn-sm" type="submit"><?= icon('search') ?> Search</button>
    <?php if ($hasFilters) { ?>
        <a class="filter-reset" href="<?= e(url('/admin/technicians')) ?>">Reset</a>
    <?php } ?>
</form>

<?php if ($technicians) { ?>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Technician</th>
                    <th>Specialty</th>
                    <th>Active jobs</th>
                    <th>Completed</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($technicians as $technician) { ?>
                    <tr>
                        <td>
                            <div class="table-user">
                                <span class="avatar" style="background: <?= e($technician['avatar_color'] ?: '#2563eb') ?>"><?= e(initials($technician['name'])) ?></span>
                                <span>
                                    <strong><?= e($technician['name']) ?></strong>
                                    <small><?= e($technician['email']) ?><?= $technician['phone'] ? ' · '.e($technician['phone']) : '' ?></small>
                                </span>
                            </div>
                        </td>
                        <td><?= e($technician['specialty'] ?: 'General') ?></td>
                        <td><?= (int) $technician['active_jobs'] ?></td>
                        <td><?= (int) $technician['completed'] ?> / <?= (int) $technician['jobs'] ?></td>
                        <td>
                            <?php if ($technician['rating']) { ?>
                                <span class="cell-strong"><?= number_format((float) $technician['rating'], 1) ?></span>
                            <?php } else { ?>
                                <span class="text-muted">—</span>
                            <?php } ?>
                        </td>
                        <td><?= status_badge($technician['status']) ?></td>
                        <td class="text-right">
                            <div class="inline-actions">
                                <a class="icon-btn" href="<?= e(url('/admin/requests?technician='.$technician['id'])) ?>" title="View jobs"><?= icon('clipboard') ?></a>
                                <a class="icon-btn" href="<?= e(url('/admin/technicians/'.$technician['id'].'/edit')) ?>" title="Edit technician"><?= icon('edit') ?></a>
                                <form method="post" action="<?= e(url('/admin/technicians/'.$technician['id'].'/delete')) ?>" data-confirm="<?= $technician['status'] === 'active' ? 'Deactivate' : 'Activate' ?> <?= e($technician['name']) ?>?">
                                    <?= csrf_field() ?>
                                    <button class="icon-btn <?= $technician['status'] === 'active' ? 'danger-text' : '' ?>" type="submit" title="<?= $technician['status'] === 'active' ? 'Deactivate' : 'Activate' ?>">
                                        <?= icon($technician['status'] === 'active' ? 'x-circle' : 'check-circle') ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
<?php } else { ?>
    <div class="panel">
        <div class="empty-state">
            <?= icon('users') ?>
            <h3>No technicians found</h3>
            <p><?= $hasFilters ? 'Try a different search or status filter.' : 'Add your first technician to start assigning jobs.' ?></p>
            <a class="btn btn-primary btn-sm" href="<?= e(url('/admin/technicians/create')) ?>"><?= icon('plus') ?> Add technician</a>
        </div>
    </div>
<?php } ?>
