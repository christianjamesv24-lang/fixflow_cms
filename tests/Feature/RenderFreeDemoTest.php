<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RenderFreeDemoTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_dashboard_works_with_sqlite_compatibility_functions(): void
    {
        $admin = User::query()->create([
            'name' => 'Demo Admin',
            'email' => 'demo-admin@example.com',
            'password_hash' => Hash::make('DemoPassword!2026'),
            'role' => 'admin',
            'status' => 'active',
            'avatar_color' => '#4f46e5',
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/reports')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/cms')
            ->assertOk();
    }

    public function test_demo_admin_command_requires_demo_mode_and_creates_account_from_environment(): void
    {
        $environment = [
            'DEMO_MODE' => 'true',
            'DEMO_ADMIN_EMAIL' => 'demo-bootstrap@example.com',
            'DEMO_ADMIN_NAME' => 'Demo Bootstrap Admin',
            'DEMO_ADMIN_PASSWORD' => 'TemporaryDemoPassword!2026',
        ];

        foreach ($environment as $key => $value) {
            putenv($key.'='.$value);
        }

        try {
            $this->artisan('admin:create-demo')
                ->expectsOutput('Demo admin account created.')
                ->assertExitCode(0);

            $admin = User::query()->where('email', $environment['DEMO_ADMIN_EMAIL'])->firstOrFail();
            $this->assertSame('admin', $admin->role);
            $this->assertTrue(Hash::check($environment['DEMO_ADMIN_PASSWORD'], $admin->password_hash));
        } finally {
            foreach (array_keys($environment) as $key) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            }
        }
    }
}
