<?php

namespace App\Mail\Transport;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Throwable;

class GmailApiTransport extends AbstractTransport
{
    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $refreshToken,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $raw = rtrim(strtr(base64_encode($message->getMessage()->toString()), '+/', '-_'), '=');

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->retry(3, 250, fn (Throwable $exception): bool => $this->shouldRetry($exception), false)
            ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', ['raw' => $raw]);

        if (! $response->successful()) {
            $context = ['http_status' => $response->status()];
            $errorStatus = $response->json('error.status');
            $errorReason = $response->json('error.errors.0.reason');

            if (is_string($errorStatus) && preg_match('/\A[A-Z_]+\z/', $errorStatus)) {
                $context['error_status'] = $errorStatus;
            }

            if (is_string($errorReason) && preg_match('/\A[A-Za-z][A-Za-z0-9_]*\z/', $errorReason)) {
                $context['error_reason'] = $errorReason;
            }

            Log::error('Gmail API rejected an email message.', $context);

            throw new RuntimeException('Gmail API rejected the message (HTTP '.$response->status().').');
        }
    }

    private function accessToken(): string
    {
        return Cache::remember('gmail-api-access-token', now()->addMinutes(50), function (): string {
            $response = Http::asForm()
                ->retry(3, 250, fn (Throwable $exception): bool => $this->shouldRetry($exception), false)
                ->post('https://oauth2.googleapis.com/token', [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $this->refreshToken,
                    'grant_type' => 'refresh_token',
                ]);

            if (! $response->successful() || ! is_string($response->json('access_token'))) {
                Log::error('Google OAuth token request failed for Gmail API mail.', ['http_status' => $response->status()]);

                throw new RuntimeException('Google OAuth could not issue a Gmail API access token (HTTP '.$response->status().').');
            }

            return $response->json('access_token');
        });
    }

    private function shouldRetry(Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        return $exception instanceof RequestException
            && ($exception->response->status() === 408
                || $exception->response->status() === 429
                || $exception->response->serverError());
    }

    public function __toString(): string
    {
        return 'gmail-api';
    }
}
