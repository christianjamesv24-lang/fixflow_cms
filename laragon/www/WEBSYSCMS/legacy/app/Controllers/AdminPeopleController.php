<?php

class AdminPeopleController extends Controller
{
    public function technicians(): void
    {
        $search = trim((string) ($_GET['q'] ?? ''));
        $status = $_GET['status'] ?? '';

        $where = ["u.role = 'technician'"];
        $params = [];

        if (in_array($status, ['active', 'inactive'], true)) {
            $where[] = 'u.status = ?';
            $params[] = $status;
        }

        if ($search !== '') {
            $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.specialty LIKE ?)';
            $like = '%'.$search.'%';
            array_push($params, $like, $like, $like);
        }

        $whereSql = implode(' AND ', $where);

        $technicians = Database::all(
            "SELECT u.*,
                    (SELECT COUNT(*) FROM service_requests r WHERE r.technician_id = u.id) AS jobs,
                    (SELECT COUNT(*) FROM service_requests r WHERE r.technician_id = u.id AND r.status = 'completed') AS completed,
                    (SELECT COUNT(*) FROM service_requests r WHERE r.technician_id = u.id AND r.status IN ('assigned','accepted','in_progress')) AS active_jobs,
                    (SELECT AVG(f.rating) FROM feedback f WHERE f.technician_id = u.id) AS rating
             FROM users u
             WHERE $whereSql
             ORDER BY u.name",
            $params
        );

        $this->view('admin/technicians', [
            'title' => 'Technicians',
            'technicians' => $technicians,
            'filters' => ['q' => $search, 'status' => $status],
        ], 'app');
    }

    public function technicianCreate(): void
    {
        $this->view('admin/technician-form', [
            'title' => 'Add technician',
            'technician' => null,
        ], 'app');
    }

    public function technicianStore(): void
    {
        $data = $this->technicianData();
        $errors = $this->validateTechnician($data, null);

        if ($errors) {
            $this->backWithErrors($errors, '/admin/technicians/create');
        }

        Database::insert(
            'INSERT INTO users (name, email, password_hash, phone, address, role, status, specialty, bio, avatar_color, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [
                $data['name'],
                $data['email'],
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['phone'] ?: null,
                $data['address'] ?: null,
                'technician',
                $data['status'],
                $data['specialty'] ?: null,
                $data['bio'] ?: null,
                $data['avatar_color'],
            ]
        );

        $this->log('create_technician', 'Added technician '.$data['name']);
        $this->flash('success', 'Technician '.$data['name'].' added successfully.');
        redirect('/admin/technicians');
    }

    public function technicianEdit(array $params): void
    {
        $technician = $this->findTechnician((int) ($params['id'] ?? 0));

        $this->view('admin/technician-form', [
            'title' => 'Edit technician',
            'technician' => $technician,
        ], 'app');
    }

    public function technicianUpdate(array $params): void
    {
        $technician = $this->findTechnician((int) ($params['id'] ?? 0));
        $data = $this->technicianData();
        $errors = $this->validateTechnician($data, (int) $technician['id']);

        if ($errors) {
            $this->backWithErrors($errors, '/admin/technicians/'.$technician['id'].'/edit');
        }

        $fields = 'name = ?, email = ?, phone = ?, address = ?, status = ?, specialty = ?, bio = ?, avatar_color = ?, updated_at = NOW()';
        $values = [
            $data['name'],
            $data['email'],
            $data['phone'] ?: null,
            $data['address'] ?: null,
            $data['status'],
            $data['specialty'] ?: null,
            $data['bio'] ?: null,
            $data['avatar_color'],
        ];

        if ($data['password'] !== '') {
            $fields .= ', password_hash = ?';
            $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $values[] = $technician['id'];
        Database::exec('UPDATE users SET '.$fields.' WHERE id = ?', $values);

        $this->log('update_technician', 'Updated technician '.$data['name']);
        $this->flash('success', 'Technician profile updated.');
        redirect('/admin/technicians');
    }

    public function technicianDelete(array $params): void
    {
        $technician = $this->findTechnician((int) ($params['id'] ?? 0));

        $activeJobs = (int) Database::value(
            "SELECT COUNT(*) FROM service_requests WHERE technician_id = ? AND status IN ('assigned','accepted','in_progress')",
            [$technician['id']]
        );

        if ($technician['status'] === 'active' && $activeJobs > 0) {
            $this->flash('error', 'Reassign this technician\'s '.$activeJobs.' active job(s) before deactivating the account.');
            back('/admin/technicians');
        }

        $newStatus = $technician['status'] === 'active' ? 'inactive' : 'active';
        Database::exec('UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?', [$newStatus, $technician['id']]);

        $this->log('technician_status', ucfirst($newStatus).' technician '.$technician['name']);
        $this->flash('success', $technician['name'].' is now '.$newStatus.'.');
        back('/admin/technicians');
    }

    public function customers(): void
    {
        $search = trim((string) ($_GET['q'] ?? ''));
        $status = $_GET['status'] ?? '';
        $page = (int) ($_GET['page'] ?? 1);
        $perPage = 12;

        $where = ["u.role = 'customer'"];
        $params = [];

        if (in_array($status, ['active', 'inactive'], true)) {
            $where[] = 'u.status = ?';
            $params[] = $status;
        }

        if ($search !== '') {
            $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
            $like = '%'.$search.'%';
            array_push($params, $like, $like, $like);
        }

        $whereSql = implode(' AND ', $where);
        $total = (int) Database::value("SELECT COUNT(*) FROM users u WHERE $whereSql", $params);
        $pagination = $this->paginate($total, $perPage, $page);

        $customers = Database::all(
            "SELECT u.*,
                    (SELECT COUNT(*) FROM service_requests r WHERE r.customer_id = u.id) AS jobs,
                    (SELECT COALESCE(SUM(r.final_cost), 0) FROM service_requests r WHERE r.customer_id = u.id AND r.status = 'completed') AS spent,
                    (SELECT MAX(r.created_at) FROM service_requests r WHERE r.customer_id = u.id) AS last_request
             FROM users u
             WHERE $whereSql
             ORDER BY u.created_at DESC
             LIMIT {$perPage} OFFSET {$pagination['offset']}",
            $params
        );

        $this->view('admin/customers', [
            'title' => 'Customers',
            'customers' => $customers,
            'filters' => ['q' => $search, 'status' => $status],
            'currentPage' => $pagination['page'],
            'totalPages' => $pagination['pages'],
            'total' => $total,
        ], 'app');
    }

    public function customerShow(array $params): void
    {
        $customer = Database::one("SELECT * FROM users WHERE id = ? AND role = 'customer'", [(int) ($params['id'] ?? 0)]);

        if (! $customer) {
            $this->notFound('That customer could not be found.');
        }

        $requests = Database::all(
            'SELECT r.*, c.name AS category_name, c.icon AS category_icon, t.name AS technician_name
             FROM service_requests r
             JOIN service_categories c ON c.id = r.category_id
             LEFT JOIN users t ON t.id = r.technician_id
             WHERE r.customer_id = ?
             ORDER BY r.created_at DESC',
            [$customer['id']]
        );

        $stats = [
            'total' => count($requests),
            'completed' => 0,
            'active' => 0,
            'spent' => 0.0,
        ];

        foreach ($requests as $request) {
            if ($request['status'] === 'completed') {
                $stats['completed']++;
                $stats['spent'] += (float) $request['final_cost'];
            }
            if (in_array($request['status'], ['pending', 'assigned', 'accepted', 'in_progress'], true)) {
                $stats['active']++;
            }
        }

        $upcoming = Database::one(
            "SELECT a.*, r.ticket_no, t.name AS technician_name
             FROM appointments a
             JOIN service_requests r ON r.id = a.request_id
             LEFT JOIN users t ON t.id = a.technician_id
             WHERE r.customer_id = ? AND a.scheduled_date >= CURDATE() AND a.status IN ('scheduled', 'confirmed')
             ORDER BY a.scheduled_date ASC, a.scheduled_time ASC LIMIT 1",
            [$customer['id']]
        );

        $this->view('admin/customer-detail', [
            'title' => $customer['name'],
            'customer' => $customer,
            'requests' => $requests,
            'stats' => $stats,
            'upcoming' => $upcoming,
        ], 'app');
    }

    private function findTechnician(int $id): array
    {
        $technician = Database::one("SELECT * FROM users WHERE id = ? AND role = 'technician'", [$id]);

        if (! $technician) {
            $this->notFound('That technician could not be found.');
        }

        return $technician;
    }

    private function technicianData(): array
    {
        return [
            'name' => $this->input('name'),
            'email' => strtolower($this->input('email')),
            'phone' => $this->input('phone'),
            'address' => $this->input('address'),
            'specialty' => $this->input('specialty'),
            'bio' => $this->input('bio'),
            'password' => $this->input('password'),
            'status' => in_array($this->input('status'), ['active', 'inactive'], true) ? $this->input('status') : 'active',
            'avatar_color' => preg_match('/^#[0-9a-fA-F]{6}$/', $this->input('avatar_color')) ? $this->input('avatar_color') : '#2563eb',
        ];
    }

    private function validateTechnician(array $data, ?int $ignoreId): array
    {
        $errors = [];

        if (mb_strlen($data['name']) < 3) {
            $errors['name'] = 'Enter the technician\'s full name.';
        }

        if (! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        } else {
            $existing = Database::one('SELECT id FROM users WHERE email = ?', [$data['email']]);
            if ($existing && (int) $existing['id'] !== (int) $ignoreId) {
                $errors['email'] = 'That email is already in use.';
            }
        }

        if ($ignoreId === null && mb_strlen($data['password']) < 8) {
            $errors['password'] = 'Set a password of at least 8 characters.';
        } elseif ($data['password'] !== '' && mb_strlen($data['password']) < 8) {
            $errors['password'] = 'Passwords must be at least 8 characters.';
        }

        return $errors;
    }
}
