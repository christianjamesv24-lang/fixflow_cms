<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
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
}
