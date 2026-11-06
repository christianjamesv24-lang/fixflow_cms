<?php $hasMore = $currentPage < $totalPages; ?>
<div class="page-head">
    <div>
        <p class="eyebrow">System</p>
        <h1>Activity log</h1>
        <p><?= number_format($total) ?> audit entr<?= $total === 1 ? 'y' : 'ies' ?> · showing page <?= $currentPage ?> of <?= $totalPages ?>.</p>
    </div>
    <div class="page-head-actions">
        <span class="badge badge-muted">Most recent first</span>
    </div>
</div>

<?php if ($logs) { ?>
    <div class="panel">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log) { ?>
                        <tr>
                            <td>
                                <div class="cell-strong"><?= e(format_date($log['created_at'], 'M j, Y')) ?></div>
                                <div class="cell-muted"><?= e(format_time($log['created_at'])) ?></div>
                            </td>
                            <td>
                                <?php if ($log['user_name']) { ?>
                                    <div class="table-user">
                                        <span class="avatar avatar-xs"><?= e(initials($log['user_name'])) ?></span>
                                        <span>
                                            <strong><?= e($log['user_name']) ?></strong>
                                            <small><?= e(ucfirst($log['user_role'])) ?></small>
                                        </span>
                                    </div>
                                <?php } else { ?>
                                    <span class="text-muted">System</span>
                                <?php } ?>
                            </td>
                            <td><span class="badge badge-info"><?= e(str_replace('_', ' ', $log['action'])) ?></span></td>
                            <td><?= e($log['description'] ?: '—') ?></td>
                            <td class="cell-muted"><?= e($log['ip_address'] ?: '—') ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php require BASE_PATH.'/app/Views/partials/pagination.php'; ?>
<?php } else { ?>
    <div class="panel">
        <div class="empty-state">
            <?= icon('activity') ?>
            <h3>No activity yet</h3>
            <p>Actions by administrators, technicians, and customers will be recorded here.</p>
        </div>
    </div>
<?php } ?>
