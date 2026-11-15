<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class GmailApiTransportTest extends TestCase
{
    public function test_it_registers_the_gmail_api_mailer_and_retries_temporary_server_errors(): void
    {
        config([
            'mail.mailers.gmail-api' => [
                'transport' => 'gmail-api',
                'client_id' => 'client-id',
                'client_secret' => 'client-secret',
                'refresh_token' => 'refresh-token',
            ],
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-access-token']),
            'https://gmail.googleapis.com/gmail/v1/users/me/messages/send' => Http::sequence()
                ->push(['error' => ['message' => 'Temporary server error']], 500)
                ->push(['id' => 'sent-message'], 200),
        ]);

        Mail::mailer('gmail-api')->raw('Test message body', function ($message): void {
            $message->from('sender@example.com')
                ->to('recipient@example.com')
                ->subject('Test message');
        });

        Http::assertSentCount(3);
    }

    public function test_it_logs_only_safe_error_codes_when_gmail_rejects_a_message(): void
    {
        config([
            'mail.mailers.gmail-api' => [
                'transport' => 'gmail-api',
                'client_id' => 'client-id',
                'client_secret' => 'client-secret',
                'refresh_token' => 'refresh-token',
            ],
        ]);
        Cache::forget('gmail-api-access-token');

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-access-token']),
            'https://gmail.googleapis.com/gmail/v1/users/me/messages/send' => Http::response([
                'error' => [
                    'status' => 'PERMISSION_DENIED',
                    'errors' => [[
                        'reason' => 'insufficientPermissions',
                        'message' => 'Do not log this response message.',
                    ]],
                ],
            ], 403),
        ]);
        Log::spy();

        try {
            Mail::mailer('gmail-api')->raw('Private message body', function ($message): void {
                $message->from('sender@example.com')
                    ->to('recipient@example.com')
                    ->subject('Test message');
            });
            $this->fail('Expected the Gmail API rejection to throw.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Gmail API rejected the message (HTTP 403).', $exception->getMessage());
        }

        Log::shouldHaveReceived('error')
            ->once()
            ->with('Gmail API rejected an email message.', [
                'http_status' => 403,
                'error_status' => 'PERMISSION_DENIED',
                'error_reason' => 'insufficientPermissions',
            ]);
    }
}
