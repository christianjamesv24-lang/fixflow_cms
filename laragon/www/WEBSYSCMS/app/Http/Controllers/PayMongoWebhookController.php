<?php

namespace App\Http\Controllers;

use App\Services\TechnicianSubscriptionActivator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayMongoWebhookController extends Controller
{
    public function __invoke(Request $request, TechnicianSubscriptionActivator $activator)
    {
        $secret = (string) config('services.paymongo.webhook_secret');
        if ($secret === '' || ! $this->hasValidSignature($request, $secret)) {
            return response()->json(['message' => 'Invalid webhook signature.'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        $event = data_get($payload, 'data.attributes') ?? data_get($payload, 'data');
        $eventType = data_get($event, 'type');

        if ($eventType !== 'checkout_session.payment.paid') {
            return response()->json(['received' => true]);
        }

        $session = data_get($event, 'data');
        $eventId = data_get($payload, 'data.id');
        if (! is_string($eventId) || $eventId === '') {
            $eventId = hash('sha256', $request->getContent());
        }
        $liveMode = data_get($event, 'livemode', data_get($session, 'attributes.livemode'));
        $expectedLiveMode = str_starts_with((string) config('services.paymongo.secret_key'), 'sk_live_');
        $sessionId = data_get($session, 'id');
        $reference = data_get($session, 'attributes.reference_number');
        $payments = data_get($session, 'attributes.payments', []);

        if (! is_string($sessionId) || ! is_string($reference)
            || ! is_array($payments) || ! is_bool($liveMode) || $liveMode !== $expectedLiveMode) {
            logger()->warning('Ignored a malformed or wrong-mode PayMongo checkout webhook.', [
                'event_id' => is_string($eventId) ? $eventId : null,
            ]);

            return response()->json(['received' => true]);
        }

        $result = DB::transaction(function () use (
            $eventId,
            $eventType,
            $sessionId,
            $reference,
            $payments,
            $activator
        ) {
            if (DB::table('paymongo_webhook_events')->where('event_id', $eventId)->exists()) {
                return 'duplicate';
            }

            try {
                DB::table('paymongo_webhook_events')->insert([
                    'event_id' => $eventId,
                    'event_type' => $eventType,
                    'created_at' => now(),
                ]);
            } catch (UniqueConstraintViolationException) {
                return 'duplicate';
            }

            $payment = DB::table('subscription_payments')
                ->join('subscriptions', 'subscriptions.id', '=', 'subscription_payments.subscription_id')
                ->where('subscription_payments.gcash_reference', $reference)
                ->select(
                    'subscription_payments.id',
                    'subscription_payments.status',
                    'subscription_payments.payment_provider',
                    'subscription_payments.provider_checkout_id',
                    'subscriptions.amount'
                )
                ->lockForUpdate()
                ->first();

            if (! $payment || $payment->payment_provider !== 'paymongo'
                || $payment->provider_checkout_id !== $sessionId
                || $payment->status !== 'pending') {
                return 'unmatched';
            }

            $expectedAmount = (int) round((float) $payment->amount * 100);
            $paid = collect($payments)->first(function ($item) use ($expectedAmount) {
                return data_get($item, 'attributes.status') === 'paid'
                    && strtoupper((string) data_get($item, 'attributes.currency')) === 'PHP'
                    && (int) data_get($item, 'attributes.amount') === $expectedAmount
                    && data_get($item, 'attributes.source.type') === 'gcash'
                    && is_string(data_get($item, 'id'));
            });

            if (! $paid || ! $activator->approveFromPayMongo($payment->id, $paid['id'])) {
                return 'unverified';
            }

            return 'approved';
        });

        if (! in_array($result, ['approved', 'duplicate'], true)) {
            logger()->warning('PayMongo checkout webhook did not match a pending subscription payment.', [
                'event_id' => $eventId,
                'result' => $result,
            ]);
        }

        return response()->json(['received' => true]);
    }

    private function hasValidSignature(Request $request, string $secret): bool
    {
        $key = (string) config('services.paymongo.secret_key');
        $mode = str_starts_with($key, 'sk_live_') ? 'live' : (str_starts_with($key, 'sk_test_') ? 'test' : null);
        if ($mode === null) {
            return false;
        }

        $parts = [];
        foreach (explode(',', (string) $request->header('Paymongo-Signature')) as $part) {
            [$name, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            $parts[$name] = $value;
        }

        $timestamp = $parts['t'] ?? '';
        $signature = $parts[$mode === 'live' ? 'li' : 'te'] ?? '';
        if ($timestamp === '' || $signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }
}
