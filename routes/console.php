<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

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

Artisan::command('admin:create-demo', function () {
    if (! filter_var((string) env('DEMO_MODE', false), FILTER_VALIDATE_BOOLEAN)) {
        $this->error('This command is only available when DEMO_MODE is enabled.');

        return 1;
    }

    $email = strtolower(trim((string) env('DEMO_ADMIN_EMAIL', '')));
    $name = trim((string) env('DEMO_ADMIN_NAME', 'FixitNow Demo Admin'));
    $password = (string) env('DEMO_ADMIN_PASSWORD', '');

    if (! filter_var($email, FILTER_VALIDATE_EMAIL) || $name === '' || mb_strlen($name) > 120) {
        $this->error('Set a valid demo admin email and name in the environment.');

        return 1;
    }

    if (mb_strlen($password) < 12) {
        $this->error('Set DEMO_ADMIN_PASSWORD to at least 12 characters.');

        return 1;
    }

    if (User::query()->where('email', $email)->exists()) {
        $this->info('Demo admin already exists; no account was changed.');

        return 0;
    }

    User::query()->create([
        'name' => $name,
        'email' => $email,
        'password_hash' => Hash::make($password),
        'role' => 'admin',
        'status' => 'active',
        'avatar_color' => '#4f46e5',
    ]);

    $this->info('Demo admin account created.');

    return 0;
})->purpose('Create the demo admin from private environment variables');
