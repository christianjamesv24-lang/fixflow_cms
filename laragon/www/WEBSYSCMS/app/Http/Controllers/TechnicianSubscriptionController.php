<?php

namespace App\Http\Controllers;

use App\Services\TechnicianSubscriptionActivator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TechnicianSubscriptionController extends Controller
{
    public function show()
    {
        $userId = (int) auth()->id();
        $selectedPlan = request()->query('plan', 'monthly');
        if (! is_string($selectedPlan) || ! array_key_exists($selectedPlan, config('subscriptions.prices'))) {
            $selectedPlan = 'monthly';
        }

        $subscription = DB::table('subscriptions')
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->where('current_period_end', '>', now())
            ->orderByDesc('current_period_end')
            ->first();

        $pendingPayment = DB::table('subscription_payments')
            ->join('subscriptions', 'subscriptions.id', '=', 'subscription_payments.subscription_id')
            ->where('subscription_payments.user_id', $userId)
            ->where('subscription_payments.status', 'pending')
            ->select('subscription_payments.*', 'subscriptions.plan')
            ->latest('subscription_payments.id')
            ->first();

        $latestPayment = DB::table('subscription_payments')
            ->join('subscriptions', 'subscriptions.id', '=', 'subscription_payments.subscription_id')
            ->where('subscription_payments.user_id', $userId)
            ->select('subscription_payments.*', 'subscriptions.plan')
            ->latest('subscription_payments.id')
            ->first();

        return view('technician.subscription', [
            'title' => 'Technician subscription',
            'subscription' => $subscription,
            'pendingPayment' => $pendingPayment,
            'latestPayment' => $latestPayment,
            'prices' => config('subscriptions.prices'),
            'gcash' => config('subscriptions.gcash'),
            'accountApproved' => auth()->user()->technician_approved_at !== null,
            'selectedPlan' => $selectedPlan,
            'demoMode' => $this->demoMode(),
            'paymongoReady' => filled(config('services.paymongo.secret_key'))
                && filled(config('services.paymongo.webhook_secret')),
        ]);
    }

    public function startPayMongoCheckout()
    {
        if ($this->demoMode()) {
            return $this->startDemoCheckout();
        }

        $secretKey = config('services.paymongo.secret_key');
        $webhookSecret = config('services.paymongo.webhook_secret');

        if (! $secretKey || ! $webhookSecret) {
            $this->flash('error', 'Online payment is not configured yet. Contact the administrator.');

            return redirect('/technician/subscription');
        }

        $plans = config('subscriptions.prices');
        $plan = $this->input('plan');

        if (! is_string($plan) || ! array_key_exists($plan, $plans)) {
            return $this->backWithErrors(['plan' => 'Choose a valid subscription plan.'], '/technician/subscription');
        }

        $pendingPayment = DB::table('subscription_payments')
            ->join('subscriptions', 'subscriptions.id', '=', 'subscription_payments.subscription_id')
            ->where('subscription_payments.user_id', auth()->id())
            ->where('subscription_payments.status', 'pending')
            ->select('subscription_payments.*', 'subscriptions.plan')
            ->latest('subscription_payments.id')
            ->first();

        if ($pendingPayment) {
            if ($pendingPayment->payment_provider === 'paymongo' && $pendingPayment->checkout_url) {
                return redirect()->away($pendingPayment->checkout_url);
            }

            if ($pendingPayment->payment_provider !== 'paymongo') {
                $this->flash('info', 'You already have a payment awaiting review.');

                return redirect('/technician/subscription');
            }

            $plan = $pendingPayment->plan;
            $checkout = [
                'payment_id' => $pendingPayment->id,
                'reference' => $pendingPayment->gcash_reference,
            ];
        } else {
            $checkout = DB::transaction(function () use ($plan, $plans) {
                DB::table('users')->where('id', auth()->id())->lockForUpdate()->first();

                $pendingPayment = DB::table('subscription_payments')
                    ->where('user_id', auth()->id())
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->first();

                if ($pendingPayment) {
                    return null;
                }

                $subscriptionId = DB::table('subscriptions')->insertGetId([
                    'user_id' => auth()->id(),
                    'plan' => $plan,
                    'amount' => $plans[$plan],
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $reference = 'SUB-'.Str::upper((string) Str::uuid());
                $paymentId = DB::table('subscription_payments')->insertGetId([
                    'subscription_id' => $subscriptionId,
                    'user_id' => auth()->id(),
                    'gcash_reference' => $reference,
                    'proof_path' => '',
                    'status' => 'pending',
                    'payment_provider' => 'paymongo',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return [
                    'payment_id' => $paymentId,
                    'reference' => $reference,
                ];
            });
        }

        if (! $checkout) {
            $this->flash('info', 'You already have a payment awaiting review.');

            return redirect('/technician/subscription');
        }

        try {
            $response = Http::acceptJson()
                ->withBasicAuth($secretKey, '')
                ->withHeaders(['Idempotency-Key' => 'technician-subscription-'.$checkout['payment_id']])
                ->timeout(20)
                ->post(config('services.paymongo.api_url').'/checkout_sessions', [
                    'data' => [
                        'attributes' => [
                            'line_items' => [[
                                'name' => ucfirst($plan).' technician subscription',
                                'amount' => (int) round($plans[$plan] * 100),
                                'currency' => 'PHP',
                                'quantity' => 1,
                            ]],
                            'payment_method_types' => ['gcash'],
                            'success_url' => url('/technician/subscription?payment=returned'),
                            'cancel_url' => url('/technician/subscription?payment=cancelled'),
                            'reference_number' => $checkout['reference'],
                            'send_email_receipt' => true,
                            'metadata' => [
                                'subscription_payment_id' => (string) $checkout['payment_id'],
                            ],
                        ],
                    ],
                ]);
            $response->throw();
        } catch (ConnectionException|RequestException $exception) {
            if ($exception instanceof RequestException) {
                $this->markCheckoutFailed($checkout['payment_id']);
            }
            logger()->error('PayMongo checkout creation failed.', [
                'subscription_payment_id' => $checkout['payment_id'],
                'exception' => $exception::class,
            ]);
            $this->flash(
                'error',
                $exception instanceof ConnectionException
                    ? 'PayMongo could not confirm the checkout response. Please retry; your payment has not been approved.'
                    : 'PayMongo could not start checkout. No payment was taken; please try again later.'
            );

            return redirect('/technician/subscription?plan='.$plan);
        }

        $checkoutId = data_get($response->json(), 'data.id');
        $checkoutUrl = data_get($response->json(), 'data.attributes.checkout_url');

        if (! is_string($checkoutId) || ! is_string($checkoutUrl)
            || parse_url($checkoutUrl, PHP_URL_SCHEME) !== 'https'
            || parse_url($checkoutUrl, PHP_URL_HOST) !== 'checkout.paymongo.com') {
            $this->markCheckoutFailed($checkout['payment_id']);
            logger()->error('PayMongo returned an invalid checkout session response.', [
                'subscription_payment_id' => $checkout['payment_id'],
            ]);
            $this->flash('error', 'PayMongo did not return a valid checkout page. Please try again later.');

            return redirect('/technician/subscription?plan='.$plan);
        }

        DB::table('subscription_payments')
            ->where('id', $checkout['payment_id'])
            ->where('status', 'pending')
            ->update([
                'provider_checkout_id' => $checkoutId,
                'checkout_url' => $checkoutUrl,
                'updated_at' => now(),
            ]);

        return redirect()->away($checkoutUrl);
    }

    public function showDemoCheckout(int $paymentId)
    {
        abort_unless($this->demoMode(), 404);

        $payment = DB::table('subscription_payments')
            ->join('subscriptions', 'subscriptions.id', '=', 'subscription_payments.subscription_id')
            ->where('subscription_payments.id', $paymentId)
            ->where('subscription_payments.user_id', auth()->id())
            ->where('subscription_payments.payment_provider', 'demo')
            ->select('subscription_payments.*', 'subscriptions.plan', 'subscriptions.amount')
            ->first();

        abort_unless($payment && $payment->status === 'pending', 404);

        return view('technician.demo-checkout', [
            'title' => 'Demo checkout',
            'payment' => $payment,
        ]);
    }

    public function completeDemoCheckout(int $paymentId, TechnicianSubscriptionActivator $activator)
    {
        abort_unless($this->demoMode(), 404);

        $payment = DB::table('subscription_payments')
            ->where('id', $paymentId)
            ->where('user_id', auth()->id())
            ->where('payment_provider', 'demo')
            ->first();

        abort_unless($payment, 404);

        if (! $activator->approveFromDemo($paymentId)) {
            $this->flash('error', 'This demo payment is no longer pending.');

            return redirect('/technician/subscription');
        }

        $this->flash('success', 'Demo payment completed. No money was charged; the subscription and technician account are active for this demonstration.');

        return redirect('/technician/subscription');
    }

    public function cancelDemoCheckout(int $paymentId)
    {
        abort_unless($this->demoMode(), 404);

        $cancelled = DB::transaction(function () use ($paymentId) {
            $payment = DB::table('subscription_payments')
                ->where('id', $paymentId)
                ->where('user_id', auth()->id())
                ->where('payment_provider', 'demo')
                ->lockForUpdate()
                ->first();

            if (! $payment || $payment->status !== 'pending') {
                return false;
            }

            DB::table('subscription_payments')->where('id', $paymentId)->update([
                'status' => 'cancelled',
                'updated_at' => now(),
            ]);
            DB::table('subscriptions')->where('id', $payment->subscription_id)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled', 'updated_at' => now()]);

            return true;
        });

        if (! $cancelled) {
            $this->flash('info', 'This demo checkout is no longer pending.');
        } else {
            $this->flash('info', 'Demo checkout cancelled. No payment was made.');
        }

        return redirect('/technician/subscription');
    }

    public function submitPayment()
    {
        if (filled(config('services.paymongo.secret_key')) && filled(config('services.paymongo.webhook_secret'))) {
            $this->flash('error', 'Use the secure PayMongo checkout to submit your subscription payment.');

            return redirect('/technician/subscription');
        }

        $plans = config('subscriptions.prices');
        $plan = $this->input('plan');
        $reference = strtoupper($this->input('gcash_reference'));
        $errors = [];

        if (! array_key_exists($plan, $plans)) {
            $errors['plan'] = 'Choose a valid subscription plan.';
        }

        if (mb_strlen($reference) < 6 || mb_strlen($reference) > 40) {
            $errors['gcash_reference'] = 'Enter the GCash transaction reference (6 to 40 characters).';
        } elseif (DB::table('subscription_payments')->where('gcash_reference', $reference)->exists()) {
            $errors['gcash_reference'] = 'That GCash reference has already been submitted.';
        }

        if (! config('subscriptions.gcash.account_name') || ! config('subscriptions.gcash.account_number')) {
            $errors['gcash_reference'] = 'GCash payment details are not configured yet. Contact the administrator.';
        }

        $proofValidator = validator(request()->all(), [
            'proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        if ($proofValidator->fails()) {
            $errors['proof'] = $proofValidator->errors()->first('proof');
        }

        if ($errors) {
            return $this->backWithErrors($errors, '/technician/subscription');
        }

        if (DB::table('subscription_payments')->where('user_id', auth()->id())->where('status', 'pending')->exists()) {
            $this->flash('info', 'You already have a payment awaiting review.');

            return redirect('/technician/subscription');
        }

        $proofDisk = config('subscriptions.proof_disk');
        $proofFile = request()->file('proof');
        $proofPath = $proofFile->store('subscription-proofs/'.auth()->id(), [
            'disk' => $proofDisk,
            'visibility' => 'private',
        ]);

        if (! $proofPath) {
            return $this->backWithErrors(
                ['proof' => 'The receipt image could not be saved. Please try again or contact the administrator.'],
                '/technician/subscription'
            );
        }

        $paymentSaved = false;
        try {
            $paymentSaved = DB::transaction(function () use ($plan, $reference, $plans, $proofPath) {
                DB::table('users')->where('id', auth()->id())->lockForUpdate()->first();

                $hasPendingPayment = DB::table('subscription_payments')
                    ->where('user_id', auth()->id())
                    ->where('status', 'pending')
                    ->exists();

                if ($hasPendingPayment) {
                    return;
                }

                $subscriptionId = DB::table('subscriptions')->insertGetId([
                    'user_id' => auth()->id(),
                    'plan' => $plan,
                    'amount' => $plans[$plan],
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('subscription_payments')->insert([
                    'subscription_id' => $subscriptionId,
                    'user_id' => auth()->id(),
                    'gcash_reference' => $reference,
                    'proof_path' => $proofPath,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return true;
            });
        } catch (UniqueConstraintViolationException) {
            return $this->backWithErrors(
                ['gcash_reference' => 'That GCash reference has already been submitted.'],
                '/technician/subscription'
            );
        } finally {
            if (! $paymentSaved && ! Storage::disk($proofDisk)->delete($proofPath)) {
                logger()->warning('Could not remove an unreferenced subscription payment proof.', [
                    'path' => $proofPath,
                ]);
            }
        }

        if ($paymentSaved) {
            $this->flash('success', 'Payment reference submitted. The administrator will verify your GCash payment and approve your account and subscription.');
        } else {
            $this->flash('info', 'You already have a payment awaiting review.');
        }

        return redirect('/technician/subscription');
    }

    private function markCheckoutFailed(int $paymentId): void
    {
        DB::transaction(function () use ($paymentId) {
            $payment = DB::table('subscription_payments')
                ->where('id', $paymentId)
                ->lockForUpdate()
                ->first();

            if (! $payment || $payment->status !== 'pending') {
                return;
            }

            DB::table('subscription_payments')->where('id', $paymentId)->update([
                'status' => 'failed',
                'updated_at' => now(),
            ]);
            DB::table('subscriptions')->where('id', $payment->subscription_id)
                ->where('status', 'pending')
                ->update(['status' => 'failed', 'updated_at' => now()]);
        });
    }

    private function startDemoCheckout()
    {
        $plans = config('subscriptions.prices');
        $plan = $this->input('plan');

        if (! is_string($plan) || ! array_key_exists($plan, $plans)) {
            return $this->backWithErrors(['plan' => 'Choose a valid subscription plan.'], '/technician/subscription');
        }

        $pendingPayment = DB::table('subscription_payments')
            ->where('user_id', auth()->id())
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        if ($pendingPayment) {
            if ($pendingPayment->payment_provider === 'demo') {
                return redirect('/technician/subscription/demo/'.$pendingPayment->id);
            }

            $this->flash('info', 'You already have a payment awaiting review.');

            return redirect('/technician/subscription');
        }

        $paymentId = DB::transaction(function () use ($plan, $plans) {
            DB::table('users')->where('id', auth()->id())->lockForUpdate()->first();

            $pendingPayment = DB::table('subscription_payments')
                ->where('user_id', auth()->id())
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if ($pendingPayment) {
                return null;
            }

            $subscriptionId = DB::table('subscriptions')->insertGetId([
                'user_id' => auth()->id(),
                'plan' => $plan,
                'amount' => $plans[$plan],
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return DB::table('subscription_payments')->insertGetId([
                'subscription_id' => $subscriptionId,
                'user_id' => auth()->id(),
                'gcash_reference' => 'DEMO-'.Str::upper((string) Str::uuid()),
                'proof_path' => '',
                'status' => 'pending',
                'payment_provider' => 'demo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        if (! $paymentId) {
            $this->flash('info', 'You already have a payment awaiting review.');

            return redirect('/technician/subscription');
        }

        return redirect('/technician/subscription/demo/'.$paymentId);
    }

    private function demoMode(): bool
    {
        return (bool) config('services.payments.demo_mode')
            && (app()->environment(['local', 'testing'])
                || config('services.payments.vercel_environment') === 'preview');
    }
}
