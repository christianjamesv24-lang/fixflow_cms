<?php
$statusStep = [
    'pending' => 'Request received',
    'assigned' => 'Technician assigned',
    'accepted' => 'Visit confirmed',
    'in_progress' => 'Repair in progress',
    'completed' => 'Service completed',
    'cancelled' => 'Request cancelled',
];
$canCancel = in_array($request['status'], ['pending', 'assigned', 'accepted'], true);
?>
<div class="page-head">
    <div>
        <nav class="breadcrumb" style="margin-bottom:8px">
            <a href="<?= e(url('/customer/requests')) ?>">My requests</a>
            <?= icon('chevron-right') ?>
            <span><?= e($request['ticket_no']) ?></span>
        </nav>
        <h1><?= e($request['title']) ?></h1>
        <p><?= e($request['category_name']) ?> · Created <?= e(format_date($request['created_at'], 'M j, Y')) ?></p>
    </div>
    <div class="page-head-actions">
        <?= status_badge($request['status']) ?>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/customer/requests')) ?>"><?= icon('arrow-left') ?> All requests</a>
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
                        <span class="info-label">Priority</span>
                        <span class="info-value"><?= status_badge($request['priority']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Brand / model</span>
                        <span class="info-value"><?= e(trim(($request['brand'] ?: '—').' / '.($request['model'] ?: '—'), ' /')) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Serial number</span>
                        <span class="info-value"><?= e($request['serial_no'] ?: '—') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Preferred date</span>
                        <span class="info-value"><?= e($request['preferred_date'] ? format_date($request['preferred_date']) : 'Flexible') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Preferred window</span>
                        <span class="info-value"><?= e($request['preferred_time'] ?: 'Any time') ?></span>
                    </div>
                    <div class="info-item" style="grid-column: 1 / -1">
                        <span class="info-label">Service address</span>
                        <span class="info-value"><?= e($request['address']) ?></span>
                    </div>
                </div>

                <div class="divider"></div>
                <span class="info-label">Reported problem</span>
                <p class="detail-desc"><?= e($request['description']) ?></p>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Status timeline</h2>
                    <p class="panel-sub">Every update on this ticket.</p>
                </div>
            </div>
            <div class="panel-body">
                <div class="timeline">
                    <?php foreach ($history as $index => $event) { ?>
                        <?php
                        $isLast = $index === count($history) - 1;
                        $toneClass = $event['status'] === 'cancelled' ? 'is-danger' : ($isLast && in_array($event['status'], ['completed'], true) ? 'is-done' : ($isLast ? 'is-current' : 'is-done'));
                        ?>
                        <div class="timeline-item <?= $toneClass ?>">
                            <span class="timeline-dot"><?= icon($event['status'] === 'completed' ? 'check' : ($event['status'] === 'cancelled' ? 'x' : ($event['status'] === 'in_progress' ? 'wrench' : 'clock'))) ?></span>
                            <div class="timeline-body">
                                <strong><?= e($statusStep[$event['status']] ?? ucfirst(str_replace('_', ' ', $event['status']))) ?></strong>
                                <?php if ($event['note']) { ?><p><?= e($event['note']) ?></p><?php } ?>
                                <div class="timeline-meta">
                                    <span><?= e(format_datetime($event['created_at'])) ?></span>
                                    <?php if ($event['changed_by_name']) { ?><span>· <?= e($event['changed_by_name']) ?></span><?php } ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </section>

        <?php if ($report) { ?>
            <section class="panel">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">Service report</h2>
                        <p class="panel-sub">Submitted by your technician.</p>
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
                            <span class="info-value"><?= $report['labor_hours'] !== null ? e($report['labor_hours']).' hr' : '—' ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Final cost</span>
                            <span class="info-value"><strong><?= e(money($request['final_cost'])) ?></strong></span>
                        </div>
                    </div>
                    <?php if ($report['recommendations']) { ?>
                        <div>
                            <span class="info-label">Recommendations</span>
                            <p class="detail-desc"><?= e($report['recommendations']) ?></p>
                        </div>
                    <?php } ?>
                </div>
            </section>
        <?php } ?>

        <?php if ($request['status'] === 'completed') { ?>
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title"><?= $feedback ? 'Your feedback' : 'Rate this service' ?></h2>
                </div>
                <div class="panel-body">
                    <?php if ($feedback) { ?>
                        <div class="testimonial" style="border:none;padding:0">
                            <?= rating_stars($feedback['rating']) ?>
                            <blockquote><?= e($feedback['comment'] ?: 'No comment provided.') ?></blockquote>
                            <figcaption>
                                <span class="avatar avatar-xs"><?= e(initials($request['customer_name'] ?? 'You')) ?></span>
                                <span><strong>You</strong><small><?= e(format_date($feedback['created_at'], 'M j, Y')) ?></small></span>
                            </figcaption>
                        </div>
                    <?php } else { ?>
                        <form method="post" action="<?= e(url('/customer/requests/'.$request['id'].'/feedback')) ?>" class="form">
                            <?= csrf_field() ?>
                            <div class="field">
                                <label>How was the service?</label>
                                <div class="rating-input">
                                    <?php for ($i = 1; $i <= 5; $i++) { ?>
                                        <label class="rating-star" title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>">
                                            <input type="radio" name="rating" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?> required>
                                            <?= icon('star') ?>
                                        </label>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="field">
                                <label for="comment">Comment (optional)</label>
                                <textarea id="comment" name="comment" rows="3" placeholder="Tell us about the visit, the technician, or anything we can improve."></textarea>
                            </div>
                            <button class="btn btn-primary" type="submit"><?= icon('send') ?> Submit feedback</button>
                        </form>
                    <?php } ?>
                </div>
            </section>
        <?php } ?>
    </div>

    <div class="sticky-col">
        <section class="panel">
            <div class="panel-body">
                <div class="flex-between">
                    <span class="service-icon"><?= icon($request['category_icon']) ?></span>
                    <?= status_badge($request['status']) ?>
                </div>
                <h2 style="font-size:1.15rem;margin:14px 0 4px"><?= e($request['category_name']) ?></h2>
                <p class="text-muted text-sm" style="margin:0"><?= e(ucfirst($request['service_type'])) ?> request</p>

                <div class="divider"></div>

                <div class="stack-sm">
                    <div class="flex-between text-sm">
                        <span class="text-muted">Estimated cost</span>
                        <strong><?= e(money($request['estimated_cost'])) ?></strong>
                    </div>
                    <div class="flex-between text-sm">
                        <span class="text-muted">Final cost</span>
                        <strong><?= e(money($request['final_cost'])) ?></strong>
                    </div>
                    <div class="flex-between text-sm">
                        <span class="text-muted">Assigned technician</span>
                        <strong><?= e($request['technician_name'] ?: 'Pending') ?></strong>
                    </div>
                    <?php if ($request['technician_phone']) { ?>
                        <div class="flex-between text-sm">
                            <span class="text-muted">Technician contact</span>
                            <strong><?= e($request['technician_phone']) ?></strong>
                        </div>
                    <?php } ?>
                </div>

                <?php if ($canCancel) { ?>
                    <div class="divider"></div>
                    <form method="post" action="<?= e(url('/customer/requests/'.$request['id'].'/cancel')) ?>" data-confirm="Cancel this service request? This cannot be undone.">
                        <?= csrf_field() ?>
                        <button class="btn btn-danger-soft btn-block" type="submit"><?= icon('x-circle') ?> Cancel request</button>
                    </form>
                <?php } ?>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <h2 class="panel-title">Appointment</h2>
            </div>
            <div class="panel-body">
                <?php if ($appointment) { ?>
                    <div class="appointment-item" style="padding:0;border:none">
                        <div class="appointment-date">
                            <strong><?= e(format_date($appointment['scheduled_date'], 'd')) ?></strong>
                            <span><?= e(format_date($appointment['scheduled_date'], 'M')) ?></span>
                        </div>
                        <div class="appointment-main">
                            <strong><?= e(format_time($appointment['scheduled_time'])) ?></strong>
                            <small><?= e($appointment['duration_minutes']) ?> minutes</small>
                            <small><?= e($appointment['technician_name'] ?: 'Technician to be confirmed') ?></small>
                        </div>
                    </div>
                    <div class="inline-actions mt-2"><?= status_badge($appointment['status']) ?></div>
                    <?php if ($appointment['notes']) { ?>
                        <p class="text-sm text-muted mt-2" style="margin-bottom:0"><?= e($appointment['notes']) ?></p>
                    <?php } ?>
                <?php } else { ?>
                    <div class="empty-state" style="padding:16px 0">
                        <?= icon('calendar') ?>
                        <h3>No appointment yet</h3>
                        <p>We will add a visit window once a technician is assigned.</p>
                    </div>
                <?php } ?>
            </div>
        </section>

        <?php if ($request['technician_name']) { ?>
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Your technician</h2>
                </div>
                <div class="panel-body">
                    <div class="table-user">
                        <span class="avatar" style="background: <?= e($request['technician_color'] ?: '#2563eb') ?>"><?= e(initials($request['technician_name'])) ?></span>
                        <span>
                            <strong><?= e($request['technician_name']) ?></strong>
                            <small><?= e($request['technician_specialty'] ?: 'Service technician') ?></small>
                        </span>
                    </div>
                    <?php if ($request['technician_phone']) { ?>
                        <a class="btn btn-soft btn-sm btn-block mt-2" href="tel:<?= e($request['technician_phone']) ?>"><?= icon('phone') ?> Call technician</a>
                    <?php } ?>
                </div>
            </section>
        <?php } ?>
    </div>
</div>
