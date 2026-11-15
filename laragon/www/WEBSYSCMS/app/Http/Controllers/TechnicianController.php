<?php

namespace App\Http\Controllers;

use App\Services\CustomerRequestNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class TechnicianController extends Controller
{
    public function dashboard()
    {
        $user = $this->authUser();
        $techId = (int) $user->id;

        $counts = array_fill_keys(request_statuses(), 0);
        foreach (db_all('SELECT status, COUNT(*) AS total FROM service_requests WHERE technician_id = ? GROUP BY status', [$techId]) as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        $completed = $counts['completed'];
        $active = $counts['assigned'] + $counts['accepted'] + $counts['in_progress'];
        $rating = (float) db_value('SELECT COALESCE(AVG(rating), 0) FROM feedback WHERE technician_id = ?', [$techId]);
        $reviewCount = (int) db_value('SELECT COUNT(*) FROM feedback WHERE technician_id = ?', [$techId]);

        $today = db_all(
            "SELECT a.*, r.ticket_no, r.title, r.priority, r.address, c.name AS category_name, u.name AS customer_name, u.phone AS customer_phone
             FROM appointments a
             JOIN service_requests r ON r.id = a.request_id
             JOIN service_categories c ON c.id = r.category_id
             JOIN users u ON u.id = r.customer_id
             WHERE a.technician_id = ? AND a.scheduled_date = CURDATE() AND a.status IN ('scheduled', 'confirmed')
             ORDER BY a.scheduled_time ASC",
            [$techId]
        );

        $activeJobs = db_all(
            "SELECT r.*, c.name AS category_name, c.icon AS category_icon, u.name AS customer_name
             FROM service_requests r
             JOIN service_categories c ON c.id = r.category_id
             JOIN users u ON u.id = r.customer_id
             WHERE r.technician_id = ? AND r.status IN ('assigned', 'accepted', 'in_progress')
             ORDER BY FIELD(r.priority, 'urgent', 'high', 'medium', 'low'), r.created_at ASC LIMIT 6",
            [$techId]
        );

        $recent = db_all(
            "SELECT r.*, c.name AS category_name, c.icon AS category_icon
             FROM service_requests r JOIN service_categories c ON c.id = r.category_id
             WHERE r.technician_id = ? AND r.status = 'completed'
             ORDER BY r.completed_at DESC LIMIT 5",
            [$techId]
        );

        return view('technician.dashboard', [
            'title' => 'Overview',
            'user' => $user,
            'counts' => $counts,
            'active' => $active,
            'completed' => $completed,
            'rating' => $rating,
            'reviewCount' => $reviewCount,
            'today' => $today,
            'activeJobs' => $activeJobs,
            'recent' => $recent,
        ]);
    }

    public function jobs()
    {
        $user = $this->authUser();
        $techId = (int) $user->id;

        $status = request()->query('status', '');
        $search = trim((string) request()->query('q', ''));
        $page = (int) request()->query('page', 1);
        $perPage = 10;

        $where = ['r.technician_id = ?'];
        $params = [$techId];

        if (in_array($status, request_statuses(), true)) {
            $where[] = 'r.status = ?';
            $params[] = $status;
        }

        if ($search !== '') {
            $where[] = '(r.ticket_no LIKE ? OR r.title LIKE ? OR u.name LIKE ? OR c.name LIKE ?)';
            $like = '%'.$search.'%';
            array_push($params, $like, $like, $like, $like);
        }

        $whereSql = implode(' AND ', $where);
        $total = (int) db_value(
            "SELECT COUNT(*) FROM service_requests r
             JOIN users u ON u.id = r.customer_id
             JOIN service_categories c ON c.id = r.category_id
             WHERE $whereSql",
            $params
        );

        $pagination = $this->paginate($total, $perPage, $page);

        $jobs = db_all(
            "SELECT r.*, c.name AS category_name, c.icon AS category_icon, u.name AS customer_name, u.phone AS customer_phone
             FROM service_requests r
             JOIN users u ON u.id = r.customer_id
             JOIN service_categories c ON c.id = r.category_id
             WHERE $whereSql
             ORDER BY FIELD(r.priority, 'urgent', 'high', 'medium', 'low'), r.created_at DESC
             LIMIT {$perPage} OFFSET {$pagination['offset']}",
            $params
        );

        $counts = array_fill_keys(request_statuses(), 0);
        foreach (db_all('SELECT status, COUNT(*) AS total FROM service_requests WHERE technician_id = ? GROUP BY status', [$techId]) as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return view('technician.jobs', [
            'title' => 'Assigned jobs',
            'jobs' => $jobs,
            'counts' => $counts,
            'status' => $status,
            'search' => $search,
            'currentPage' => $pagination['page'],
            'totalPages' => $pagination['pages'],
            'total' => $total,
        ]);
    }

    public function availableJobs()
    {
        $search = trim((string) request()->query('q', ''));
        $page = (int) request()->query('page', 1);
        $perPage = 10;

        $where = ["r.status = 'pending'", 'r.technician_id IS NULL'];
        $params = [];

        if ($search !== '') {
            $where[] = '(r.ticket_no LIKE ? OR r.title LIKE ? OR r.description LIKE ? OR c.name LIKE ?)';
            $like = '%'.$search.'%';
            array_push($params, $like, $like, $like, $like);
        }

        $whereSql = implode(' AND ', $where);
        $total = (int) db_value(
            "SELECT COUNT(*) FROM service_requests r
             JOIN service_categories c ON c.id = r.category_id
             WHERE $whereSql",
            $params
        );

        $pagination = $this->paginate($total, $perPage, $page);
        $jobs = db_all(
            "SELECT r.id, r.ticket_no, r.service_type, r.title, r.description, r.brand, r.model,
                    r.address, r.priority, r.preferred_date, r.preferred_time, r.created_at,
                    c.name AS category_name, c.icon AS category_icon
             FROM service_requests r
             JOIN service_categories c ON c.id = r.category_id
             WHERE $whereSql
             ORDER BY CASE r.priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END,
                      r.created_at DESC
             LIMIT {$perPage} OFFSET {$pagination['offset']}",
            $params
        );
        $jobs = array_map(static function (array $job): array {
            $addressParts = array_values(array_filter(
                array_map('trim', explode(',', (string) $job['address'])),
                static fn (string $part): bool => $part !== ''
            ));

            $job['general_area'] = count($addressParts) > 1
                ? implode(', ', array_slice($addressParts, -min(2, count($addressParts) - 1)))
                : 'Area withheld until claim';
            unset($job['address']);

            return $job;
        }, $jobs);

        return view('technician.available-jobs', [
            'title' => 'Available jobs',
            'jobs' => $jobs,
            'search' => $search,
            'currentPage' => $pagination['page'],
            'totalPages' => $pagination['pages'],
            'total' => $total,
        ]);
    }

    public function claim(int $id): RedirectResponse
    {
        $user = $this->authUser();
        $claimedAt = now();

        $claimed = DB::transaction(function () use ($id, $user, $claimedAt): bool {
            $updated = DB::table('service_requests')
                ->where('id', $id)
                ->whereNull('technician_id')
                ->where('status', 'pending')
                ->update([
                    'technician_id' => $user->id,
                    'status' => 'accepted',
                    'assigned_at' => $claimedAt,
                    'updated_at' => $claimedAt,
                ]);

            if ($updated !== 1) {
                return false;
            }

            DB::table('request_status_history')->insert([
                'request_id' => $id,
                'status' => 'accepted',
                'note' => 'Technician claimed the open request.',
                'changed_by' => $user->id,
                'created_at' => $claimedAt,
            ]);

            return true;
        });

        if (! $claimed) {
            $this->flash('error', 'This request is no longer available. Refresh the job board.');

            return redirect('/technician/jobs/available');
        }

        $request = $this->findJob($id, (int) $user->id);
        app(CustomerRequestNotifier::class)->notify(
            (int) $request['customer_id'],
            (int) $request['id'],
            'Your request was claimed',
            $user->name.' claimed request #'.$request['ticket_no'].'.'
        );

        $this->log('claim_job', 'Claimed request '.$request['ticket_no']);
        $this->flash('success', 'Job claimed. Customer details are now available.');

        return redirect('/technician/jobs/'.$request['id']);
    }

    public function show(int $id)
    {
        $user = $this->authUser();
        $request = $this->findJob($id, (int) $user->id);

        $history = db_all(
            'SELECT h.*, u.name AS changed_by_name
             FROM request_status_history h LEFT JOIN users u ON u.id = h.changed_by
             WHERE h.request_id = ? ORDER BY h.created_at ASC, h.id ASC',
            [$request['id']]
        );

        $appointment = db_one(
            'SELECT * FROM appointments WHERE request_id = ? ORDER BY scheduled_date DESC, scheduled_time DESC LIMIT 1',
            [$request['id']]
        );

        $report = db_one('SELECT * FROM service_reports WHERE request_id = ?', [$request['id']]);
        $feedback = db_one('SELECT * FROM feedback WHERE request_id = ?', [$request['id']]);

        return view('technician.job-detail', [
            'title' => $request['ticket_no'],
            'request' => $request,
            'history' => $history,
            'appointment' => $appointment,
            'report' => $report,
            'feedback' => $feedback,
        ]);
    }

    public function accept(int $id)
    {
        $user = $this->authUser();
        $request = $this->findJob($id, (int) $user->id);

        if ($request['status'] !== 'assigned') {
            $this->flash('error', 'Only newly assigned jobs can be accepted.');

            return redirect('/technician/jobs/'.$request['id']);
        }

        DB::table('service_requests')->where('id', $request['id'])->update([
            'status' => 'accepted',
            'updated_at' => now(),
        ]);
        DB::table('appointments')
            ->where('request_id', $request['id'])
            ->where('status', 'scheduled')
            ->update(['status' => 'confirmed', 'updated_at' => now()]);
        DB::table('request_status_history')->insert([
            'request_id' => $request['id'],
            'status' => 'accepted',
            'note' => 'Technician accepted the job and confirmed the appointment.',
            'changed_by' => $user->id,
            'created_at' => now(),
        ]);

        app(CustomerRequestNotifier::class)->notify(
            (int) $request['customer_id'],
            (int) $request['id'],
            'Your request was accepted',
            $user->name.' accepted request #'.$request['ticket_no'].' and confirmed the appointment.'
        );

        $this->log('accept_job', 'Accepted job '.$request['ticket_no']);
        $this->flash('success', 'Job accepted. The appointment is confirmed.');

        return redirect('/technician/jobs/'.$request['id']);
    }

    public function reject(int $id)
    {
        $user = $this->authUser();
        $request = $this->findJob($id, (int) $user->id);

        if ($request['status'] !== 'assigned') {
            $this->flash('error', 'Only newly assigned jobs can be declined.');

            return redirect('/technician/jobs/'.$request['id']);
        }

        db_exec(
            "UPDATE service_requests SET technician_id = NULL, status = 'pending', assigned_at = NULL, updated_at = NOW() WHERE id = ?",
            [$request['id']]
        );
        db_exec('DELETE FROM technician_live_locations WHERE request_id = ?', [$request['id']]);
        db_exec(
            "UPDATE appointments SET status = 'cancelled', updated_at = NOW() WHERE request_id = ? AND status IN ('scheduled', 'confirmed')",
            [$request['id']]
        );
        db_insert(
            'INSERT INTO request_status_history (request_id, status, note, changed_by, created_at) VALUES (?, ?, ?, ?, NOW())',
            [$request['id'], 'pending', 'Technician declined the job. Returned to the assignment queue.', $user->id]
        );

        app(CustomerRequestNotifier::class)->notify(
            (int) $request['customer_id'],
            (int) $request['id'],
            'Technician assignment updated',
            'The technician could not take request #'.$request['ticket_no'].'. It is back in the assignment queue.'
        );

        $this->log('reject_job', 'Declined job '.$request['ticket_no']);
        $this->flash('info', 'Job declined and returned to the assignment queue.');

        return redirect('/technician/jobs');
    }

    public function start(int $id)
    {
        $user = $this->authUser();
        $request = $this->findJob($id, (int) $user->id);

        if (! in_array($request['status'], ['assigned', 'accepted'], true)) {
            $this->flash('error', 'This job cannot be started from its current status.');

            return redirect('/technician/jobs/'.$request['id']);
        }

        db_exec(
            "UPDATE service_requests SET status = 'in_progress', started_at = COALESCE(started_at, NOW()), updated_at = NOW() WHERE id = ?",
            [$request['id']]
        );
        db_exec(
            "UPDATE appointments SET status = 'confirmed', updated_at = NOW() WHERE request_id = ? AND status IN ('scheduled', 'confirmed')",
            [$request['id']]
        );
        db_insert(
            'INSERT INTO request_status_history (request_id, status, note, changed_by, created_at) VALUES (?, ?, ?, ?, NOW())',
            [$request['id'], 'in_progress', 'Technician arrived on site and started the job.', $user->id]
        );

        app(CustomerRequestNotifier::class)->notify(
            (int) $request['customer_id'],
            (int) $request['id'],
            'Job status updated',
            'Your request #'.$request['ticket_no'].' is now in progress.'
        );

        $this->log('start_job', 'Started job '.$request['ticket_no']);
        $this->flash('success', 'Job started. Submit the service report when the work is done.');

        return redirect('/technician/jobs/'.$request['id']);
    }

    public function complete(int $id)
    {
        $user = $this->authUser();
        $request = $this->findJob($id, (int) $user->id);

        if ($request['status'] !== 'in_progress') {
            $this->flash('error', 'Start the job before submitting the completion report.');

            return redirect('/technician/jobs/'.$request['id']);
        }

        $data = [
            'diagnosis' => $this->input('diagnosis'),
            'work_performed' => $this->input('work_performed'),
            'parts_used' => $this->input('parts_used'),
            'labor_hours' => $this->input('labor_hours'),
            'recommendations' => $this->input('recommendations'),
            'final_cost' => $this->input('final_cost'),
        ];

        $errors = [];

        if (mb_strlen($data['diagnosis']) < 10) {
            $errors['diagnosis'] = 'Describe the diagnosis in at least 10 characters.';
        }

        if (mb_strlen($data['work_performed']) < 10) {
            $errors['work_performed'] = 'Describe the work performed.';
        }

        if ($data['labor_hours'] !== '' && ! is_numeric($data['labor_hours'])) {
            $errors['labor_hours'] = 'Labor hours must be a number.';
        }

        if ($data['final_cost'] === '' || ! is_numeric($data['final_cost']) || (float) $data['final_cost'] < 0) {
            $errors['final_cost'] = 'Enter the final service cost.';
        }

        if ($errors) {
            return $this->backWithErrors($errors, '/technician/jobs/'.$request['id']);
        }

        DB::transaction(function () use ($data, $request, $user) {
            $existing = db_one('SELECT id FROM service_reports WHERE request_id = ?', [$request['id']]);

            if ($existing) {
                db_exec(
                    'UPDATE service_reports SET diagnosis = ?, work_performed = ?, parts_used = ?, labor_hours = ?, recommendations = ? WHERE request_id = ?',
                    [
                        $data['diagnosis'],
                        $data['work_performed'],
                        $data['parts_used'] ?: null,
                        $data['labor_hours'] !== '' ? (float) $data['labor_hours'] : null,
                        $data['recommendations'] ?: null,
                        $request['id'],
                    ]
                );
            } else {
                db_insert(
                    'INSERT INTO service_reports (request_id, technician_id, diagnosis, work_performed, parts_used, labor_hours, recommendations, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
                    [
                        $request['id'],
                        $user->id,
                        $data['diagnosis'],
                        $data['work_performed'],
                        $data['parts_used'] ?: null,
                        $data['labor_hours'] !== '' ? (float) $data['labor_hours'] : null,
                        $data['recommendations'] ?: null,
                    ]
                );
            }

            db_exec(
                "UPDATE service_requests SET status = 'completed', final_cost = ?, completed_at = NOW(), updated_at = NOW() WHERE id = ?",
                [(float) $data['final_cost'], $request['id']]
            );
            db_exec('DELETE FROM technician_live_locations WHERE request_id = ?', [$request['id']]);

            db_exec(
                "UPDATE appointments SET status = 'completed', updated_at = NOW() WHERE request_id = ? AND status IN ('scheduled', 'confirmed')",
                [$request['id']]
            );

            db_insert(
                'INSERT INTO request_status_history (request_id, status, note, changed_by, created_at) VALUES (?, ?, ?, ?, NOW())',
                [$request['id'], 'completed', 'Service completed and report submitted. Final cost: '.money($data['final_cost']), $user->id]
            );

            app(CustomerRequestNotifier::class)->notify(
                (int) $request['customer_id'],
                (int) $request['id'],
                'Job status updated',
                'Your request #'.$request['ticket_no'].' is complete. Review the service report and share your feedback.'
            );
        });

        $this->log('complete_job', 'Completed job '.$request['ticket_no']);
        $this->flash('success', 'Service report submitted. Ticket '.$request['ticket_no'].' is now complete.');

        return redirect('/technician/jobs/'.$request['id']);
    }

    public function schedule()
    {
        $user = $this->authUser();
        $techId = (int) $user->id;

        $status = request()->query('status', '');
        $where = ['a.technician_id = ?'];
        $params = [$techId];

        if (in_array($status, ['scheduled', 'confirmed', 'completed', 'cancelled', 'rescheduled', 'no_show'], true)) {
            $where[] = 'a.status = ?';
            $params[] = $status;
        }

        $whereSql = implode(' AND ', $where);

        $appointments = db_all(
            "SELECT a.*, r.ticket_no, r.title, r.address, r.priority, r.status AS request_status,
                    c.name AS category_name, c.icon AS category_icon, u.name AS customer_name, u.phone AS customer_phone
             FROM appointments a
             JOIN service_requests r ON r.id = a.request_id
             JOIN service_categories c ON c.id = r.category_id
             JOIN users u ON u.id = r.customer_id
             WHERE $whereSql
             ORDER BY a.scheduled_date ASC, a.scheduled_time ASC",
            $params
        );

        $todayCount = 0;
        foreach ($appointments as $appointment) {
            if ($appointment['scheduled_date'] === date('Y-m-d') && in_array($appointment['status'], ['scheduled', 'confirmed'], true)) {
                $todayCount++;
            }
        }

        return view('technician.schedule', [
            'title' => 'My schedule',
            'appointments' => $appointments,
            'status' => $status,
            'todayCount' => $todayCount,
        ]);
    }

    private function findJob(int $id, int $technicianId): array
    {
        $request = db_one(
            'SELECT r.*, c.name AS category_name, c.icon AS category_icon, c.base_price,
                    u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone, u.address AS customer_address
             FROM service_requests r
             JOIN service_categories c ON c.id = r.category_id
             JOIN users u ON u.id = r.customer_id
             WHERE r.id = ? AND r.technician_id = ?',
            [$id, $technicianId]
        );

        if (! $request) {
            $this->notFound('That job is not assigned to you.');
        }

        return $request;
    }
}
