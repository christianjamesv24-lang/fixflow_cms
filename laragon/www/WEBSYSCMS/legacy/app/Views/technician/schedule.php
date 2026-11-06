<?php
$groups = [];
foreach ($appointments as $appointment) {
    $groups[$appointment['scheduled_date']][] = $appointment;
}
$statusOptions = ['scheduled', 'confirmed', 'rescheduled', 'completed', 'cancelled', 'no_show'];
?>
<div class="page-head">
    <div>
        <p class="eyebrow">Calendar</p>
        <h1>My schedule</h1>
        <p><?= $todayCount ?> appointment<?= $todayCount === 1 ? '' : 's' ?> today · <?= count($appointments) ?> shown in total.</p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-ghost" href="<?= e(url('/technician/jobs')) ?>"><?= icon('clipboard') ?> Assigned jobs</a>
    </div>
</div>

<form class="filter-bar" method="get" action="<?= e(url('/technician/schedule')) ?>">
    <div class="field">
        <label for="status">Appointment status</label>
        <select id="status" name="status">
            <option value="">All appointments</option>
            <?php foreach ($statusOptions as $option) { ?>
                <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $option))) ?></option>
            <?php } ?>
        </select>
    </div>
    <button class="btn btn-primary btn-sm" type="submit"><?= icon('filter') ?> Filter</button>
    <?php if ($status !== '') { ?>
        <a class="filter-reset" href="<?= e(url('/technician/schedule')) ?>">Reset</a>
    <?php } ?>
</form>

<?php if ($groups) { ?>
    <div class="panel">
        <?php foreach ($groups as $date => $items) { ?>
            <?php
            $isToday = $date === date('Y-m-d');
            $isTomorrow = $date === date('Y-m-d', strtotime('+1 day'));
            $label = $isToday ? 'Today' : ($isTomorrow ? 'Tomorrow' : format_date($date, 'l, F j'));
            ?>
            <div class="day-group">
                <div class="day-group-head">
                    <span><?= e($label) ?></span>
                    <span><?= count($items) ?> visit<?= count($items) === 1 ? '' : 's' ?></span>
                </div>
                <div class="appointment-list">
                    <?php foreach ($items as $appointment) { ?>
                        <div class="appointment-item">
                            <div class="appointment-date">
                                <strong><?= e(format_date($appointment['scheduled_date'], 'd')) ?></strong>
                                <span><?= e(format_date($appointment['scheduled_date'], 'M')) ?></span>
                            </div>
                            <div class="appointment-main">
                                <strong><?= e($appointment['title']) ?></strong>
                                <small><?= e($appointment['ticket_no']) ?> · <?= e($appointment['customer_name']) ?> · <?= e($appointment['category_name']) ?></small>
                                <small><?= icon('pin') ?> <?= e(truncate($appointment['address'], 70)) ?></small>
                            </div>
                            <div class="appointment-side">
                                <span class="appointment-time"><?= icon('clock') ?> <?= e(format_time($appointment['scheduled_time'])) ?></span>
                                <?= status_badge($appointment['status']) ?>
                                <a class="btn btn-ghost btn-sm" href="<?= e(url('/technician/jobs/'.$appointment['request_id'])) ?>">Open job</a>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>
<?php } else { ?>
    <div class="panel">
        <div class="empty-state">
            <?= icon('calendar') ?>
            <h3>No appointments found</h3>
            <p>Your scheduled visits will appear here once a coordinator books them.</p>
        </div>
    </div>
<?php } ?>
