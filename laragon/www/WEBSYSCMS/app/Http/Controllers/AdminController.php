<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class AdminController extends Controller
{
    public function dashboard()
    {
        $counts = array_fill_keys(request_statuses(), 0);
        foreach (db_all('SELECT status, COUNT(*) AS total FROM service_requests GROUP BY status') as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        $total = array_sum($counts);
        $revenue = (float) db_value("SELECT COALESCE(SUM(final_cost), 0) FROM service_requests WHERE status = 'completed'");
        $customers = (int) db_value("SELECT COUNT(*) FROM users WHERE role = 'customer'");
        $technicians = (int) db_value("SELECT COUNT(*) FROM users WHERE role = 'technician' AND status = 'active'");
        $rating = (float) db_value('SELECT COALESCE(AVG(rating), 0) FROM feedback');
        $reviewCount = (int) db_value('SELECT COUNT(*) FROM feedback');

        $recent = db_all(
            'SELECT r.*, c.name AS category_name, c.icon AS category_icon,
                    cu.name AS customer_name, t.name AS technician_name
             FROM service_requests r
             JOIN service_categories c ON c.id = r.category_id
             JOIN users cu ON cu.id = r.customer_id
             LEFT JOIN users t ON t.id = r.technician_id
             ORDER BY r.created_at DESC LIMIT 6'
        );

        $todayAppointments = db_all(
            "SELECT a.*, r.ticket_no, r.title, u.name AS customer_name, t.name AS technician_name
             FROM appointments a
             JOIN service_requests r ON r.id = a.request_id
             JOIN users u ON u.id = r.customer_id
             LEFT JOIN users t ON t.id = a.technician_id
             WHERE a.scheduled_date = CURDATE() AND a.status IN ('scheduled', 'confirmed')
             ORDER BY a.scheduled_time ASC LIMIT 6"
        );

        $monthly = db_all(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS total
             FROM service_requests
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
             GROUP BY ym ORDER BY ym"
        );
        $monthlyMap = [];
        foreach ($monthly as $row) {
            $monthlyMap[$row['ym']] = (int) $row['total'];
        }
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $key = date('Y-m', strtotime("-{$i} months"));
            $trend[] = [
                'label' => date('M', strtotime($key.'-01')),
                'value' => $monthlyMap[$key] ?? 0,
            ];
        }
        $trendMax = max(1, max(array_column($trend, 'value')));

        $technicianPerformance = db_all(
            "SELECT u.id, u.name, u.specialty, u.avatar_color,
                    COUNT(r.id) AS jobs,
                    COALESCE(SUM(r.status = 'completed'), 0) AS completed,
                    COALESCE(AVG(f.rating), 0) AS rating
             FROM users u
             LEFT JOIN service_requests r ON r.technician_id = u.id
             LEFT JOIN feedback f ON f.request_id = r.id
             WHERE u.role = 'technician' AND u.status = 'active'
             GROUP BY u.id
             ORDER BY completed DESC, jobs DESC LIMIT 5"
        );

        $categories = db_all(
            'SELECT c.name, COUNT(r.id) AS total
             FROM service_categories c
             LEFT JOIN service_requests r ON r.category_id = c.id
             GROUP BY c.id ORDER BY total DESC LIMIT 6'
        );
        $categoryMax = max(1, max(array_column($categories, 'total') ?: [1]));

        return view('admin.dashboard', [
            'title' => 'Overview',
            'counts' => $counts,
            'total' => $total,
            'revenue' => $revenue,
            'customers' => $customers,
            'technicians' => $technicians,
            'rating' => $rating,
            'reviewCount' => $reviewCount,
            'recent' => $recent,
            'todayAppointments' => $todayAppointments,
            'trend' => $trend,
            'trendMax' => $trendMax,
            'technicianPerformance' => $technicianPerformance,
            'categories' => $categories,
            'categoryMax' => $categoryMax,
        ]);
    }

    public function requests()
    {
        $status = request()->query('status', '');
        $priority = request()->query('priority', '');
        $technician = (int) request()->query('technician', 0);
        $search = trim((string) request()->query('q', ''));
        $page = (int) request()->query('page', 1);
        $perPage = 12;

        $where = ['1 = 1'];
        $params = [];

        if (in_array($status, request_statuses(), true)) {
            $where[] = 'r.status = ?';
            $params[] = $status;
        }

        if (in_array($priority, ['low', 'medium', 'high', 'urgent'], true)) {
            $where[] = 'r.priority = ?';
            $params[] = $priority;
        }

        if ($technician > 0) {
            $where[] = 'r.technician_id = ?';
            $params[] = $technician;
        }

        if ($search !== '') {
            $where[] = '(r.ticket_no LIKE ? OR r.title LIKE ? OR cu.name LIKE ? OR c.name LIKE ?)';
            $like = '%'.$search.'%';
            array_push($params, $like, $like, $like, $like);
        }

        $whereSql = implode(' AND ', $where);
        $baseFrom = "FROM service_requests r
                     JOIN users cu ON cu.id = r.customer_id
                     JOIN service_categories c ON c.id = r.category_id
                     LEFT JOIN users t ON t.id = r.technician_id
                     WHERE $whereSql";

        $total = (int) db_value("SELECT COUNT(*) $baseFrom", $params);
        $pagination = $this->paginate($total, $perPage, $page);

        $requests = db_all(
            "SELECT r.*, c.name AS category_name, c.icon AS category_icon,
                    cu.name AS customer_name, cu.phone AS customer_phone, t.name AS technician_name
             $baseFrom
             ORDER BY FIELD(r.status, 'pending','in_progress','assigned','accepted','completed','cancelled'), r.created_at DESC
             LIMIT {$perPage} OFFSET {$pagination['offset']}",
            $params
        );

        $counts = array_fill_keys(request_statuses(), 0);
        foreach (db_all('SELECT status, COUNT(*) AS total FROM service_requests GROUP BY status') as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        $technicians = db_all("SELECT id, name FROM users WHERE role = 'technician' AND status = 'active' ORDER BY name");

        return view('admin.requests', [
            'title' => 'Service requests',
            'requests' => $requests,
            'counts' => $counts,
            'technicians' => $technicians,
            'filters' => ['status' => $status, 'priority' => $priority, 'technician' => $technician, 'q' => $search],
            'currentPage' => $pagination['page'],
            'totalPages' => $pagination['pages'],
            'total' => $total,
        ]);
    }

    public function requestShow(int $id)
    {
        $request = $this->findRequestWithDetails($id);

        $history = db_all(
            'SELECT h.*, u.name AS changed_by_name, u.role AS changed_by_role
             FROM request_status_history h LEFT JOIN users u ON u.id = h.changed_by
             WHERE h.request_id = ? ORDER BY h.created_at ASC, h.id ASC',
            [$request['id']]
        );

        $appointments = db_all(
            'SELECT a.*, t.name AS technician_name
             FROM appointments a LEFT JOIN users t ON t.id = a.technician_id
             WHERE a.request_id = ? ORDER BY a.scheduled_date DESC, a.scheduled_time DESC',
            [$request['id']]
        );

        $report = db_one('SELECT * FROM service_reports WHERE request_id = ?', [$request['id']]);
        $feedback = db_one('SELECT * FROM feedback WHERE request_id = ?', [$request['id']]);
        $technicians = db_all("SELECT id, name, specialty FROM users WHERE role = 'technician' AND status = 'active' ORDER BY name");

        return view('admin.request-detail', [
            'title' => $request['ticket_no'],
            'request' => $request,
            'history' => $history,
            'appointments' => $appointments,
            'report' => $report,
            'feedback' => $feedback,
            'technicians' => $technicians,
        ]);
    }

    public function assign(int $id)
    {
        $request = $this->findRequestWithDetails($id);

        if (in_array($request['status'], ['completed', 'cancelled'], true)) {
            $this->flash('error', 'Completed or cancelled requests cannot be reassigned.');

            return redirect('/admin/requests/'.$request['id']);
        }

        $technicianId = (int) $this->input('technician_id');
        $technician = db_one("SELECT * FROM users WHERE id = ? AND role = 'technician' AND status = 'active'", [$technicianId]);

        if (! $technician) {
            $this->flash('error', 'Choose an active technician.');

            return redirect('/admin/requests/'.$request['id']);
        }

        db_exec(
            "UPDATE service_requests SET technician_id = ?, status = 'assigned', assigned_at = NOW(), updated_at = NOW() WHERE id = ?",
            [$technicianId, $request['id']]
        );

        db_exec(
            "UPDATE appointments SET technician_id = ?, updated_at = NOW() WHERE request_id = ? AND status IN ('scheduled', 'confirmed', 'rescheduled')",
            [$technicianId, $request['id']]
        );

        db_insert(
            'INSERT INTO request_status_history (request_id, status, note, changed_by, created_at) VALUES (?, ?, ?, ?, NOW())',
            [$request['id'], 'assigned', 'Assigned to '.$technician['name'].'.', auth()->id()]
        );

        $this->log('assign_technician', 'Assigned '.$technician['name'].' to '.$request['ticket_no']);
        $this->flash('success', $technician['name'].' has been assigned to '.$request['ticket_no'].'.');

        return redirect('/admin/requests/'.$request['id']);
    }

    public function updateStatus(int $id)
    {
        $request = $this->findRequestWithDetails($id);
        $status = $this->input('status');
        $note = $this->input('note');

        if (! in_array($status, request_statuses(), true)) {
            $this->flash('error', 'Choose a valid status.');

            return redirect('/admin/requests/'.$request['id']);
        }

        if ($request['status'] === $status) {
            $this->flash('info', 'The request is already in that status.');

            return redirect('/admin/requests/'.$request['id']);
        }

        $fields = ['status = ?', 'updated_at = NOW()'];
        $values = [$status];

        if ($status === 'pending') {
            $fields[] = 'technician_id = NULL';
            $fields[] = 'assigned_at = NULL';
            $fields[] = 'started_at = NULL';
        }

        if ($status === 'in_progress') {
            $fields[] = 'started_at = COALESCE(started_at, NOW())';
        }

        if ($status === 'completed') {
            $fields[] = 'completed_at = COALESCE(completed_at, NOW())';
        }

        if ($status === 'cancelled') {
            db_exec(
                "UPDATE appointments SET status = 'cancelled', updated_at = NOW() WHERE request_id = ? AND status IN ('scheduled', 'confirmed', 'rescheduled')",
                [$request['id']]
            );
        }

        $values[] = $request['id'];
        db_exec('UPDATE service_requests SET '.implode(', ', $fields).' WHERE id = ?', $values);

        db_insert(
            'INSERT INTO request_status_history (request_id, status, note, changed_by, created_at) VALUES (?, ?, ?, ?, NOW())',
            [$request['id'], $status, $note !== '' ? $note : 'Status updated by an administrator.', auth()->id()]
        );

        $this->log('status_update', 'Moved '.$request['ticket_no'].' to '.$status);
        $this->flash('success', 'Status updated to '.str_replace('_', ' ', $status).'.');

        return redirect('/admin/requests/'.$request['id']);
    }

    public function schedule(int $id)
    {
        $request = $this->findRequestWithDetails($id);

        if (in_array($request['status'], ['completed', 'cancelled'], true)) {
            $this->flash('error', 'This request is closed and cannot be scheduled.');

            return redirect('/admin/requests/'.$request['id']);
        }

        $date = $this->input('scheduled_date');
        $time = $this->input('scheduled_time');
        $duration = (int) $this->input('duration_minutes', '60');
        $notes = $this->input('notes');
        $technicianId = (int) $this->input('technician_id');

        $errors = [];

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) === false) {
            $errors['scheduled_date'] = 'Choose a valid date.';
        }

        if (! preg_match('/^\d{2}:\d{2}/', $time) || strtotime($time) === false) {
            $errors['scheduled_time'] = 'Choose a valid time.';
        }

        if ($duration < 15 || $duration > 480) {
            $errors['duration_minutes'] = 'Duration must be between 15 and 480 minutes.';
        }

        if ($errors) {
            return $this->backWithErrors($errors, '/admin/requests/'.$request['id']);
        }

        if ($technicianId <= 0) {
            $technicianId = (int) ($request['technician_id'] ?? 0);
        }

        if ($technicianId > 0) {
            $technician = db_one("SELECT id, name FROM users WHERE id = ? AND role = 'technician' AND status = 'active'", [$technicianId]);

            if (! $technician) {
                $this->flash('error', 'Choose an active technician.');

                return redirect('/admin/requests/'.$request['id']);
            }
        } else {
            $this->flash('error', 'Assign a technician before scheduling.');

            return redirect('/admin/requests/'.$request['id']);
        }

        db_insert(
            'INSERT INTO appointments (request_id, technician_id, scheduled_date, scheduled_time, duration_minutes, status, notes, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [$request['id'], $technicianId, $date, $time.':00', $duration, 'scheduled', $notes ?: null, auth()->id()]
        );

        if ($request['status'] === 'pending' || (int) $request['technician_id'] !== $technicianId) {
            db_exec(
                "UPDATE service_requests SET technician_id = ?, status = IF(status = 'pending', 'assigned', status), assigned_at = COALESCE(assigned_at, NOW()), updated_at = NOW() WHERE id = ?",
                [$technicianId, $request['id']]
            );
        } else {
            db_exec('UPDATE service_requests SET updated_at = NOW() WHERE id = ?', [$request['id']]);
        }

        db_insert(
            'INSERT INTO request_status_history (request_id, status, note, changed_by, created_at) VALUES (?, ?, ?, ?, NOW())',
            [
                $request['id'],
                $request['status'] === 'pending' ? 'assigned' : $request['status'],
                'Appointment scheduled for '.date('M j, Y', strtotime($date)).' at '.date('g:i A', strtotime($time)).'.',
                auth()->id(),
            ]
        );

        $this->log('schedule', 'Scheduled appointment for '.$request['ticket_no']);
        $this->flash('success', 'Appointment scheduled and technician notified.');

        return redirect('/admin/requests/'.$request['id']);
    }

    public function updateCost(int $id)
    {
        $request = $this->findRequestWithDetails($id);

        $estimated = $this->input('estimated_cost');
        $final = $this->input('final_cost');

        $errors = [];

        if ($estimated !== '' && (! is_numeric($estimated) || (float) $estimated < 0)) {
            $errors['estimated_cost'] = 'Enter a valid estimate.';
        }

        if ($final !== '' && (! is_numeric($final) || (float) $final < 0)) {
            $errors['final_cost'] = 'Enter a valid final cost.';
        }

        if ($errors) {
            return $this->backWithErrors($errors, '/admin/requests/'.$request['id']);
        }

        db_exec(
            'UPDATE service_requests SET estimated_cost = ?, final_cost = ?, updated_at = NOW() WHERE id = ?',
            [
                $estimated !== '' ? (float) $estimated : null,
                $final !== '' ? (float) $final : null,
                $request['id'],
            ]
        );

        $this->log('update_cost', 'Updated costs for '.$request['ticket_no']);
        $this->flash('success', 'Service costs updated.');

        return redirect('/admin/requests/'.$request['id']);
    }

    public function appointments()
    {
        $status = request()->query('status', '');
        $from = request()->query('from', '');
        $to = request()->query('to', '');
        $page = (int) request()->query('page', 1);
        $perPage = 12;

        $where = ['1 = 1'];
        $params = [];

        if (in_array($status, ['scheduled', 'confirmed', 'rescheduled', 'completed', 'cancelled', 'no_show'], true)) {
            $where[] = 'a.status = ?';
            $params[] = $status;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $where[] = 'a.scheduled_date >= ?';
            $params[] = $from;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $where[] = 'a.scheduled_date <= ?';
            $params[] = $to;
        }

        $whereSql = implode(' AND ', $where);
        $baseFrom = "FROM appointments a
                     JOIN service_requests r ON r.id = a.request_id
                     JOIN users cu ON cu.id = r.customer_id
                     LEFT JOIN users t ON t.id = a.technician_id
                     WHERE $whereSql";

        $total = (int) db_value("SELECT COUNT(*) $baseFrom", $params);
        $pagination = $this->paginate($total, $perPage, $page);

        $appointments = db_all(
            "SELECT a.*, r.ticket_no, r.title, r.status AS request_status, cu.name AS customer_name, t.name AS technician_name
             $baseFrom
             ORDER BY a.scheduled_date DESC, a.scheduled_time DESC
             LIMIT {$perPage} OFFSET {$pagination['offset']}",
            $params
        );

        $counts = ['scheduled' => 0, 'confirmed' => 0, 'rescheduled' => 0, 'completed' => 0, 'cancelled' => 0, 'no_show' => 0];
        foreach (db_all('SELECT status, COUNT(*) AS total FROM appointments GROUP BY status') as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return view('admin.appointments', [
            'title' => 'Appointments',
            'appointments' => $appointments,
            'counts' => $counts,
            'filters' => ['status' => $status, 'from' => $from, 'to' => $to],
            'currentPage' => $pagination['page'],
            'totalPages' => $pagination['pages'],
            'total' => $total,
        ]);
    }

    public function appointmentStatus(int $id)
    {
        $appointment = db_one(
            'SELECT a.*, r.ticket_no FROM appointments a JOIN service_requests r ON r.id = a.request_id WHERE a.id = ?',
            [$id]
        );

        if (! $appointment) {
            $this->notFound('That appointment could not be found.');
        }

        $status = $this->input('status');

        if (! in_array($status, ['scheduled', 'confirmed', 'rescheduled', 'completed', 'cancelled', 'no_show'], true)) {
            $this->flash('error', 'Choose a valid appointment status.');

            return redirect('/admin/appointments');
        }

        db_exec('UPDATE appointments SET status = ?, updated_at = NOW() WHERE id = ?', [$status, $id]);
        $this->log('appointment_status', 'Marked appointment #'.$id.' as '.$status);
        $this->flash('success', 'Appointment marked as '.str_replace('_', ' ', $status).'.');

        return redirect('/admin/appointments');
    }

    public function feedback()
    {
        $rows = db_all(
            'SELECT f.*, r.ticket_no, cu.name AS customer_name, t.name AS technician_name, t.avatar_color
             FROM feedback f
             JOIN service_requests r ON r.id = f.request_id
             JOIN users cu ON cu.id = f.customer_id
             LEFT JOIN users t ON t.id = f.technician_id
             ORDER BY f.created_at DESC'
        );

        $average = (float) db_value('SELECT COALESCE(AVG(rating), 0) FROM feedback');
        $total = (int) db_value('SELECT COUNT(*) FROM feedback');

        $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        foreach (db_all('SELECT rating, COUNT(*) AS total FROM feedback GROUP BY rating') as $row) {
            $distribution[(int) $row['rating']] = (int) $row['total'];
        }
        $maxRating = max(1, max($distribution));

        $technicians = db_all(
            "SELECT u.id, u.name, u.avatar_color, COUNT(f.id) AS reviews, AVG(f.rating) AS rating
             FROM users u JOIN feedback f ON f.technician_id = u.id
             WHERE u.role = 'technician'
             GROUP BY u.id ORDER BY rating DESC"
        );

        return view('admin.feedback', [
            'title' => 'Feedback',
            'rows' => $rows,
            'average' => $average,
            'total' => $total,
            'distribution' => $distribution,
            'maxRating' => $maxRating,
            'technicians' => $technicians,
        ]);
    }

    public function messages()
    {
        $status = request()->query('status', '');
        $where = '1 = 1';
        $params = [];

        if (in_array($status, ['new', 'read', 'replied'], true)) {
            $where = 'status = ?';
            $params[] = $status;
        }

        $messages = db_all("SELECT * FROM contact_messages WHERE $where ORDER BY FIELD(status, 'new', 'read', 'replied'), created_at DESC", $params);

        $counts = ['new' => 0, 'read' => 0, 'replied' => 0];
        foreach (db_all('SELECT status, COUNT(*) AS total FROM contact_messages GROUP BY status') as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return view('admin.messages', [
            'title' => 'Inbox',
            'messages' => $messages,
            'counts' => $counts,
            'status' => $status,
        ]);
    }

    public function messageStatus(int $id)
    {
        $status = $this->input('status');

        if (! in_array($status, ['new', 'read', 'replied'], true)) {
            $this->flash('error', 'Choose a valid message status.');

            return redirect('/admin/messages');
        }

        db_exec('UPDATE contact_messages SET status = ? WHERE id = ?', [$status, $id]);
        $this->flash('success', 'Message marked as '.$status.'.');

        return redirect('/admin/messages');
    }

    public function reports()
    {
        $from = request()->query('from', date('Y-m-d', strtotime('-5 months')));
        $to = request()->query('to', date('Y-m-d'));

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $from = date('Y-m-d', strtotime('-5 months'));
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $to = date('Y-m-d');
        }

        $start = $from.' 00:00:00';
        $end = $to.' 23:59:59';

        $totalJobs = (int) db_value('SELECT COUNT(*) FROM service_requests WHERE created_at BETWEEN ? AND ?', [$start, $end]);
        $completedJobs = (int) db_value("SELECT COUNT(*) FROM service_requests WHERE status = 'completed' AND created_at BETWEEN ? AND ?", [$start, $end]);
        $cancelledJobs = (int) db_value("SELECT COUNT(*) FROM service_requests WHERE status = 'cancelled' AND created_at BETWEEN ? AND ?", [$start, $end]);
        $revenue = (float) db_value("SELECT COALESCE(SUM(final_cost), 0) FROM service_requests WHERE status = 'completed' AND created_at BETWEEN ? AND ?", [$start, $end]);
        $avgRating = (float) db_value('SELECT COALESCE(AVG(rating), 0) FROM feedback WHERE created_at BETWEEN ? AND ?', [$start, $end]);
        $reviewCount = (int) db_value('SELECT COUNT(*) FROM feedback WHERE created_at BETWEEN ? AND ?', [$start, $end]);

        $statusCounts = array_fill_keys(request_statuses(), 0);
        foreach (db_all('SELECT status, COUNT(*) AS total FROM service_requests WHERE created_at BETWEEN ? AND ? GROUP BY status', [$start, $end]) as $row) {
            $statusCounts[$row['status']] = (int) $row['total'];
        }
        $statusMax = max(1, max($statusCounts));

        $categoryPerf = db_all(
            "SELECT c.name, COUNT(r.id) AS jobs,
                    COALESCE(SUM(CASE WHEN r.status = 'completed' THEN r.final_cost ELSE 0 END), 0) AS revenue
             FROM service_categories c
             LEFT JOIN service_requests r ON r.category_id = c.id AND r.created_at BETWEEN ? AND ?
             GROUP BY c.id HAVING jobs > 0
             ORDER BY jobs DESC LIMIT 8",
            [$start, $end]
        );
        $categoryMax = max(1, max(array_column($categoryPerf, 'jobs') ?: [1]));

        $technicianPerf = db_all(
            "SELECT u.name, u.avatar_color, COUNT(r.id) AS jobs,
                    COALESCE(SUM(r.status = 'completed'), 0) AS completed,
                    COALESCE(SUM(CASE WHEN r.status = 'completed' THEN r.final_cost ELSE 0 END), 0) AS revenue,
                    COALESCE(AVG(f.rating), 0) AS rating
             FROM users u
             LEFT JOIN service_requests r ON r.technician_id = u.id AND r.created_at BETWEEN ? AND ?
             LEFT JOIN feedback f ON f.request_id = r.id
             WHERE u.role = 'technician'
             GROUP BY u.id ORDER BY completed DESC, jobs DESC",
            [$start, $end]
        );

        $monthly = db_all(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS total
             FROM service_requests
             WHERE created_at BETWEEN ? AND ?
             GROUP BY ym ORDER BY ym",
            [$start, $end]
        );
        $monthly = array_slice($monthly, -12);
        $trend = [];
        foreach ($monthly as $row) {
            $trend[] = [
                'label' => date('M y', strtotime($row['ym'].'-01')),
                'value' => (int) $row['total'],
            ];
        }
        $trendMax = max(1, max(array_column($trend, 'value') ?: [1]));

        $topCustomers = db_all(
            "SELECT u.name, COUNT(r.id) AS jobs, COALESCE(SUM(CASE WHEN r.status = 'completed' THEN r.final_cost ELSE 0 END), 0) AS spend
             FROM users u JOIN service_requests r ON r.customer_id = u.id
             WHERE u.role = 'customer' AND r.created_at BETWEEN ? AND ?
             GROUP BY u.id ORDER BY spend DESC, jobs DESC LIMIT 5",
            [$start, $end]
        );

        return view('admin.reports', [
            'title' => 'Reports',
            'filters' => ['from' => $from, 'to' => $to],
            'totalJobs' => $totalJobs,
            'completedJobs' => $completedJobs,
            'cancelledJobs' => $cancelledJobs,
            'revenue' => $revenue,
            'avgRating' => $avgRating,
            'reviewCount' => $reviewCount,
            'statusCounts' => $statusCounts,
            'statusMax' => $statusMax,
            'categoryPerf' => $categoryPerf,
            'categoryMax' => $categoryMax,
            'technicianPerf' => $technicianPerf,
            'trend' => $trend,
            'trendMax' => $trendMax,
            'topCustomers' => $topCustomers,
        ]);
    }

    public function export()
    {
        $from = request()->query('from', date('Y-m-d', strtotime('-5 months')));
        $to = request()->query('to', date('Y-m-d'));

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $from = date('Y-m-d', strtotime('-5 months'));
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $to = date('Y-m-d');
        }

        $rows = db_all(
            'SELECT r.ticket_no, r.created_at, cu.name AS customer, c.name AS category, r.service_type,
                    r.title, t.name AS technician, r.status, r.priority, r.estimated_cost, r.final_cost, r.completed_at
             FROM service_requests r
             JOIN users cu ON cu.id = r.customer_id
             JOIN service_categories c ON c.id = r.category_id
             LEFT JOIN users t ON t.id = r.technician_id
             WHERE r.created_at BETWEEN ? AND ?
             ORDER BY r.created_at DESC',
            [$from.' 00:00:00', $to.' 23:59:59']
        );

        $filename = 'fixitnow-service-requests-'.$from.'-to-'.$to.'.csv';

        $this->log('export_report', 'Exported service requests CSV for '.$from.' to '.$to);

        return response()->streamDownload(function () use ($rows) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Ticket', 'Created', 'Customer', 'Category', 'Service type', 'Title', 'Technician', 'Status', 'Priority', 'Estimate', 'Final cost', 'Completed']);

            foreach ($rows as $row) {
                fputcsv($output, [
                    $row['ticket_no'],
                    $row['created_at'],
                    $row['customer'],
                    $row['category'],
                    $row['service_type'],
                    $row['title'],
                    $row['technician'] ?: 'Unassigned',
                    $row['status'],
                    $row['priority'],
                    $row['estimated_cost'],
                    $row['final_cost'],
                    $row['completed_at'],
                ]);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    public function activity()
    {
        $page = (int) request()->query('page', 1);
        $perPage = 20;

        $total = (int) db_value('SELECT COUNT(*) FROM activity_logs');
        $pagination = $this->paginate($total, $perPage, $page);

        $logs = db_all(
            "SELECT l.*, u.name AS user_name, u.role AS user_role
             FROM activity_logs l LEFT JOIN users u ON u.id = l.user_id
             ORDER BY l.created_at DESC, l.id DESC
             LIMIT {$perPage} OFFSET {$pagination['offset']}"
        );

        return view('admin.activity', [
            'title' => 'Activity log',
            'logs' => $logs,
            'currentPage' => $pagination['page'],
            'totalPages' => $pagination['pages'],
            'total' => $total,
        ]);
    }

    public function settings()
    {
        return view('admin.settings', [
            'title' => 'Settings',
        ]);
    }

    public function saveSettings()
    {
        $allowed = [
            'site_name', 'site_tagline', 'contact_email', 'contact_phone', 'contact_address',
            'business_hours', 'support_hours', 'currency_symbol', 'hero_title', 'hero_subtitle',
            'about_intro', 'footer_note', 'facebook_url', 'instagram_url',
            'brand_color', 'accent_color', 'site_font',
        ];

        $errors = [];

        if (! filter_var($this->input('contact_email'), FILTER_VALIDATE_EMAIL)) {
            $errors['contact_email'] = 'Enter a valid contact email.';
        }

        if ($this->input('site_name') === '') {
            $errors['site_name'] = 'The site name is required.';
        }

        foreach (['brand_color', 'accent_color'] as $colorField) {
            if (preg_match('/\A#[0-9a-fA-F]{6}\z/', $this->input($colorField)) !== 1) {
                $errors[$colorField] = 'Choose a valid six-digit hex color.';
            }
        }

        if (! array_key_exists($this->input('site_font'), config('branding.fonts'))) {
            $errors['site_font'] = 'Choose a supported font.';
        }

        $uploadValidator = Validator::make(request()->all(), [
            'site_logo' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ]);
        $errors = array_merge($errors, $uploadValidator->errors()->toArray());

        if ($errors) {
            return $this->backWithErrors($errors, '/admin/settings');
        }

        $oldLogo = (string) setting('site_logo', '');
        $logoPath = $oldLogo;
        $logo = request()->file('site_logo');

        if ($logo !== null) {
            $extension = $logo->guessExtension();

            if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                return $this->backWithErrors(['site_logo' => 'Upload a JPEG, PNG, or WebP image.'], '/admin/settings');
            }

            $directory = public_path('uploads/branding');
            File::ensureDirectoryExists($directory);

            $filename = bin2hex(random_bytes(16)).'.'.$extension;
            $logo->move($directory, $filename);
            $logoPath = 'uploads/branding/'.$filename;
        } elseif ($this->input('remove_site_logo') === '1') {
            $logoPath = '';
        }

        DB::transaction(function () use ($allowed, $logoPath): void {
            foreach ($allowed as $key) {
                DB::table('settings')->updateOrInsert(
                    ['setting_key' => $key],
                    ['setting_value' => $this->input($key)]
                );
            }

            DB::table('settings')->updateOrInsert(
                ['setting_key' => 'site_logo'],
                ['setting_value' => $logoPath]
            );
        });

        settings(true);

        if ($oldLogo !== $logoPath) {
            $this->deleteManagedLogo($oldLogo);
        }

        $this->log('settings_update', 'Updated website settings');
        $this->flash('success', 'Settings saved. Public pages now use the updated details.');

        return redirect('/admin/settings');
    }

    private function deleteManagedLogo(string $logoPath): void
    {
        if (preg_match('/\Auploads\/branding\/[a-f0-9]{32}\.(?:jpe?g|png|webp)\z/i', $logoPath) !== 1) {
            return;
        }

        $absolutePath = public_path($logoPath);

        if (is_file($absolutePath) && ! File::delete($absolutePath)) {
            throw new RuntimeException('The previous site logo could not be removed.');
        }
    }

    private function findRequestWithDetails(int $id): array
    {
        $request = db_one(
            'SELECT r.*, c.name AS category_name, c.icon AS category_icon, c.base_price,
                    cu.name AS customer_name, cu.email AS customer_email, cu.phone AS customer_phone, cu.address AS customer_address,
                    t.name AS technician_name, t.phone AS technician_phone, t.specialty AS technician_specialty, t.avatar_color AS technician_color
             FROM service_requests r
             JOIN service_categories c ON c.id = r.category_id
             JOIN users cu ON cu.id = r.customer_id
             LEFT JOIN users t ON t.id = r.technician_id
             WHERE r.id = ?',
            [$id]
        );

        if (! $request) {
            $this->notFound('That service request could not be found.');
        }

        return $request;
    }
}
