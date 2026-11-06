@extends('layouts.app')

@section('content')
<?php
$average = round($average, 1);
$totalStars = max(1, $total);
?>
<div class="page-head">
    <div>
        <p class="eyebrow">People</p>
        <h1>Customer feedback</h1>
        <p><?= number_format($total) ?> review<?= $total === 1 ? '' : 's' ?> across completed jobs.</p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-ghost" href="<?= e(url('/admin/reports')) ?>"><?= icon('chart') ?> Reports</a>
    </div>
</div>

<div class="dash-grid">
    <div class="dash-stack">
        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">All reviews</h2>
                    <p class="panel-sub">Sorted by most recent.</p>
                </div>
            </div>
            <?php if ($rows): ?>
                <div>
                    <?php foreach ($rows as $row): ?>
                        <article style="border-bottom:1px solid var(--line);padding:16px 20px">
                            <div class="flex-between" style="align-items:flex-start;gap:12px">
                                <div class="table-user">
                                    <span class="avatar" style="background: <?= e($row['avatar_color'] ?: '#2563eb') ?>"><?= e(initials($row['customer_name'])) ?></span>
                                    <span>
                                        <strong><?= e($row['customer_name']) ?></strong>
                                        <small>Job <?= e($row['ticket_no']) ?> · <?= e(format_date($row['created_at'], 'M j, Y')) ?></small>
                                        <small>Technician: <?= e($row['technician_name'] ?: 'Unassigned') ?></small>
                                    </span>
                                </div>
                                <div class="stack-sm" style="align-items:flex-end;text-align:right">
                                    <div class="rating-summary" style="align-items:flex-end">
                                        <?= rating_stars($row['rating']) ?>
                                        <span><?= number_format((float) $row['rating'], 1) ?>/5</span>
                                    </div>
                                    <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/requests/' . $row['request_id'])) ?>">View ticket</a>
                                </div>
                            </div>
                            <blockquote class="detail-desc" style="margin:12px 0 0;font-style:italic"><?= e($row['comment'] ?: 'No comment provided.') ?></blockquote>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <?= icon('star') ?>
                    <h3>No feedback yet</h3>
                    <p>Customer reviews will appear here after completed jobs are rated.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <div class="dash-stack">
        <section class="panel">
            <div class="panel-head">
                <h2 class="panel-title">Rating summary</h2>
            </div>
            <div class="panel-body">
                <div class="rating-summary" style="align-items:center;text-align:center;margin:0 auto 14px;float:none">
                    <div style="font-size:2.4rem;font-weight:800;line-height:1;letter-spacing:-.04em"><?= $average ?></div>
                    <div><?= rating_stars($average) ?></div>
                    <span><?= number_format($total) ?> reviews</span>
                </div>
                <div class="rating-breakdown">
                    <?php for ($r = 5; $r >= 1; $r--): ?>
                        <?php $count = $distribution[$r] ?? 0; ?>
                        <div class="rating-row">
                            <span><?= $r ?> ★</span>
                            <div class="rating-track">
                                <div class="rating-fill" style="width: <?= max(2, (int) round(($count / $totalStars) * 100)) ?>%"></div>
                            </div>
                            <span><?= $count ?></span>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Top technicians</h2>
                    <p class="panel-sub">By average rating.</p>
                </div>
            </div>
            <div class="mini-list">
                <?php foreach ($technicians as $tech): ?>
                    <a class="mini-item" href="<?= e(url('/admin/requests?technician=' . $tech['id'])) ?>">
                        <span class="avatar avatar-sm" style="background: <?= e($tech['avatar_color'] ?: '#2563eb') ?>"><?= e(initials($tech['name'])) ?></span>
                        <div class="mini-main">
                            <strong><?= e($tech['name']) ?></strong>
                            <small><?= (int) $tech['reviews'] ?> review<?= (int) $tech['reviews'] === 1 ? '' : 's' ?></small>
                        </div>
                        <span class="mini-meta"><?= number_format((float) $tech['rating'], 1) ?> ★</span>
                    </a>
                <?php endforeach; ?>
                <?php if (!$technicians): ?>
                    <div class="empty-state" style="padding:24px 20px">
                        <h3>No ratings yet</h3>
                        <p>Top technicians will appear here once reviews come in.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

@endsection
