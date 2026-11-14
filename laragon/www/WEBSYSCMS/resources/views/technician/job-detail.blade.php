@extends('layouts.app')

@section('content')
<?php
$statusStep = [
    'pending' => 'Request received',
    'assigned' => 'Assigned to you',
    'accepted' => 'Visit confirmed',
    'in_progress' => 'Repair in progress',
    'completed' => 'Service completed',
    'cancelled' => 'Request cancelled',
];
$hasActiveAssignment = in_array($request['status'], ['assigned', 'accepted', 'in_progress'], true);
?>
<?php if ($hasActiveAssignment): ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<?php endif; ?>
<div class="page-head">
    <div>
        <nav class="breadcrumb" style="margin-bottom:8px">
            <a href="<?= e(url('/technician/jobs')) ?>">Assigned jobs</a>
            <?= icon('chevron-right') ?>
            <span><?= e($request['ticket_no']) ?></span>
        </nav>
        <h1><?= e($request['title']) ?></h1>
        <p><?= e($request['category_name']) ?> · <?= e($request['customer_name']) ?></p>
    </div>
    <div class="page-head-actions">
        <?= status_badge($request['status']) ?>
        <?= status_badge($request['priority']) ?>
    </div>
</div>

<?php if ($request['status'] === 'assigned'): ?>
    <div class="note-box" style="margin-bottom:18px;border-color:var(--brand-line);background:var(--brand-soft)">
        <?= icon('info') ?>
        <p><strong>New assignment.</strong> Accept the job to confirm the appointment, or decline it to return it to the assignment queue.</p>
    </div>
<?php endif; ?>

<div class="detail-layout">
    <div class="dash-stack">
        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Job details</h2>
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
                        <span class="info-label">Brand / model</span>
                        <span class="info-value"><?= e(trim(($request['brand'] ?: '—') . ' / ' . ($request['model'] ?: '—'), ' /')) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Serial number</span>
                        <span class="info-value"><?= e($request['serial_no'] ?: '—') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Preferred visit</span>
                        <span class="info-value"><?= e($request['preferred_date'] ? format_date($request['preferred_date']) : 'Flexible') ?> · <?= e($request['preferred_time'] ?: 'Any time') ?></span>
                    </div>
                    <div class="info-item" style="grid-column:1/-1">
                        <span class="info-label">Service address</span>
                        <span class="info-value"><?= e($request['address']) ?></span>
                    </div>
                </div>
                <div class="divider"></div>
                <span class="info-label">Customer's description</span>
                <p class="detail-desc"><?= e($request['description']) ?></p>
            </div>
        </section>

        <?php if ($hasActiveAssignment): ?>
            <section class="panel" data-live-location
                     data-user-role="technician"
                     data-endpoint="<?= e(url('/service-requests/'.$request['id'].'/location')) ?>"
                     data-tile-url="<?= e(config('services.location.tile_url')) ?>">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">Location sharing</h2>
                        <p class="panel-sub">Only this request's customer can see your live location.</p>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="location-map" data-tracking-map aria-label="Map showing the customer service location and your shared location"></div>
                    <div class="inline-actions mt-2">
                        <button class="btn btn-primary btn-sm" type="button" data-start-location>Start sharing</button>
                        <button class="btn btn-danger-soft btn-sm" type="button" data-stop-location hidden>Stop sharing</button>
                    </div>
                    <p class="field-hint" data-live-location-status aria-live="polite">Your location is not being shared.</p>
                    <div class="map-legend">
                        <span><i class="map-dot customer"></i> Customer address</span>
                        <span><i class="map-dot technician"></i> Your location</span>
                    </div>
                    <small class="field-hint">Sharing is optional and stops when you click Stop. GPS accuracy varies by device. Map data © OpenStreetMap contributors.</small>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($request['status'] === 'in_progress'): ?>
            <section class="panel">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">Complete this job</h2>
                        <p class="panel-sub">Submit the service report to close the ticket.</p>
                    </div>
                    <?= icon('file') ?>
                </div>
                <form method="post" action="<?= e(url('/technician/jobs/' . $request['id'] . '/complete')) ?>" class="form">
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="diagnosis">Diagnosis *</label>
                        <textarea id="diagnosis" name="diagnosis" rows="3" class="<?= isset($errors['diagnosis']) ? 'is-invalid' : '' ?>" required><?= e(old('diagnosis', $report['diagnosis'] ?? '')) ?></textarea>
                        <?php if (isset($errors['diagnosis'])): ?><span class="field-error"><?= e($errors['diagnosis']) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="work_performed">Work performed *</label>
                        <textarea id="work_performed" name="work_performed" rows="3" class="<?= isset($errors['work_performed']) ? 'is-invalid' : '' ?>" required><?= e(old('work_performed', $report['work_performed'] ?? '')) ?></textarea>
                        <?php if (isset($errors['work_performed'])): ?><span class="field-error"><?= e($errors['work_performed']) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="parts_used">Parts used</label>
                        <input type="text" id="parts_used" name="parts_used" value="<?= e(old('parts_used', $report['parts_used'] ?? '')) ?>" placeholder="e.g. 35uF capacitor, refrigerant top-up">
                    </div>
                    <div class="form-row">
                        <div class="field">
                            <label for="labor_hours">Labor hours</label>
                            <input type="number" step="0.5" min="0" id="labor_hours" name="labor_hours" value="<?= e(old('labor_hours', $report['labor_hours'] ?? '')) ?>" class="<?= isset($errors['labor_hours']) ? 'is-invalid' : '' ?>" placeholder="e.g. 2">
                            <?php if (isset($errors['labor_hours'])): ?><span class="field-error"><?= e($errors['labor_hours']) ?></span><?php endif; ?>
                        </div>
                        <div class="field">
                            <label for="final_cost">Final cost *</label>
                            <input type="number" step="0.01" min="0" id="final_cost" name="final_cost" value="<?= e(old('final_cost', $request['final_cost'] ?? $request['estimated_cost'] ?? '')) ?>" class="<?= isset($errors['final_cost']) ? 'is-invalid' : '' ?>" required>
                            <?php if (isset($errors['final_cost'])): ?><span class="field-error"><?= e($errors['final_cost']) ?></span><?php endif; ?>
                        </div>
                    </div>
                    <div class="field">
                        <label for="recommendations">Recommendations</label>
                        <textarea id="recommendations" name="recommendations" rows="2" placeholder="Maintenance advice for the customer"><?= e(old('recommendations', $report['recommendations'] ?? '')) ?></textarea>
                    </div>
                    <button class="btn btn-primary" type="submit"><?= icon('check-circle') ?> Submit report &amp; complete job</button>
                </form>
            </section>
        <?php endif; ?>

        <?php if ($report && $request['status'] === 'completed'): ?>
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

        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Status timeline</h2>
                    <p class="panel-sub">Ticket activity.</p>
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
                                    <?php if ($event['changed_by_name']): ?><span>· <?= e($event['changed_by_name']) ?></span><?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

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
    </div>

    <div class="sticky-col">
        <section class="panel">
            <div class="panel-body">
                <div class="flex-between">
                    <span class="service-icon"><?= icon($request['category_icon']) ?></span>
                    <?= status_badge($request['status']) ?>
                </div>
                <h2 style="font-size:1.15rem;margin:14px 0 4px"><?= e($request['category_name']) ?></h2>
                <p class="text-muted text-sm" style="margin:0"><?= e(ucfirst($request['service_type'])) ?> job · <?= e($request['ticket_no']) ?></p>

                <div class="divider"></div>

                <div class="stack-sm">
                    <div class="flex-between text-sm">
                        <span class="text-muted">Estimated cost</span>
                        <strong><?= e(money($request['estimated_cost'])) ?></strong>
                    </div>
                    <div class="flex-between text-sm">
                        <span class="text-muted">Assigned at</span>
                        <strong><?= e(format_datetime($request['assigned_at'])) ?></strong>
                    </div>
                    <div class="flex-between text-sm">
                        <span class="text-muted">Started</span>
                        <strong><?= e(format_datetime($request['started_at'])) ?></strong>
                    </div>
                </div>

                <div class="divider"></div>

                <?php if ($request['status'] === 'assigned'): ?>
                    <div class="stack-sm">
                        <form method="post" action="<?= e(url('/technician/jobs/' . $request['id'] . '/accept')) ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-primary btn-block" type="submit"><?= icon('check') ?> Accept job</button>
                        </form>
                        <form method="post" action="<?= e(url('/technician/jobs/' . $request['id'] . '/reject')) ?>" data-confirm="Decline this job and return it to the assignment queue?">
                            <?= csrf_field() ?>
                            <button class="btn btn-danger-soft btn-block" type="submit">Decline job</button>
                        </form>
                    </div>
                <?php elseif (in_array($request['status'], ['accepted'], true)): ?>
                    <form method="post" action="<?= e(url('/technician/jobs/' . $request['id'] . '/start')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-primary btn-block" type="submit"><?= icon('wrench') ?> Start job</button>
                    </form>
                <?php elseif ($request['status'] === 'in_progress'): ?>
                    <p class="text-sm text-muted" style="margin:0">Fill out the completion report to close this job.</p>
                <?php endif; ?>
                <?php if ($hasActiveAssignment): ?>
                    <a class="btn btn-soft btn-sm btn-block mt-2" href="<?= e(url('/service-requests/'.$request['id'].'/chat')) ?>"><?= icon('message') ?> Chat with customer</a>
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
                    <span class="text-sm text-muted"><?= icon('pin') ?> <?= e($request['address']) ?></span>
                </div>
                <?php if ($request['customer_phone']): ?>
                    <a class="btn btn-soft btn-sm btn-block mt-2" href="tel:<?= e($request['customer_phone']) ?>"><?= icon('phone') ?> Call customer</a>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <h2 class="panel-title">Appointment</h2>
            </div>
            <div class="panel-body">
                <?php if ($appointment): ?>
                    <div class="appointment-item" style="padding:0;border:none">
                        <div class="appointment-date">
                            <strong><?= e(format_date($appointment['scheduled_date'], 'd')) ?></strong>
                            <span><?= e(format_date($appointment['scheduled_date'], 'M')) ?></span>
                        </div>
                        <div class="appointment-main">
                            <strong><?= e(format_time($appointment['scheduled_time'])) ?></strong>
                            <small><?= e($appointment['duration_minutes']) ?> minutes</small>
                        </div>
                    </div>
                    <div class="inline-actions mt-2"><?= status_badge($appointment['status']) ?></div>
                    <?php if ($appointment['notes']): ?>
                        <p class="text-sm text-muted mt-2" style="margin-bottom:0"><?= e($appointment['notes']) ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-sm text-muted" style="margin:0">No appointment has been scheduled for this job yet. Coordinate with the service desk.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<?php if ($hasActiveAssignment): ?>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php endif; ?>
@endsection
