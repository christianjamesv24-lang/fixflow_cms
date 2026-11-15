<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminProvisioningTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_command_creates_the_requested_account_with_a_hashed_password(): void
    {
        $password = 'SecureTestPassword!2026';

        $this->artisan('admin:create', ['email' => 'fixitnow_admin@gmail.com'])
            ->expectsQuestion('Admin name', 'FixitNow Admin')
            ->expectsQuestion('Set an admin password (at least 12 characters)', $password)
            ->expectsQuestion('Confirm the admin password', $password)
            ->expectsOutput('Admin account created. Sign in with the email you provided and the password entered securely.')
            ->assertExitCode(0);

        $admin = User::query()->where('email', 'fixitnow_admin@gmail.com')->firstOrFail();

        $this->assertSame('admin', $admin->role);
        $this->assertSame('active', $admin->status);
        $this->assertTrue(Hash::check($password, $admin->getAuthPassword()));
        $this->assertNotSame($password, $admin->getAuthPassword());
    }

    public function test_admin_command_does_not_replace_an_existing_account(): void
    {
        DB::table('users')->insert([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password_hash' => Hash::make('ExistingPassword!2026'),
            'role' => 'customer',
            'status' => 'active',
            'avatar_color' => '#2563eb',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('admin:create', ['email' => 'existing@example.com'])
            ->expectsOutput('An account already uses that email. No account was changed.')
            ->assertExitCode(1);

        $this->assertDatabaseHas('users', [
            'email' => 'existing@example.com',
            'role' => 'customer',
        ]);
    }

    public function test_production_bootstrap_creates_an_admin_from_environment_without_exposing_the_password(): void
    {
        $password = 'BootstrapTestPassword!2026';
        $this->app->detectEnvironment(fn () => 'production');

        $this->withBootstrapEnvironment([
            'ADMIN_BOOTSTRAP_ONCE' => 'true',
            'ADMIN_BOOTSTRAP_EMAIL' => 'bootstrap-admin@example.test',
            'ADMIN_BOOTSTRAP_PASSWORD' => $password,
        ], function () use ($password) {
            $this->artisan('admin:bootstrap-from-env')
                ->expectsOutput('Admin account created. Remove the bootstrap environment variables from Render.')
                ->doesntExpectOutput($password)
                ->assertExitCode(0);
        });

        $admin = User::query()->where('email', 'bootstrap-admin@example.test')->firstOrFail();

        $this->assertSame('admin', $admin->role);
        $this->assertSame('active', $admin->status);
        $this->assertTrue(Hash::check($password, $admin->password_hash));
        $this->assertNotSame($password, $admin->password_hash);
    }

    public function test_production_bootstrap_never_changes_an_existing_account(): void
    {
        $passwordHash = Hash::make('ExistingPassword!2026');
        DB::table('users')->insert([
            'name' => 'Existing User',
            'email' => 'bootstrap-admin@example.test',
            'password_hash' => $passwordHash,
            'role' => 'customer',
            'status' => 'active',
            'avatar_color' => '#2563eb',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->app->detectEnvironment(fn () => 'production');

        $this->withBootstrapEnvironment([
            'ADMIN_BOOTSTRAP_ONCE' => 'true',
            'ADMIN_BOOTSTRAP_EMAIL' => 'bootstrap-admin@example.test',
            'ADMIN_BOOTSTRAP_PASSWORD' => 'AnotherBootstrapPassword!2026',
        ], function () {
            $this->artisan('admin:bootstrap-from-env')
                ->expectsOutput('Admin bootstrap skipped: an account already exists. No account was changed.')
                ->assertExitCode(0);
        });

        $user = User::query()->where('email', 'bootstrap-admin@example.test')->firstOrFail();

        $this->assertSame('customer', $user->role);
        $this->assertSame($passwordHash, $user->password_hash);
    }

    public function test_production_bootstrap_is_rejected_outside_production(): void
    {
        $this->app->detectEnvironment(fn () => 'testing');

        $this->withBootstrapEnvironment([
            'ADMIN_BOOTSTRAP_ONCE' => 'true',
            'ADMIN_BOOTSTRAP_EMAIL' => 'bootstrap-admin@example.test',
            'ADMIN_BOOTSTRAP_PASSWORD' => 'BootstrapTestPassword!2026',
        ], function () {
            $this->artisan('admin:bootstrap-from-env')
                ->expectsOutput('Admin bootstrap is only allowed in production. No account was changed.')
                ->assertExitCode(1);
        });

        $this->assertDatabaseMissing('users', ['email' => 'bootstrap-admin@example.test']);
    }

    public function test_only_active_admins_can_open_the_filament_panel(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'panel-admin@example.com',
            'password_hash' => Hash::make('SecureTestPassword!2026'),
            'role' => 'admin',
            'status' => 'active',
            'avatar_color' => '#4f46e5',
        ]);
        $technician = User::query()->create([
            'name' => 'Technician',
            'email' => 'panel-technician@example.com',
            'password_hash' => Hash::make('SecureTestPassword!2026'),
            'role' => 'technician',
            'status' => 'active',
            'avatar_color' => '#2563eb',
        ]);

        $this->actingAs($admin)->get('/cms')->assertOk();
        $this->actingAs($technician)->get('/cms')->assertForbidden();
    }

    private function withBootstrapEnvironment(array $variables, callable $callback): mixed
    {
        $originalValues = [];

        foreach ($variables as $name => $value) {
            $originalValues[$name] = getenv($name);
            putenv($name.'='.$value);
        }

        try {
            return $callback();
        } finally {
            foreach ($originalValues as $name => $value) {
                if ($value === false) {
                    putenv($name);
                } else {
                    putenv($name.'='.$value);
                }
            }
        }
    }
}
