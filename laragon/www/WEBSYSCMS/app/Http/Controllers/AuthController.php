<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

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

    public function showForgotPassword()
    {
        return view('auth.forgot-password', [
            'title' => 'Forgot password — '.setting('site_name', 'FixitNow'),
        ]);
    }

    public function sendPasswordResetLink()
    {
        $email = strtolower($this->input('email'));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->backWithErrors(['email' => 'Enter a valid email address.'], '/forgot-password');
        }

        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            $this->flash('error', 'Password recovery email is not configured. Please contact the service administrator.');

            return redirect('/forgot-password')->withInput(['email' => $email]);
        }

        try {
            Password::sendResetLink(['email' => $email]);
        } catch (\Throwable $exception) {
            Log::error('Password reset email delivery failed.', [
                'mailer' => config('mail.default'),
                'exception' => $exception::class,
            ]);
        }

        $this->flash('success', 'If an account exists for that email, a password recovery link will be sent.');

        return redirect('/forgot-password');
    }

    public function showResetPassword(string $token)
    {
        return view('auth.reset-password', [
            'title' => 'Reset password — '.setting('site_name', 'FixitNow'),
            'email' => strtolower($this->input('email')),
            'token' => $token,
        ]);
    }

    public function resetPassword(string $token)
    {
        $email = strtolower($this->input('email'));
        $password = request()->input('password');
        $confirmation = request()->input('password_confirmation');
        $errors = [];

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        }

        if (! is_string($password) || mb_strlen($password) < 8) {
            $errors['password'] = 'Use at least 8 characters.';
        } elseif (! is_string($confirmation) || $password !== $confirmation) {
            $errors['password_confirmation'] = 'Passwords do not match.';
        }

        if ($errors) {
            return $this->backWithErrors($errors, route('password.reset', ['token' => $token, 'email' => $email]));
        }

        $status = Password::reset(
            [
                'email' => $email,
                'token' => $token,
                'password' => $password,
                'password_confirmation' => $confirmation,
            ],
            function (User $user, string $password): void {
                $user->forceFill(['password_hash' => Hash::make($password)])->save();
                event(new PasswordReset($user));
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            $this->flash('success', 'Your password has been reset. You can now sign in.');

            return redirect('/login');
        }

        $this->flash('error', 'That password recovery link is invalid or expired. Request a new one.');

        return redirect()->route('password.reset', ['token' => $token, 'email' => $email]);
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
            'role' => request()->input('role', 'customer'),
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

        if (! in_array($data['role'], ['customer', 'technician'], true)) {
            $errors['role'] = 'Choose a valid account type.';
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
            'INSERT INTO users (name, email, password_hash, phone, address, role, status, avatar_color, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['name'],
                $data['email'],
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['phone'] ?: null,
                $data['address'] ?: null,
                $data['role'],
                'active',
                $colors[array_rand($colors)],
                now(),
            ]
        );

        $user = User::find($userId);
        Auth::login($user);
        request()->session()->regenerate();
        log_activity('login', 'Signed in to the portal');

        if ($data['role'] === 'technician') {
            $this->flash('success', 'Your technician account is registered. Submit your subscription payment for admin review; your account and subscription will be approved together.');

            return redirect('/technician/subscription');
        }

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
