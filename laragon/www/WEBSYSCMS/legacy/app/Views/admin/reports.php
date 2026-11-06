<?php
$statusLabels = ['pending' => 'Pending', 'assigned' => 'Assigned', 'accepted' => 'Accepted', 'in_progress' => 'In progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
$completionRate = $totalJobs > 0 ? round(($completedJobs / $totalJobs) * 100) : 0;
$avgTicket = $completedJobs > 0 ? $revenue / $completedJobs : 0;
$exportQuery = http_build_query(['from' => $filters['from'], 'to' => $filters['to']]);
?>
<div class="page-head">
    <div>
        <p class="eyebrow">Analytics</p>
        <h1>Reports</h1>
        <p><?= e(format_date($filters['from'], 'M j, Y')) ?> to <?= e(format_date($filters['to'], 'M j, Y')) ?>.</p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-primary" href="<?= e(url('/admin/reports/export?'.$exportQuery)) ?>"><?= icon('download') ?> Export CSV</a>
    </div>
</div>

<form class="filter-bar" method="get" action="<?= e(url('/admin/reports')) ?>">
    <div class="field">
        <label for="from">From</label>
        <input type="date" id="from" name="from" value="<?= e($filters['from']) ?>">
    </div>
    <div class="field">
        <label for="to">To</label>
        <input type="date" id="to" name="to" value="<?= e($filters['to']) ?>">
    </div>
    <button class="btn btn-primary btn-sm" type="submit"><?= icon('filter') ?> Update report</button>
    <a class="filter-reset" href="<?= e(url('/admin/reports')) ?>">Reset to last 6 months</a>
</form>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon"><?= icon('clipboard') ?></span>
            <span class="stat-trend"><?= $completionRate ?>% completion</span>
        </div>
        <div>
            <div class="stat-value"><?= number_format($totalJobs) ?></div>
            <div class="stat-label">Requests received</div>
        </div>
        <div class="stat-foot"><?= $completedJobs ?> completed · <?= $cancelledJobs ?> cancelled</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon tone-success"><?= icon('credit-card') ?></span>
        </div>
        <div>
            <div class="stat-value"><?= e(money($revenue)) ?></div>
            <div class="stat-label">Revenue collected</div>
        </div>
        <div class="stat-foot">Average <?= e(money($avgTicket)) ?> per completed job.</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon tone-info"><?= icon('star') ?></span>
            <span class="stat-trend"><?= $reviewCount ?> reviews</span>
        </div>
        <div>
            <div class="stat-value"><?= $avgRating > 0 ? number_format($avgRating, 1) : '—' ?></div>
            <div class="stat-label">Average rating</div>
        </div>
        <div class="stat-foot"><?= rating_stars($avgRating ?: 0) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon tone-warn"><?= icon('users') ?></span>
        </div>
        <div>
            <div class="stat-value"><?= count($technicianPerf) ?></div>
            <div class="stat-label">Technicians active</div>
        </div>
        <div class="stat-foot"><?= count($categoryPerf) ?> service categories booked.</div>
    </div>
</div>

<div class="dash-grid">
    <div class="dash-stack">
        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Monthly request trend</h2>
                    <p class="panel-sub">Requests created in the selected period.</p>
                </div>
            </div>
            <div class="panel-body">
                <?php if ($trend) { ?>
                    <div class="chart-bars">
                        <?php foreach ($trend as $point) { ?>
                            <?php $height = max(4, (int) round(($point['value'] / $trendMax) * 150)); ?>
                            <div class="chart-col">
                                <div class="chart-col-bar" style="height: <?= $height ?>px"><span><?= $point['value'] ?></span></div>
                                <div class="chart-col-label"><?= e($point['label']) ?></div>
                            </div>
                        <?php } ?>
                    </div>
                <?php } else { ?>
                    <div class="empty-state" style="padding:24px 0">
                        <h3>No requests in this range</h3>
                        <p>Adjust the date filters to see data.</p>
                    </div>
                <?php } ?>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Technician performance</h2>
                    <p class="panel-sub">Jobs, revenue, and satisfaction.</p>
                </div>
            </div>
            <?php if ($technicianPerf) { ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Technician</th>
                                <th>Jobs</th>
                                <th>Completed</th>
                                <th>Revenue</th>
                                <th>Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($technicianPerf as $tech) { ?>
                                <tr>
                                    <td>
                                        <div class="table-user">
                                            <span class="avatar avatar-xs" style="background: <?= e($tech['avatar_color'] ?: '#2563eb') ?>"><?= e(initials($tech['name'])) ?></span>
                                            <span><strong><?= e($tech['name']) ?></strong></span>
                                        </div>
                                    </td>
                                    <td><?= (int) $tech['jobs'] ?></td>
                                    <td><?= (int) $tech['completed'] ?></td>
                                    <td class="cell-money"><?= e(money($tech['revenue'])) ?></td>
                                    <td><?= $tech['rating'] > 0 ? number_format((float) $tech['rating'], 1).' ★' : '<span class="text-muted">—</span>' ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } else { ?>
                <div class="empty-state">
                    <?= icon('users') ?>
                    <h3>No technician data</h3>
                    <p>Technician performance will appear once jobs are assigned.</p>
                </div>
            <?php } ?>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Top customers</h2>
                    <p class="panel-sub">By completed spend in this period.</p>
                </div>
            </div>
            <?php if ($topCustomers) { ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Requests</th>
                                <th>Spend</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topCustomers as $customer) { ?>
                                <tr>
                                    <td><?= e($customer['name']) ?></td>
                                    <td><?= (int) $customer['jobs'] ?></td>
                                    <td class="cell-money"><?= e(money($customer['spend'])) ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } else { ?>
                <div class="empty-state" style="padding:28px 20px">
                    <h3>No customer activity</h3>
                    <p>No requests were filed in this period.</p>
                </div>
            <?php } ?>
        </section>
    </div>

    <div class="dash-stack">
        <section class="panel">
            <div class="panel-head">
                <h2 class="panel-title">Status breakdown</h2>
            </div>
            <div class="panel-body">
                <div class="bar-list">
                    <?php foreach ($statusCounts as $status => $count) { ?>
                        <div class="bar-row">
                            <div class="bar-row-label">
                                <span><?= e($statusLabels[$status] ?? ucfirst($status)) ?></span>
                                <span><?= $count ?></span>
                            </div>
                            <div class="bar-track">
                                <span class="bar-fill tone-<?= ['pending' => 'warn', 'assigned' => 'info', 'accepted' => 'info', 'in_progress' => 'info', 'completed' => 'success', 'cancelled' => 'danger'][$status] ?? 'muted' ?>" style="width: <?= max(3, (int) round(($count / $statusMax) * 100)) ?>%"></span>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Category performance</h2>
                    <p class="panel-sub">Requests and revenue per appliance.</p>
                </div>
            </div>
            <div class="panel-body">
                <?php if ($categoryPerf) { ?>
                    <div class="bar-list">
                        <?php foreach ($categoryPerf as $category) { ?>
                            <div class="bar-row">
                                <div class="bar-row-label">
                                    <span><?= e($category['name']) ?></span>
                                    <span><?= (int) $category['jobs'] ?> · <?= e(money($category['revenue'])) ?></span>
                                </div>
                                <div class="bar-track">
                                    <span class="bar-fill" style="width: <?= max(3, (int) round(($category['jobs'] / $categoryMax) * 100)) ?>%"></span>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php } else { ?>
                    <p class="text-sm text-muted" style="margin:0">No category activity in this period.</p>
                <?php } ?>
            </div>
        </section>

        <div class="note-box">
            <?= icon('info') ?>
            <p><strong>Export ready.</strong> The CSV includes every request in the selected range with costs, technician, and status for accounting.</p>
        </div>
    </div>
</div>
