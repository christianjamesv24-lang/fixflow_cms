<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('admin:create {email=fixitnow_admin@gmail.com}', function (string $email) {
    $email = strtolower(trim($email));

    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Enter a valid email address.');

        return 1;
    }

    if (User::query()->where('email', $email)->exists()) {
        $this->error('An account already uses that email. No account was changed.');

        return 1;
    }

    $name = trim((string) $this->ask('Admin name', 'FixitNow Admin'));
    if ($name === '' || mb_strlen($name) > 120) {
        $this->error('Enter a name between 1 and 120 characters.');

        return 1;
    }

    $password = (string) $this->secret('Set an admin password (at least 12 characters)');
    $confirmation = (string) $this->secret('Confirm the admin password');

    if (mb_strlen($password) < 12 || ! hash_equals($password, $confirmation)) {
        $this->error('Passwords must match and be at least 12 characters. No account was created.');

        return 1;
    }

    User::query()->create([
        'name' => $name,
        'email' => $email,
        'password_hash' => Hash::make($password),
        'role' => 'admin',
        'status' => 'active',
        'avatar_color' => '#4f46e5',
    ]);

    $this->info('Admin account created. Sign in with the email you provided and the password entered securely.');

    return 0;
})->purpose('Create an admin account without a hardcoded password');

Artisan::command('admin:bootstrap-from-env', function () {
    if (! app()->environment('production')) {
        $this->error('Admin bootstrap is only allowed in production. No account was changed.');

        return 1;
    }

    if (! filter_var((string) getenv('ADMIN_BOOTSTRAP_ONCE'), FILTER_VALIDATE_BOOLEAN)) {
        $this->error('Admin bootstrap is not enabled. No account was changed.');

        return 1;
    }

    $email = strtolower(trim((string) getenv('ADMIN_BOOTSTRAP_EMAIL')));
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('ADMIN_BOOTSTRAP_EMAIL must be a valid email address. No account was changed.');

        return 1;
    }

    if (User::query()->where('email', $email)->exists()) {
        $this->info('Admin bootstrap skipped: an account already exists. No account was changed.');

        return 0;
    }

    $password = (string) getenv('ADMIN_BOOTSTRAP_PASSWORD');
    if (mb_strlen($password) < 12) {
        $this->error('ADMIN_BOOTSTRAP_PASSWORD must be at least 12 characters. No account was changed.');

        return 1;
    }

    User::query()->create([
        'name' => 'FixitNow Admin',
        'email' => $email,
        'password_hash' => Hash::make($password),
        'role' => 'admin',
        'status' => 'active',
        'avatar_color' => '#4f46e5',
    ]);

    $this->info('Admin account created. Remove the bootstrap environment variables from Render.');

    return 0;
})->purpose('Create the first production admin from one-time deployment environment variables');
