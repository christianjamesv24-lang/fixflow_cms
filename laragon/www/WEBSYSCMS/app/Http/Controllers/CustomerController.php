<?php

namespace App\Http\Controllers;

use App\Services\CustomerRequestNotifier;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function dashboard()
    {
        $user = $this->authUser();
        $customerId = (int) $user->id;

        $counts = array_fill_keys(request_statuses(), 0);
        foreach (db_all('SELECT status, COUNT(*) AS total FROM service_requests WHERE customer_id = ? GROUP BY status', [$customerId]) as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        $total = array_sum($counts);
        $active = $counts['pending'] + $counts['assigned'] + $counts['accepted'] + $counts['in_progress'];
        $spent = (float) db_value(
            "SELECT COALESCE(SUM(final_cost), 0) FROM service_requests WHERE customer_id = ? AND status = 'completed'",
            [$customerId]
        );

        $upcoming = db_one(
            "SELECT a.*, r.ticket_no, r.title, c.name AS category_name, c.icon AS category_icon, t.name AS technician_name
             FROM appointments a
             JOIN service_requests r ON r.id = a.request_id
             JOIN service_categories c ON c.id = r.category_id
             LEFT JOIN users t ON t.id = a.technician_id
             WHERE r.customer_id = ? AND a.scheduled_date >= CURDATE() AND a.status IN ('scheduled', 'confirmed')
             ORDER BY a.scheduled_date ASC, a.scheduled_time ASC LIMIT 1",
            [$customerId]
        );

        $recent = db_all(
            'SELECT r.*, c.name AS category_name, c.icon AS category_icon, t.name AS technician_name
             FROM service_requests r
             JOIN service_categories c ON c.id = r.category_id
             LEFT JOIN users t ON t.id = r.technician_id
             WHERE r.customer_id = ?
             ORDER BY r.created_at DESC LIMIT 6',
            [$customerId]
        );

        return view('customer.dashboard', [
            'title' => 'Overview',
            'user' => $user,
            'counts' => $counts,
            'total' => $total,
            'active' => $active,
            'spent' => $spent,
            'upcoming' => $upcoming,
            'recent' => $recent,
        ]);
    }

    public function index()
    {
        $user = $this->authUser();
        $customerId = (int) $user->id;

        $status = request()->query('status', '');
        $search = trim((string) request()->query('q', ''));
        $page = (int) request()->query('page', 1);
        $perPage = 10;

        $where = ['r.customer_id = ?'];
        $params = [$customerId];

        if (in_array($status, request_statuses(), true)) {
            $where[] = 'r.status = ?';
            $params[] = $status;
        }

        if ($search !== '') {
            $where[] = '(r.ticket_no LIKE ? OR r.title LIKE ? OR c.name LIKE ?)';
            $like = '%'.$search.'%';
            array_push($params, $like, $like, $like);
        }

        $whereSql = implode(' AND ', $where);
        $total = (int) db_value(
            "SELECT COUNT(*) FROM service_requests r JOIN service_categories c ON c.id = r.category_id WHERE $whereSql",
            $params
        );

        $pagination = $this->paginate($total, $perPage, $page);

        $requests = db_all(
            "SELECT r.*, c.name AS category_name, c.icon AS category_icon, t.name AS technician_name
             FROM service_requests r
             JOIN service_categories c ON c.id = r.category_id
             LEFT JOIN users t ON t.id = r.technician_id
             WHERE $whereSql
             ORDER BY r.created_at DESC
             LIMIT {$perPage} OFFSET {$pagination['offset']}",
            $params
        );

        $counts = array_fill_keys(request_statuses(), 0);
        foreach (db_all('SELECT status, COUNT(*) AS total FROM service_requests WHERE customer_id = ? GROUP BY status', [$customerId]) as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return view('customer.requests', [
            'title' => 'My requests',
            'requests' => $requests,
            'counts' => $counts,
            'status' => $status,
            'search' => $search,
            'currentPage' => $pagination['page'],
            'totalPages' => $pagination['pages'],
            'total' => $total,
        ]);
    }

    public function create()
    {
        $user = $this->authUser();
        $categories = db_all("SELECT * FROM service_categories WHERE status = 'active' ORDER BY name");
        $addresses = db_all(
            'SELECT id, label, address, latitude, longitude, accuracy
             FROM customer_addresses WHERE customer_id = ? ORDER BY id DESC',
            [$user->id]
        );
        $selected = request()->query('category', '');

        return view('customer.request-form', [
            'title' => 'New service request',
            'user' => $user,
            'categories' => $categories,
            'addresses' => $addresses,
            'selected' => $selected,
        ]);
    }

    public function store()
    {
        $user = $this->authUser();

        $data = [
            'category_id' => (int) $this->input('category_id'),
            'service_type' => $this->input('service_type', 'repair'),
            'title' => $this->input('title'),
            'description' => $this->input('description'),
            'brand' => $this->input('brand'),
            'model' => $this->input('model'),
            'serial_no' => $this->input('serial_no'),
            'priority' => $this->input('priority', 'medium'),
            'preferred_date' => $this->input('preferred_date'),
            'preferred_time' => $this->input('preferred_time'),
            'address' => $this->input('address'),
            'latitude' => $this->input('latitude'),
            'longitude' => $this->input('longitude'),
            'location_accuracy' => $this->input('location_accuracy'),
        ];

        $errors = [];

        $category = db_one("SELECT * FROM service_categories WHERE id = ? AND status = 'active'", [$data['category_id']]);

        if (! $category) {
            $errors['category_id'] = 'Choose a valid service category.';
        }

        if (! in_array($data['service_type'], ['repair', 'maintenance', 'installation', 'inspection'], true)) {
            $errors['service_type'] = 'Choose a service type.';
        }

        if (mb_strlen($data['title']) < 5) {
            $errors['title'] = 'Give your request a short, clear title.';
        }

        if (mb_strlen($data['description']) < 20) {
            $errors['description'] = 'Please describe the issue in at least 20 characters.';
        }

        if ($data['address'] === '') {
            $errors['address'] = 'We need a service address.';
        }

        if (($data['latitude'] === '') !== ($data['longitude'] === '')) {
            $errors['latitude'] = 'Use a complete map location or leave both coordinates blank.';
        } elseif ($data['latitude'] !== '' && (
            ! is_numeric($data['latitude'])
            || ! is_numeric($data['longitude'])
            || (float) $data['latitude'] < -90
            || (float) $data['latitude'] > 90
            || (float) $data['longitude'] < -180
            || (float) $data['longitude'] > 180
        )) {
            $errors['latitude'] = 'Choose a valid map location.';
        }

        if ($data['location_accuracy'] !== '' && (
            ! is_numeric($data['location_accuracy'])
            || (float) $data['location_accuracy'] < 0
            || (float) $data['location_accuracy'] > 100000
        )) {
            $errors['location_accuracy'] = 'The reported location accuracy is invalid.';
        }

        if (! in_array($data['priority'], ['low', 'medium', 'high', 'urgent'], true)) {
            $data['priority'] = 'medium';
        }

        if ($data['preferred_date'] !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['preferred_date'])) {
            $errors['preferred_date'] = 'Choose a valid preferred date.';
        }

        if ($errors) {
            return $this->backWithErrors($errors, '/customer/requests/new');
        }

        $ticket = generate_ticket_no();
        $requestId = DB::transaction(function () use ($ticket, $user, $data) {
            $createdAt = now();
            $requestId = DB::table('service_requests')->insertGetId([
                'ticket_no' => $ticket,
                'customer_id' => $user->id,
                'category_id' => $data['category_id'],
                'service_type' => $data['service_type'],
                'title' => $data['title'],
                'description' => $data['description'],
                'brand' => $data['brand'] ?: null,
                'model' => $data['model'] ?: null,
                'serial_no' => $data['serial_no'] ?: null,
                'address' => $data['address'],
                'latitude' => $data['latitude'] !== '' ? (float) $data['latitude'] : null,
                'longitude' => $data['longitude'] !== '' ? (float) $data['longitude'] : null,
                'location_accuracy' => $data['location_accuracy'] !== '' ? (float) $data['location_accuracy'] : null,
                'priority' => $data['priority'],
                'preferred_date' => $data['preferred_date'] ?: null,
                'preferred_time' => $data['preferred_time'] ?: null,
                'status' => 'pending',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            DB::table('request_status_history')->insert([
                'request_id' => $requestId,
                'status' => 'pending',
                'note' => 'Service request submitted by the customer.',
                'changed_by' => $user->id,
                'created_at' => $createdAt,
            ]);

            app(CustomerRequestNotifier::class)->notify(
                (int) $user->id,
                $requestId,
                'Request submitted',
                'We received your service request. You will be notified as soon as a technician responds.'
            );

            $technicianIds = DB::table('users as u')
                ->where('u.role', 'technician')
                ->whereNotNull('u.technician_approved_at')
                ->whereExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('subscriptions')
                        ->whereColumn('subscriptions.user_id', 'u.id')
                        ->where('subscriptions.status', 'active')
                        ->where('subscriptions.current_period_end', '>', now());
                })
                ->pluck('u.id');

            $notifications = $technicianIds->map(static fn ($technicianId) => [
                'user_id' => $technicianId,
                'title' => 'New customer request available',
                'body' => 'A new service request is ready to review in Available jobs.',
                'type' => 'info',
                'is_read' => false,
                'target_url' => url('/technician/jobs/available'),
                'created_at' => $createdAt,
            ])->all();

            if ($notifications) {
                DB::table('user_notifications')->insert($notifications);
            }

            return $requestId;
        });

        $this->log('create_request', 'Submitted service request '.$ticket);
        $this->flash('success', 'Request submitted. Your ticket number is '.$ticket.'. We will assign a technician shortly.');

        return redirect('/customer/requests/'.$requestId);
    }

    public function show(int $id)
    {
        $user = $this->authUser();
        $request = $this->findRequest($id, (int) $user->id);

        $history = db_all(
            'SELECT h.*, u.name AS changed_by_name
             FROM request_status_history h
             LEFT JOIN users u ON u.id = h.changed_by
             WHERE h.request_id = ? ORDER BY h.created_at ASC, h.id ASC',
            [$request['id']]
        );

        $appointment = db_one(
            'SELECT a.*, t.name AS technician_name
             FROM appointments a LEFT JOIN users t ON t.id = a.technician_id
             WHERE a.request_id = ? ORDER BY a.scheduled_date DESC, a.scheduled_time DESC LIMIT 1',
            [$request['id']]
        );

        $report = db_one('SELECT * FROM service_reports WHERE request_id = ?', [$request['id']]);
        $feedback = db_one('SELECT * FROM feedback WHERE request_id = ?', [$request['id']]);

        return view('customer.request-detail', [
            'title' => $request['ticket_no'],
            'request' => $request,
            'history' => $history,
            'appointment' => $appointment,
            'report' => $report,
            'feedback' => $feedback,
        ]);
    }

    public function cancel(int $id)
    {
        $user = $this->authUser();
        $request = $this->findRequest($id, (int) $user->id);

        if (! in_array($request['status'], ['pending', 'assigned', 'accepted'], true)) {
            $this->flash('error', 'This request can no longer be cancelled. Please contact support.');

            return redirect('/customer/requests/'.$request['id']);
        }

        db_exec("UPDATE service_requests SET status = 'cancelled', updated_at = NOW() WHERE id = ?", [$request['id']]);
        db_exec('DELETE FROM technician_live_locations WHERE request_id = ?', [$request['id']]);
        db_exec(
            "UPDATE appointments SET status = 'cancelled', updated_at = NOW() WHERE request_id = ? AND status IN ('scheduled', 'confirmed')",
            [$request['id']]
        );
        db_insert(
            'INSERT INTO request_status_history (request_id, status, note, changed_by, created_at) VALUES (?, ?, ?, ?, NOW())',
            [$request['id'], 'cancelled', 'Cancelled by the customer.', $user->id]
        );

        $this->log('cancel_request', 'Cancelled service request '.$request['ticket_no']);
        $this->flash('success', 'Request '.$request['ticket_no'].' has been cancelled.');

        return redirect('/customer/requests/'.$request['id']);
    }

    public function feedback(int $id)
    {
        $user = $this->authUser();
        $request = $this->findRequest($id, (int) $user->id);

        if ($request['status'] !== 'completed') {
            $this->flash('error', 'Feedback can be submitted once the job is completed.');

            return redirect('/customer/requests/'.$request['id']);
        }

        if (db_one('SELECT id FROM feedback WHERE request_id = ?', [$request['id']])) {
            $this->flash('error', 'Feedback has already been submitted for this request.');

            return redirect('/customer/requests/'.$request['id']);
        }

        $rating = (int) $this->input('rating', '0');
        $comment = $this->input('comment');

        if ($rating < 1 || $rating > 5) {
            $this->flash('error', 'Choose a rating between 1 and 5 stars.');

            return redirect('/customer/requests/'.$request['id']);
        }

        db_insert(
            'INSERT INTO feedback (request_id, customer_id, technician_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())',
            [$request['id'], $user->id, $request['technician_id'], $rating, $comment ?: null]
        );

        $this->log('feedback', 'Left a '.$rating.'-star review for '.$request['ticket_no']);
        $this->flash('success', 'Thank you for the feedback!');

        return redirect('/customer/requests/'.$request['id']);
    }

    private function findRequest(int $id, int $customerId): array
    {
        $request = db_one(
            'SELECT r.*, c.name AS category_name, c.icon AS category_icon, c.base_price,
                    t.name AS technician_name, t.phone AS technician_phone, t.specialty AS technician_specialty, t.avatar_color AS technician_color
             FROM service_requests r
             JOIN service_categories c ON c.id = r.category_id
             LEFT JOIN users t ON t.id = r.technician_id
             WHERE r.id = ? AND r.customer_id = ?',
            [$id, $customerId]
        );

        if (! $request) {
            $this->notFound('That service request could not be found.');
        }

        return $request;
    }
}
