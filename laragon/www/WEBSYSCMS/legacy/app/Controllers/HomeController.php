<?php

class HomeController extends Controller
{
    public function index(): void
    {
        $stats = [
            'completed' => (int) Database::value("SELECT COUNT(*) FROM service_requests WHERE status = 'completed'"),
            'technicians' => (int) Database::value("SELECT COUNT(*) FROM users WHERE role = 'technician' AND status = 'active'"),
            'customers' => (int) Database::value("SELECT COUNT(*) FROM users WHERE role = 'customer' AND status = 'active'"),
            'rating' => round((float) Database::value('SELECT COALESCE(AVG(rating), 0) FROM feedback'), 1),
        ];

        $categories = Database::all(
            "SELECT c.*, (SELECT COUNT(*) FROM service_requests r WHERE r.category_id = c.id) AS request_count
             FROM service_categories c WHERE c.status = 'active' ORDER BY c.name"
        );

        $testimonials = Database::all(
            'SELECT f.rating, f.comment, f.created_at, u.name AS customer_name, r.ticket_no
             FROM feedback f
             JOIN users u ON u.id = f.customer_id
             JOIN service_requests r ON r.id = f.request_id
             WHERE f.rating >= 4 AND f.comment IS NOT NULL
             ORDER BY f.rating DESC, f.created_at DESC LIMIT 6'
        );

        $this->view('home/index', [
            'title' => setting('site_name', 'FixFlow').' — '.setting('site_tagline', 'Appliance repair, organized.'),
            'stats' => $stats,
            'categories' => $categories,
            'testimonials' => $testimonials,
        ]);
    }

    public function services(): void
    {
        $categories = Database::all(
            "SELECT c.*, (SELECT COUNT(*) FROM service_requests r WHERE r.category_id = c.id) AS request_count
             FROM service_categories c WHERE c.status = 'active' ORDER BY c.name"
        );

        $this->view('home/services', [
            'title' => 'Services — '.setting('site_name', 'FixFlow'),
            'categories' => $categories,
        ]);
    }

    public function serviceShow(string $slug): void
    {
        $category = Database::one(
            "SELECT * FROM service_categories WHERE slug = ? AND status = 'active'",
            [$slug]
        );

        if (! $category) {
            $this->notFound('That service could not be found.');
        }

        $completed = (int) Database::value(
            "SELECT COUNT(*) FROM service_requests WHERE category_id = ? AND status = 'completed'",
            [$category['id']]
        );

        $related = Database::all(
            "SELECT * FROM service_categories WHERE status = 'active' AND id != ? ORDER BY RAND() LIMIT 3",
            [$category['id']]
        );

        $this->view('home/service-detail', [
            'title' => $category['name'].' Service — '.setting('site_name', 'FixFlow'),
            'category' => $category,
            'completed' => $completed,
            'related' => $related,
        ]);
    }

    public function about(): void
    {
        $page = Database::one("SELECT * FROM cms_pages WHERE slug = 'about' AND status = 'published'");

        $this->view('home/about', [
            'title' => 'About — '.setting('site_name', 'FixFlow'),
            'page' => $page,
        ]);
    }

    public function contact(): void
    {
        $this->view('home/contact', [
            'title' => 'Contact — '.setting('site_name', 'FixFlow'),
        ]);
    }

    public function contactStore(): void
    {
        $data = [
            'name' => $this->input('name'),
            'email' => $this->input('email'),
            'phone' => $this->input('phone'),
            'subject' => $this->input('subject'),
            'message' => $this->input('message'),
        ];

        $errors = [];

        if ($data['name'] === '') {
            $errors['name'] = 'Please enter your name.';
        }

        if (! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        }

        if ($data['subject'] === '') {
            $errors['subject'] = 'Please add a subject.';
        }

        if (mb_strlen($data['message']) < 10) {
            $errors['message'] = 'Tell us a little more (at least 10 characters).';
        }

        if ($errors) {
            $this->backWithErrors($errors, '/contact');
        }

        Database::insert(
            'INSERT INTO contact_messages (name, email, phone, subject, message, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [$data['name'], $data['email'], $data['phone'] ?: null, $data['subject'], $data['message'], 'new']
        );

        $this->log('contact_message', 'New contact message from '.$data['name']);
        $this->flash('success', 'Thanks! Your message is with our team. We usually reply within one business day.');
        redirect('/contact');
    }

    public function page(string $slug): void
    {
        $page = Database::one(
            "SELECT * FROM cms_pages WHERE slug = ? AND status = 'published'",
            [$slug]
        );

        if (! $page) {
            $this->notFound('That page could not be found.');
        }

        $this->view('home/page', [
            'title' => ($page['meta_title'] ?: $page['title']).' — '.setting('site_name', 'FixFlow'),
            'page' => $page,
        ]);
    }
}
