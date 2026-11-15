<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_links_to_password_recovery(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee(route('password.request'));

        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Send recovery email');
    }

    public function test_password_recovery_emails_a_reset_link_without_revealing_unknown_emails(): void
    {
        Notification::fake();
        config(['mail.default' => 'smtp']);
        $user = $this->createUser();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('_toasts', fn (array $toasts): bool => str_contains($toasts[0]['message'], 'If an account exists'));

        Notification::assertSentTo($user, ResetPassword::class);

        Notification::fake();

        $this->post('/forgot-password', ['email' => 'unknown@example.com'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('_toasts', fn (array $toasts): bool => str_contains($toasts[0]['message'], 'If an account exists'));

        Notification::assertNothingSent();
    }

    public function test_password_recovery_handles_delivery_failures_without_exposing_account_status(): void
    {
        config(['mail.default' => 'resend']);
        $user = $this->createUser();
        $exception = new \RuntimeException('private transport details');

        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => $user->email])
            ->andThrow($exception);

        Log::spy();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('_toasts', fn (array $toasts): bool => str_contains($toasts[0]['message'], 'If an account exists'));

        Log::shouldHaveReceived('error')
            ->once()
            ->with('Password reset email delivery failed.', [
                'mailer' => 'resend',
                'exception' => \RuntimeException::class,
            ]);
    }

    public function test_password_recovery_does_not_log_reset_tokens_when_mail_is_unconfigured(): void
    {
        Notification::fake();
        config(['mail.default' => 'log']);
        $user = $this->createUser();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('_toasts', fn (array $toasts): bool => str_contains($toasts[0]['message'], 'email is not configured'));

        Notification::assertNothingSent();
    }

    public function test_valid_reset_token_updates_the_custom_password_hash(): void
    {
        $user = $this->createUser();
        $token = Password::createToken($user);
        $password = 'NewSecurePassword!2026';

        $this->post('/reset-password/'.$token, [
            'email' => $user->email,
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertRedirect('/login');

        $this->assertTrue(Hash::check($password, $user->fresh()->password_hash));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_invalid_reset_token_does_not_change_the_password(): void
    {
        $user = $this->createUser();
        $originalPasswordHash = $user->password_hash;
        $token = 'invalid-token';

        $this->post('/reset-password/'.$token, [
            'email' => $user->email,
            'password' => 'NewSecurePassword!2026',
            'password_confirmation' => 'NewSecurePassword!2026',
        ])->assertRedirect(route('password.reset', ['token' => $token, 'email' => $user->email]));

        $this->assertSame($originalPasswordHash, $user->fresh()->password_hash);
    }

    private function createUser(): User
    {
        return User::query()->create([
            'name' => 'Recovery Test User',
            'email' => 'recovery@example.com',
            'password_hash' => Hash::make('CurrentPassword!2026'),
            'role' => 'customer',
            'status' => 'active',
            'avatar_color' => '#2563eb',
        ]);
    }
}
