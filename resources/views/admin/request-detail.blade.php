@extends('layouts.app')

@section('content')
<?php
$statusStep = [
    'pending' => 'Request received',
    'assigned' => 'Technician assigned',
    'accepted' => 'Visit confirmed',
    'in_progress' => 'Repair in progress',
    'completed' => 'Service completed',
    'cancelled' => 'Request cancelled',
];
$statusLabels = ['pending' => 'Pending', 'assigned' => 'Assigned', 'accepted' => 'Accepted', 'in_progress' => 'In progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
$isClosed = in_array($request['status'], ['completed', 'cancelled'], true);
$defaultDate = old('scheduled_date', $request['preferred_date'] ?: date('Y-m-d'));
$defaultTime = old('scheduled_time', $request['preferred_time'] ? substr($request['preferred_time'], 0, 5) : '09:00');
?>
<div class="page-head">
    <div>
        <nav class="breadcrumb" style="margin-bottom:8px">
            <a href="<?= e(url('/admin/requests')) ?>">Service requests</a>
            <?= icon('chevron-right') ?>
            <span><?= e($request['ticket_no']) ?></span>
        </nav>
        <h1><?= e($request['title']) ?></h1>
        <p><?= e($request['customer_name']) ?> · <?= e($request['category_name']) ?> · Created <?= e(format_date($request['created_at'], 'M j, Y')) ?></p>
    </div>
    <div class="page-head-actions">
        <?= status_badge($request['status']) ?>
        <?= status_badge($request['priority']) ?>
        <?php if ($request['customer_phone']): ?>
            <a class="btn btn-ghost btn-sm" href="tel:<?= e($request['customer_phone']) ?>"><?= icon('phone') ?> Call</a>
        <?php endif; ?>
    </div>
</div>

<div class="detail-layout">
    <div class="dash-stack">
        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Request details</h2>
                    <p class="panel-sub">Ticket <?= e($request['ticket_no']) ?></p>
                </div>
                <span class="badge badge-muted"><?= e(ucfirst($request['service_type'])) ?></span>
            </div>
            <div class="panel-body">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Appliance</span>
                        <span class="info-value"><strong><?= e($request['category_name']) ?></strong></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Base service price</span>
                        <span class="info-value"><?= e(money($request['base_price'])) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Brand / model</span>
                        <span class="info-value"><?= e(trim(($request['brand'] ?: '—') . ' / ' . ($request['model'] ?: '—'), ' /')) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Serial number</span>
                        <span class="info-value"><?= e($request['serial_no'] ?: '—') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Preferred visit</span>
                        <span class="info-value"><?= e($request['preferred_date'] ? format_date($request['preferred_date']) : 'Flexible') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Preferred window</span>
                        <span class="info-value"><?= e($request['preferred_time'] ?: 'Any time') ?></span>
                    </div>
                    <div class="info-item" style="grid-column:1/-1">
                        <span class="info-label">Service address</span>
                        <span class="info-value"><?= e($request['address']) ?></span>
                    </div>
                </div>
                <div class="divider"></div>
                <span class="info-label">Reported problem</span>
                <p class="detail-desc"><?= e($request['description']) ?></p>

                <div class="divider"></div>
                <div class="info-grid info-grid-3">
                    <div class="info-item">
                        <span class="info-label">Estimated cost</span>
                        <span class="info-value"><strong><?= e(money($request['estimated_cost'])) ?></strong></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Final cost</span>
                        <span class="info-value"><strong><?= e(money($request['final_cost'])) ?></strong></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Assigned</span>
                        <span class="info-value"><?= e($request['technician_name'] ?: 'Unassigned') ?></span>
                    </div>
                </div>
            </div>
        </section>

        <?php if ($report): ?>
            <section class="panel">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">Service report</h2>
                        <p class="panel-sub">Filed <?= e(format_date($report['created_at'], 'M j, Y')) ?></p>
                    </div>
                    <?= status_badge('completed') ?>
                </div>
                <div class="panel-body stack">
                    <div>
                        <span class="info-label">Diagnosis</span>
                        <p class="detail-desc"><?= e($report['diagnosis']) ?></p>
                    </div>
                    <div>
                        <span class="info-label">Work performed</span>
                        <p class="detail-desc"><?= e($report['work_performed']) ?></p>
                    </div>
                    <div class="info-grid info-grid-3">
                        <div class="info-item">
                            <span class="info-label">Parts used</span>
                            <span class="info-value"><?= e($report['parts_used'] ?: 'None') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Labor hours</span>
                            <span class="info-value"><?= $report['labor_hours'] !== null ? e($report['labor_hours']) . ' hr' : '—' ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Final cost</span>
                            <span class="info-value"><strong><?= e(money($request['final_cost'])) ?></strong></span>
                        </div>
                    </div>
                    <?php if ($report['recommendations']): ?>
                        <div>
                            <span class="info-label">Recommendations</span>
                            <p class="detail-desc"><?= e($report['recommendations']) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($feedback): ?>
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Customer feedback</h2>
                </div>
                <div class="panel-body">
                    <div class="testimonial" style="border:none;padding:0">
                        <?= rating_stars($feedback['rating']) ?>
                        <blockquote><?= e($feedback['comment'] ?: 'No comment provided.') ?></blockquote>
                        <figcaption>
                            <span class="avatar avatar-xs"><?= e(initials($request['customer_name'])) ?></span>
                            <span><strong><?= e($request['customer_name']) ?></strong><small><?= e(format_date($feedback['created_at'], 'M j, Y')) ?></small></span>
                        </figcaption>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Status timeline</h2>
                    <p class="panel-sub">Full audit trail for this ticket.</p>
                </div>
            </div>
            <div class="panel-body">
                <div class="timeline">
                    <?php foreach ($history as $index => $event): ?>
                        <?php
                        $isLast = $index === count($history) - 1;
                        $toneClass = $event['status'] === 'cancelled' ? 'is-danger' : ($isLast ? 'is-current' : 'is-done');
                        ?>
                        <div class="timeline-item <?= $toneClass ?>">
                            <span class="timeline-dot"><?= icon($event['status'] === 'completed' ? 'check' : ($event['status'] === 'cancelled' ? 'x' : 'clock')) ?></span>
                            <div class="timeline-body">
                                <strong><?= e($statusStep[$event['status']] ?? ucfirst(str_replace('_', ' ', $event['status']))) ?></strong>
                                <?php if ($event['note']): ?><p><?= e($event['note']) ?></p><?php endif; ?>
                                <div class="timeline-meta">
                                    <span><?= e(format_datetime($event['created_at'])) ?></span>
                                    <?php if ($event['changed_by_name']): ?><span>· <?= e($event['changed_by_name']) ?> (<?= e($event['changed_by_role']) ?>)</span><?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </div>

    <div class="sticky-col">
        <section class="panel">
            <div class="panel-body">
                <div class="flex-between">
                    <span class="service-icon"><?= icon($request['category_icon']) ?></span>
                    <?= status_badge($request['status']) ?>
                </div>
                <h2 style="font-size:1.15rem;margin:14px 0 4px"><?= e($request['category_name']) ?></h2>
                <p class="text-muted text-sm" style="margin:0"><?= e($request['ticket_no']) ?> · <?= e(ucfirst($request['service_type'])) ?></p>

                <div class="divider"></div>

                <?php if (!$isClosed): ?>
                    <form method="post" action="<?= e(url('/admin/requests/' . $request['id'] . '/assign')) ?>" class="stack-sm">
                        <?= csrf_field() ?>
                        <div class="field">
                            <label for="technician_id">Assign technician</label>
                            <select id="technician_id" name="technician_id" required>
                                <option value="">Choose a technician</option>
                                <?php foreach ($technicians as $technician): ?>
                                    <option value="<?= (int) $technician['id'] ?>" <?= (int) $request['technician_id'] === (int) $technician['id'] ? 'selected' : '' ?>>
                                        <?= e($technician['name']) ?><?= $technician['specialty'] ? ' · ' . e($technician['specialty']) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="btn btn-primary btn-block btn-sm" type="submit"><?= icon('user') ?> <?= $request['technician_id'] ? 'Reassign' : 'Assign' ?> job</button>
                    </form>
                <?php else: ?>
                    <p class="text-sm text-muted" style="margin:0">This ticket is closed and can no longer be assigned or scheduled.</p>
                <?php endif; ?>

                <div class="divider"></div>

                <form method="post" action="<?= e(url('/admin/requests/' . $request['id'] . '/status')) ?>" class="stack-sm">
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="status">Update status</label>
                        <select id="status" name="status" required>
                            <?php foreach ($statusLabels as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= $request['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="note">Note (optional)</label>
                        <input type="text" id="note" name="note" placeholder="Reason or comment for the log">
                    </div>
                    <button class="btn btn-soft btn-block btn-sm" type="submit"><?= icon('refresh') ?> Save status</button>
                </form>

                <div class="divider"></div>

                <form method="post" action="<?= e(url('/admin/requests/' . $request['id'] . '/cost')) ?>" class="stack-sm">
                    <?= csrf_field() ?>
                    <div class="form-row">
                        <div class="field">
                            <label for="estimated_cost">Estimate (₱)</label>
                            <input type="number" step="0.01" min="0" id="estimated_cost" name="estimated_cost" value="<?= e(old('estimated_cost', $request['estimated_cost'])) ?>" class="<?= isset($errors['estimated_cost']) ? 'is-invalid' : '' ?>">
                            <?php if (isset($errors['estimated_cost'])): ?><span class="field-error"><?= e($errors['estimated_cost']) ?></span><?php endif; ?>
                        </div>
                        <div class="field">
                            <label for="final_cost">Final (₱)</label>
                            <input type="number" step="0.01" min="0" id="final_cost" name="final_cost" value="<?= e(old('final_cost', $request['final_cost'])) ?>" class="<?= isset($errors['final_cost']) ? 'is-invalid' : '' ?>">
                            <?php if (isset($errors['final_cost'])): ?><span class="field-error"><?= e($errors['final_cost']) ?></span><?php endif; ?>
                        </div>
                    </div>
                    <button class="btn btn-soft btn-block btn-sm" type="submit"><?= icon('credit-card') ?> Save costs</button>
                </form>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Schedule a visit</h2>
                    <p class="panel-sub"><?= e($request['technician_name'] ?: 'Assign a technician first') ?></p>
                </div>
            </div>
            <div class="panel-body">
                <?php if (!$isClosed): ?>
                    <form method="post" action="<?= e(url('/admin/requests/' . $request['id'] . '/schedule')) ?>" class="stack-sm">
                        <?= csrf_field() ?>
                        <div class="field">
                            <label for="scheduled_date">Date *</label>
                            <input type="date" id="scheduled_date" name="scheduled_date" value="<?= e($defaultDate) ?>" class="<?= isset($errors['scheduled_date']) ? 'is-invalid' : '' ?>" required>
                            <?php if (isset($errors['scheduled_date'])): ?><span class="field-error"><?= e($errors['scheduled_date']) ?></span><?php endif; ?>
                        </div>
                        <div class="form-row">
                            <div class="field">
                                <label for="scheduled_time">Time *</label>
                                <input type="time" id="scheduled_time" name="scheduled_time" value="<?= e($defaultTime) ?>" class="<?= isset($errors['scheduled_time']) ? 'is-invalid' : '' ?>" required>
                                <?php if (isset($errors['scheduled_time'])): ?><span class="field-error"><?= e($errors['scheduled_time']) ?></span><?php endif; ?>
                            </div>
                            <div class="field">
                                <label for="duration_minutes">Minutes</label>
                                <input type="number" step="15" min="15" max="480" id="duration_minutes" name="duration_minutes" value="<?= e(old('duration_minutes', '90')) ?>" class="<?= isset($errors['duration_minutes']) ? 'is-invalid' : '' ?>">
                                <?php if (isset($errors['duration_minutes'])): ?><span class="field-error"><?= e($errors['duration_minutes']) ?></span><?php endif; ?>
                            </div>
                        </div>
                        <div class="field">
                            <label for="schedule_technician">Technician *</label>
                            <select id="schedule_technician" name="technician_id" required>
                                <option value="">Choose a technician</option>
                                <?php foreach ($technicians as $technician): ?>
                                    <option value="<?= (int) $technician['id'] ?>" <?= (int) $request['technician_id'] === (int) $technician['id'] ? 'selected' : '' ?>><?= e($technician['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="notes">Visit notes</label>
                            <input type="text" id="notes" name="notes" value="<?= e(old('notes')) ?>" placeholder="Gate code, landmark, reminders">
                        </div>
                        <button class="btn btn-primary btn-block btn-sm" type="submit"><?= icon('calendar') ?> Book appointment</button>
                    </form>
                <?php else: ?>
                    <p class="text-sm text-muted" style="margin:0">Closed tickets cannot be rescheduled.</p>
                <?php endif; ?>

                <?php if ($appointments): ?>
                    <div class="divider"></div>
                    <div class="stack-sm">
                        <?php foreach ($appointments as $appointment): ?>
                            <div class="mini-item" style="padding:10px 0;border:none">
                                <span class="mini-icon"><?= icon('calendar') ?></span>
                                <div class="mini-main">
                                    <strong><?= e(format_date($appointment['scheduled_date'], 'M j, Y')) ?> · <?= e(format_time($appointment['scheduled_time'])) ?></strong>
                                    <small><?= e($appointment['technician_name'] ?: 'Unassigned') ?><?= $appointment['notes'] ? ' · ' . e(truncate($appointment['notes'], 40)) : '' ?></small>
                                </div>
                                <span class="mini-meta"><?= status_badge($appointment['status']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <h2 class="panel-title">Customer</h2>
            </div>
            <div class="panel-body">
                <div class="table-user">
                    <span class="avatar"><?= e(initials($request['customer_name'])) ?></span>
                    <span>
                        <strong><?= e($request['customer_name']) ?></strong>
                        <small><?= e($request['customer_phone'] ?: 'No phone on file') ?></small>
                    </span>
                </div>
                <div class="stack-sm mt-2">
                    <span class="text-sm text-muted"><?= icon('mail') ?> <?= e($request['customer_email']) ?></span>
                    <span class="text-sm text-muted"><?= icon('pin') ?> <?= e($request['customer_address'] ?: $request['address']) ?></span>
                </div>
                <?php if ($request['technician_name']): ?>
                    <div class="divider"></div>
                    <span class="info-label">Assigned technician</span>
                    <div class="table-user mt-1">
                        <span class="avatar avatar-sm" style="background: <?= e($request['technician_color'] ?: '#2563eb') ?>"><?= e(initials($request['technician_name'])) ?></span>
                        <span>
                            <strong><?= e($request['technician_name']) ?></strong>
                            <small><?= e($request['technician_specialty'] ?: 'Field technician') ?><?= $request['technician_phone'] ? ' · ' . e($request['technician_phone']) : '' ?></small>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

@endsection
