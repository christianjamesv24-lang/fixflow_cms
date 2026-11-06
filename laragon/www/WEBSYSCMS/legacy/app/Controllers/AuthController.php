<?php

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect(dashboard_url());
        }

        $this->view('auth/login', [
            'title' => 'Sign in — '.setting('site_name', 'FixFlow'),
        ], 'guest');
    }

    public function login(): void
    {
        $email = strtolower($this->input('email'));
        $password = $this->input('password');

        $errors = [];

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        }

        if ($password === '') {
            $errors['password'] = 'Enter your password.';
        }

        if ($errors) {
            $this->backWithErrors($errors, '/login');
        }

        if (! Auth::attempt($email, $password)) {
            $_SESSION['_old'] = ['email' => $email];
            $this->flash('error', 'Those credentials do not match an active account.');
            back('/login');
        }

        $intended = $_SESSION['intended'] ?? '';
        unset($_SESSION['intended']);

        if ($intended !== '' && ! str_contains($intended, '/login')) {
            header('Location: '.$intended);
            exit;
        }

        $this->flash('success', 'Welcome back, '.Auth::user()['name'].'.');
        redirect(dashboard_url());
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            redirect(dashboard_url());
        }

        $this->view('auth/register', [
            'title' => 'Create an account — '.setting('site_name', 'FixFlow'),
        ], 'guest');
    }

    public function register(): void
    {
        $data = [
            'name' => $this->input('name'),
            'email' => strtolower($this->input('email')),
            'phone' => $this->input('phone'),
            'address' => $this->input('address'),
            'password' => $this->input('password'),
            'password_confirmation' => $this->input('password_confirmation'),
        ];

        $errors = [];

        if (mb_strlen($data['name']) < 3) {
            $errors['name'] = 'Please enter your full name.';
        }

        if (! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif (Database::one('SELECT id FROM users WHERE email = ?', [$data['email']])) {
            $errors['email'] = 'That email is already registered. Try signing in.';
        }

        if (mb_strlen($data['password']) < 8) {
            $errors['password'] = 'Use at least 8 characters for your password.';
        } elseif ($data['password'] !== $data['password_confirmation']) {
            $errors['password_confirmation'] = 'Passwords do not match.';
        }

        if ($errors) {
            $this->backWithErrors($errors, '/register');
        }

        $colors = ['#2563eb', '#7c3aed', '#db2777', '#059669', '#ea580c', '#0891b2'];
        $userId = Database::insert(
            'INSERT INTO users (name, email, password_hash, phone, address, role, status, avatar_color, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [
                $data['name'],
                $data['email'],
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['phone'] ?: null,
                $data['address'] ?: null,
                'customer',
                'active',
                $colors[array_rand($colors)],
            ]
        );

        $user = Database::one('SELECT * FROM users WHERE id = ?', [$userId]);
        Auth::loginAs($user);

        $this->flash('success', 'Your FixFlow account is ready. Submit your first service request anytime.');
        redirect('/customer');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->flash('success', 'You have been signed out.');
        redirect('/');
    }
}
