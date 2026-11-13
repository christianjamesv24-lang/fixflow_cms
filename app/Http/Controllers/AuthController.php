<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (auth()->check()) {
            return redirect(dashboard_url());
        }

        return view('auth.login', [
            'title' => 'Sign in — '.setting('site_name', 'FixitNow'),
        ]);
    }

    public function login()
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
            return $this->backWithErrors($errors, '/login');
        }

        if (! Auth::attempt(['email' => $email, 'password' => $password, 'status' => 'active'])) {
            flash('error', 'Those credentials do not match an active account.');

            return redirect('/login')->withInput(['email' => $email]);
        }

        request()->session()->regenerate();

        $intended = session()->pull('url.intended', '');

        if ($intended !== '' && ! str_contains($intended, '/login')) {
            return redirect()->to($intended);
        }

        $this->flash('success', 'Welcome back, '.auth()->user()->name.'.');

        return redirect(dashboard_url());
    }

    public function showRegister()
    {
        if (auth()->check()) {
            return redirect(dashboard_url());
        }

        return view('auth.register', [
            'title' => 'Create an account — '.setting('site_name', 'FixitNow'),
        ]);
    }

    public function register()
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
        } elseif (db_one('SELECT id FROM users WHERE email = ?', [$data['email']])) {
            $errors['email'] = 'That email is already registered. Try signing in.';
        }

        if (mb_strlen($data['password']) < 8) {
            $errors['password'] = 'Use at least 8 characters for your password.';
        } elseif ($data['password'] !== $data['password_confirmation']) {
            $errors['password_confirmation'] = 'Passwords do not match.';
        }

        if ($errors) {
            return $this->backWithErrors($errors, '/register');
        }

        $colors = ['#2563eb', '#7c3aed', '#db2777', '#059669', '#ea580c', '#0891b2'];
        $userId = db_insert(
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

        $user = User::find($userId);
        Auth::login($user);
        request()->session()->regenerate();
        log_activity('login', 'Signed in to the portal');

        $this->flash('success', 'Your FixitNow account is ready. Submit your first service request anytime.');

        return redirect('/customer');
    }

    public function logout()
    {
        log_activity('logout', 'Signed out of the portal');
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $this->flash('success', 'You have been signed out.');

        return redirect('/');
    }
}
